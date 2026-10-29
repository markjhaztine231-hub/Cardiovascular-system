<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\MedicationLog;
use App\Models\Prescription;
use App\Models\User;
use App\Services\MedicationTriZoneService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MedicationController extends Controller
{
    private MedicationTriZoneService $triZone;

    private function triZone(): MedicationTriZoneService
    {
        return $this->triZone ??= app(MedicationTriZoneService::class);
    }

    /**
     * Prescriptions belonging to an appointment's encounter(s), ordered by id so
     * the legacy zero-based "prescription_index" maps deterministically to a row
     * (prescriptions are no longer a JSON blob on appointments).
     */
    private function prescriptions(Appointment $appointment)
    {
        return Prescription::whereHas('encounter', function ($q) use ($appointment) {
            $q->where('appointment_id', $appointment->id);
        })
            // Free-text rows have no machine-readable frequency, so they have no
            // dose schedule. Excluding them here keeps the canonical index used
            // by log-dose/toggle-dose aligned with todayMeds().
            ->where('is_freeform', false)
            ->orderBy('id')
            ->get();
    }

    /**
     * A prescription is part of the patient's current regimen only while its
     * course is open. Null dates mean "no bound" for legacy rows.
     */
    private function isWithinCourse(Prescription $prescription, Carbon $date): bool
    {
        if ($prescription->status !== null && $prescription->status !== 'active') {
            return false;
        }

        if ($prescription->start_date && $prescription->start_date->gt($date)) {
            return false;
        }

        if ($prescription->end_date && $prescription->end_date->lt($date)) {
            return false;
        }

        return true;
    }

    /**
     * Records a single dose under the Pharmacokinetic Tri-Zone rules:
     *
     *   GREEN · [sched−1h, sched+1h]   → TAKEN / ON_TIME
     *   AMBER · (sched+1h, sched+½]    → TAKEN / LATE
     *   RED   · (sched+½, …]           → MISSED / EXPIRED_WINDOW (safety lockout)
     *
     * A 5-hour minimum cooldown between intakes always applies; blocked off-plan
     * attempts are audit-logged as "ATTEMPTED_OFF_SCHEDULE_INTAKE".
     */
    public function logDose(Request $request, Appointment $appointment)
    {
        if ($appointment->patient_id !== Auth::user()->patient?->id && Auth::user()->role !== 'doctor') {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'prescription_index' => 'required|integer|min:0',
            'medicine' => 'nullable|string|max:255',
            'scheduled_time' => 'nullable|date_format:H:i',
        ]);

        $prescriptions = $this->prescriptions($appointment);
        $prescription = $prescriptions[$validated['prescription_index']] ?? null;
        if (! $prescription) {
            return response()->json(['error' => 'Invalid prescription index.'], 422);
        }

        if (! $this->isWithinCourse($prescription, Carbon::today())) {
            return response()->json([
                'error' => 'This prescription is not active today.',
                'error_code' => 'COURSE_CLOSED',
            ], 422);
        }

        $count = $this->triZone()->dailyDoseCount($prescription->frequency);
        if ($count === 0) {
            return response()->json([
                'error' => 'This medication is "as needed" (PRN) — there is no fixed dosing window to record.',
                'error_code' => 'PRN_NO_WINDOW',
            ], 422);
        }

        $now = Carbon::now();
        $date = Carbon::today();

        // Sweep any earlier dose slots whose windows have already closed.
        $this->triZone()->syncMissedSlots($prescription, $date, $now);

        $records = MedicationLog::where('prescription_id', $prescription->id)
            ->whereDate('log_date', $date)
            ->get()
            ->keyBy('scheduled_time');

        $pinnedTime = $validated['scheduled_time'] ?? null;
        $slots = collect($this->triZone()->slotsFor($prescription, $date, $now));

        if ($pinnedTime) {
            $slot = $slots->firstWhere('time', $pinnedTime);
            if (! $slot) {
                return response()->json([
                    'error' => 'That time is not one of your scheduled doses for today.',
                    'error_code' => 'INVALID_SLOT',
                ], 422);
            }

            $pinnedRecord = $records->get($pinnedTime);
            if ($pinnedRecord?->status === MedicationTriZoneService::STATUS_TAKEN) {
                return response()->json(['error' => 'That dose has already been recorded today.'], 409);
            }

            // The window for this pinned dose has already closed and been swept
            // as MISSED — the attempt is off-schedule and must stay blocked.
            if ($pinnedRecord?->status === MedicationTriZoneService::STATUS_MISSED) {
                $this->auditAttempt($appointment, $prescription, $slot['time'], 'ATTEMPTED_OFF_SCHEDULE_INTAKE');

                return response()->json([
                    'error' => 'This dose window has already closed.',
                    'error_code' => 'SAFETY_LOCKOUT',
                    'message' => 'Dose skipped — the intake window is closed. Do NOT double your next dose. Your next scheduled intake is '.($this->nextSlot($prescription, $slot, $records, $date, $now) ?? 'tomorrow').'.',
                    'zone' => 'RED',
                    'adherence_flag' => MedicationTriZoneService::FLAG_EXPIRED_WINDOW,
                    'scheduled_time' => $slot['time'],
                    'next_scheduled_time' => $this->nextSlot($prescription, $slot, $records, $date, $now),
                ], 422);
            }
        } else {
            // Auto-pick the earliest scheduled dose not yet recorded (TAKEN or
            // MISSED slots are already closed).
            $slot = $slots->first(fn ($s) => ! $records->has($s['time']));
            if (! $slot) {
                return response()->json(['error' => 'All scheduled doses have already been recorded for today.'], 409);
            }
        }

        $zone = $slot['zone'];

        // RED: window has expired — lock the dose, mark it missed, audit the attempt.
        if ($zone === 'RED') {
            MedicationLog::updateOrCreate(
                [
                    'prescription_id' => $prescription->id,
                    'log_date' => $date->toDateString(),
                    'scheduled_time' => $slot['time'],
                ],
                [
                    'status' => MedicationTriZoneService::STATUS_MISSED,
                    'adherence_flag' => MedicationTriZoneService::FLAG_EXPIRED_WINDOW,
                    'taken_at' => null,
                ]
            );

            $this->auditAttempt($appointment, $prescription, $slot['time'], 'ATTEMPTED_OFF_SCHEDULE_INTAKE');

            return response()->json([
                'error' => 'This dose window has expired.',
                'error_code' => 'SAFETY_LOCKOUT',
                'message' => 'Dose skipped — the intake window is closed. Do NOT double the next dose. Your next scheduled intake is '.($this->nextSlot($prescription, $slot, $records, $date, $now) ?? 'tomorrow').'.',
                'zone' => 'RED',
                'adherence_flag' => MedicationTriZoneService::FLAG_EXPIRED_WINDOW,
                'scheduled_time' => $slot['time'],
                'next_scheduled_time' => $this->nextSlot($prescription, $slot, $records, $date, $now),
            ], 422);
        }

        if ($zone === 'AHEAD') {
            return response()->json([
                'error' => 'Your next scheduled intake is at '.$slot['time'].' — record it during its designated intake window.',
                'error_code' => 'WINDOW_NOT_OPEN',
                'zone' => 'AHEAD',
                'scheduled_time' => $slot['time'],
            ], 422);
        }

        // GREEN / AMBER → enforce the 5-hour minimum cooldown first.
        $cooldown = $this->triZone()->cooldown($prescription->id, $now);
        if ($cooldown['active']) {
            return response()->json([
                'error' => 'Cooldown in effect between doses.',
                'error_code' => 'COOLDOWN_ACTIVE',
                'message' => 'Please wait at least 5 hours between doses for your safety. You may record the next intake starting '
                    .$cooldown['next_available_at']->format('g:i A').'.',
                'last_taken_at' => $cooldown['last_taken_at']->toDateTimeString(),
                'next_available_at' => $cooldown['next_available_at']->toDateTimeString(),
            ], 422);
        }

        $isLate = $zone === 'AMBER';
        $log = MedicationLog::updateOrCreate(
            [
                'prescription_id' => $prescription->id,
                'log_date' => $date->toDateString(),
                'scheduled_time' => $slot['time'],
            ],
            [
                'status' => MedicationTriZoneService::STATUS_TAKEN,
                'adherence_flag' => $isLate ? MedicationTriZoneService::FLAG_LATE : MedicationTriZoneService::FLAG_ON_TIME,
                'taken_at' => $now,
            ]
        );

        AuditLog::create([
            'user_id' => Auth::id(),
            'patient_id' => $appointment->patient_id,
            'resource_type' => 'MedicationLog',
            'resource_id' => $log->id,
            'action' => 'Patient recorded intake of '.($prescription->medication_name ?? 'medication')
                .' ('.$prescription->dosage.') — scheduled '.$slot['time'].' — '.($isLate ? 'LATE' : 'ON_TIME'),
            'description' => $isLate
                ? 'Intake recorded after the 1-hour on-time window but within the safe-half (AMBER) window.'
                : 'Intake recorded within the on-time intake window.',
        ]);

        return response()->json([
            'message' => $isLate
                ? 'Dose recorded (late window). Keep an eye on your next scheduled intake.'
                : 'Dose recorded on time.',
            'zone' => $zone,
            'status' => MedicationTriZoneService::STATUS_TAKEN,
            'adherence_flag' => $isLate ? MedicationTriZoneService::FLAG_LATE : MedicationTriZoneService::FLAG_ON_TIME,
            'scheduled_time' => $slot['time'],
            'taken_at' => $now->toDateTimeString(),
            'cooldown' => $this->triZone()->cooldown($prescription->id, $now),
            'next_scheduled_time' => $this->nextSlot($prescription, $slot, $records, $date, $now),
        ]);
    }

    /**
     * Time of the next unrecorded dose after $slot (or null when none remain).
     */
    private function nextSlot(Prescription $prescription, array $slot, $records, Carbon $date, Carbon $now): ?string
    {
        $times = collect($this->triZone()->scheduledTimes($prescription->frequency));
        $after = $times->filter(fn ($t) => $t > $slot['time']);

        foreach ($after as $time) {
            if (! $records->has($time)) {
                return $time;
            }
        }

        return null;
    }

    private function auditAttempt(Appointment $appointment, Prescription $prescription, string $time, string $action): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'patient_id' => $appointment->patient_id,
            'resource_type' => 'Prescription',
            'resource_id' => $prescription->id,
            'action' => $action.' — '.($prescription->medication_name ?? 'medication').' at '.$time,
            'description' => 'Safety lockout engaged: the intake window for the '.$time.' dose had already closed (EXPIRED_WINDOW). '
                .'The attempt was blocked and the dose recorded as missed; no double-dosing occurred.',
        ]);
    }

    public function adherence(Appointment $appointment)
    {
        if ($appointment->patient_id !== Auth::user()->patient?->id && Auth::user()->role !== 'doctor') {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $prescriptions = $this->prescriptions($appointment);
        $active = $prescriptions->filter(
            fn (Prescription $p): bool => $p->status === null || $p->status === 'active'
        );

        $today = Carbon::today();
        $startDate = $active
            ->map(fn (Prescription $p) => ($p->start_date ?? Carbon::parse($appointment->scheduled_date))->copy()->startOfDay())
            ->min();

        if ($active->isEmpty() || ! $startDate || $startDate->gt($today)) {
            return response()->json(['daily' => [], 'appointment_id' => $appointment->id]);
        }

        $logs = MedicationLog::whereIn('prescription_id', $prescriptions->pluck('id'))
            ->get()
            ->groupBy(fn ($log) => $log->log_date->format('Y-m-d').'_'.$log->prescription_id);

        // A long-running course must show the most recent 90 days, not the
        // first 90 days after it started.
        $rangeStart = $startDate->copy();
        if ($rangeStart->diffInDays($today) + 1 > 90) {
            $rangeStart = $today->copy()->subDays(89);
        }

        $days = min($rangeStart->diffInDays($today) + 1, 90);

        $daily = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $rangeStart->copy()->addDays($i);
            $date = $day->format('Y-m-d');
            $dayData = ['date' => $date, 'medications' => []];

            // $active preserves the canonical keys of $prescriptions, so the
            // "index" below still matches log-dose/toggle-dose.
            foreach ($active as $idx => $p) {
                $courseStart = ($p->start_date?->copy()
                    ?? Carbon::parse($appointment->scheduled_date))->startOfDay();
                $courseEnd = ($p->end_date?->copy() ?? $today->copy())->startOfDay();

                if ($day->lt($courseStart) || $day->gt($courseEnd)) {
                    continue;
                }

                $expected = $this->triZone()->dailyDoseCount($p->frequency);
                if ($expected === 0) {
                    continue;
                }

                $taken = $logs->get($date.'_'.$p->id, collect())
                    ->where('status', MedicationTriZoneService::STATUS_TAKEN)
                    ->count();

                $dayData['medications'][] = [
                    'index' => $idx,
                    'id' => $p->id,
                    'medicine' => $p->medication_name,
                    'expected' => $expected,
                    'taken' => $taken,
                    'adherence' => round(($taken / $expected) * 100),
                ];
            }

            $daily[] = $dayData;
        }

        return response()->json(['daily' => $daily, 'appointment_id' => $appointment->id]);
    }

    public function toggleDose(Request $request, Appointment $appointment)
    {
        if ($appointment->patient_id !== Auth::user()->patient?->id && Auth::user()->role !== 'doctor') {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'prescription_index' => 'required|integer|min:0',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $prescriptions = $this->prescriptions($appointment);
        $prescription = $prescriptions[$validated['prescription_index']] ?? null;
        if (! $prescription) {
            return response()->json(['error' => 'Invalid prescription index.'], 422);
        }

        $toggleDate = Carbon::parse($validated['date']);
        if ($toggleDate->gt(Carbon::today())) {
            return response()->json([
                'error' => 'Future doses cannot be recorded.',
                'error_code' => 'FUTURE_DATE',
            ], 422);
        }

        if (! $this->isWithinCourse($prescription, $toggleDate)) {
            return response()->json([
                'error' => 'That date is outside this prescription\'s course.',
                'error_code' => 'COURSE_CLOSED',
            ], 422);
        }

        $expectedDaily = $this->triZone()->dailyDoseCount($prescription->frequency);
        if ($expectedDaily === 0) {
            return response()->json(['error' => 'No expected daily dose for this prescription.'], 422);
        }

        $existingLogs = MedicationLog::where('prescription_id', $prescription->id)
            ->whereDate('log_date', $validated['date'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($existingLogs->isNotEmpty()) {
            $existingLogs->first()->delete();

            return response()->json(['message' => 'Dose removed.', 'action' => 'removed']);
        }

        $log = MedicationLog::create([
            'prescription_id' => $prescription->id,
            'log_date' => $validated['date'],
            'scheduled_time' => null,
            'taken_at' => $validated['date'].' 00:00:00',
        ]);

        // Retrospective chart toggle — counts as a taken dose.
        $log->update([
            'status' => MedicationTriZoneService::STATUS_TAKEN,
            'adherence_flag' => MedicationTriZoneService::FLAG_ON_TIME,
        ]);

        return response()->json([
            'message' => 'Dose logged successfully.',
            'action' => 'added',
            'log' => $log,
        ]);
    }

    public function todayMeds()
    {
        $patientRecord = Auth::user()->patient;
        if (! $patientRecord) {
            return response()->json([]);
        }

        $encounters = $patientRecord->encounters()
            ->whereHas('prescriptions')
            ->with(['prescriptions' => fn ($q) => $q->where('is_freeform', false), 'appointment:id,patient_id,scheduled_date'])
            ->orderByDesc('started_at')
            ->limit(5)
            ->get();

        $now = Carbon::now();
        $date = Carbon::today();

        // "prescription_index" is a zero-based position within the whole,
        // id-ordered prescription list of one appointment — the same contract
        // log-dose, toggle-dose, and adherence resolve against. Build that map
        // up front so a tracker click can never send an index that the server
        // resolves to a different prescription.
        $canonicalIndexes = [];
        $appointmentIds = $encounters->pluck('appointment_id')->filter()->unique()->values();

        if ($appointmentIds->isNotEmpty()) {
            $rows = DB::table('prescriptions')
                ->join('encounters', 'encounters.id', '=', 'prescriptions.encounter_id')
                ->whereIn('encounters.appointment_id', $appointmentIds)
                ->where('prescriptions.is_freeform', false)
                ->orderBy('prescriptions.id')
                ->get(['prescriptions.id as prescription_id', 'encounters.appointment_id as appointment_id']);

            $positions = [];
            foreach ($rows as $row) {
                $positions[$row->appointment_id] = ($positions[$row->appointment_id] ?? -1) + 1;
                $canonicalIndexes[$row->appointment_id][$row->prescription_id] = $positions[$row->appointment_id];
            }
        }

        $seen = [];
        $meds = [];
        foreach ($encounters as $encounter) {
            foreach ($encounter->prescriptions->sortBy('id')->values() as $localIndex => $p) {
                $key = strtolower(trim((string) $p->medication_name));
                if ($key !== '' && in_array($key, $seen, true)) {
                    continue;
                }
                if (! $this->isWithinCourse($p, $date)) {
                    continue;
                }
                if ($key !== '') {
                    $seen[] = $key;
                }

                $expected = $this->triZone()->dailyDoseCount($p->frequency);
                if ($expected === 0) {
                    $meds[] = [
                        'prescription_index' => $canonicalIndexes[$encounter->appointment_id][$p->id] ?? $localIndex,
                        'prescription_id' => $p->id,
                        'appointment_id' => $encounter->appointment_id,
                        'medicine' => $p->medication_name,
                        'dosage' => $p->dosage ?? '',
                        'frequency' => $p->frequency ?? '',
                        'duration' => $p->duration_days
                            ? $p->duration_days.' days'
                            : ($p->end_date ? 'Until '.$p->end_date->format('M j, Y') : 'Ongoing'),
                        'instructions' => $p->instructions,
                        'start_date' => $p->start_date?->toDateString(),
                        'end_date' => $p->end_date?->toDateString(),
                        'as_needed' => true,
                        'expected_doses' => 0,
                        'taken_doses' => 0,
                        'all_taken' => false,
                        'prescribed_at' => ($encounter->appointment?->scheduled_date
                            ?? $encounter->started_at?->toDateString()),
                        'doses' => [],
                        'cooldown' => [
                            'active' => false,
                            'last_taken_at' => null,
                            'next_available_at' => null,
                        ],
                    ];

                    continue;
                }

                // Keep missed/expired slots visible as locked doses, then read
                // the ground-truth rows (the sweep just created MISSED entries).
                $this->triZone()->syncMissedSlots($p, $date, $now);

                $records = MedicationLog::where('prescription_id', $p->id)
                    ->whereDate('log_date', $date)
                    ->get()
                    ->keyBy('scheduled_time');
                $takenToday = $records->where('status', MedicationTriZoneService::STATUS_TAKEN)->count();
                $cooldown = $this->triZone()->cooldown($p->id, $now);

                $doses = [];
                foreach ($this->triZone()->slotsFor($p, $date, $now) as $slot) {
                    $record = $records->get($slot['time']);
                    $status = $record?->status ?? null;

                    $canTake = true;
                    $blockedBy = null;
                    if ($status === MedicationTriZoneService::STATUS_TAKEN) {
                        $canTake = false;
                    } elseif ($status === MedicationTriZoneService::STATUS_MISSED || $slot['zone'] === 'RED') {
                        $status = MedicationTriZoneService::STATUS_MISSED;
                        $canTake = false;
                        $blockedBy = 'SAFETY_LOCKOUT';
                    } elseif ($slot['zone'] === 'AHEAD') {
                        $canTake = false;
                        $blockedBy = 'WINDOW_NOT_OPEN';
                    } elseif ($cooldown['active']) {
                        $canTake = false;
                        $blockedBy = 'COOLDOWN_ACTIVE';
                    }

                    $doses[] = [
                        'scheduled_time' => $slot['time'],
                        'zone' => $slot['zone'],
                        'status' => $status ?? 'PENDING',
                        'adherence_flag' => $record?->adherence_flag ?? null,
                        'taken_at' => $record?->taken_at?->toISOString() ?? null,
                        'can_take' => $canTake,
                        'blocked_by' => $blockedBy,
                    ];
                }

                $meds[] = [
                    'prescription_index' => $canonicalIndexes[$encounter->appointment_id][$p->id] ?? $localIndex,
                    'prescription_id' => $p->id,
                    'appointment_id' => $encounter->appointment_id,
                    'medicine' => $p->medication_name,
                    'dosage' => $p->dosage ?? '',
                    'frequency' => $p->frequency ?? '',
                    'duration' => $p->duration_days
                        ? $p->duration_days.' days'
                        : ($p->end_date ? 'Until '.$p->end_date->format('M j, Y') : 'Ongoing'),
                    'instructions' => $p->instructions,
                    'start_date' => $p->start_date?->toDateString(),
                    'end_date' => $p->end_date?->toDateString(),
                    'as_needed' => false,
                    'expected_doses' => $expected,
                    'taken_doses' => $takenToday,
                    'all_taken' => $takenToday >= $expected,
                    'prescribed_at' => ($encounter->appointment?->scheduled_date
                        ?? $encounter->started_at?->toDateString()),
                    'doses' => $doses,
                    'cooldown' => [
                        'active' => $cooldown['active'],
                        'last_taken_at' => $cooldown['last_taken_at']?->toISOString(),
                        'next_available_at' => $cooldown['next_available_at']?->toISOString(),
                    ],
                ];
            }
        }

        return response()->json($meds);
    }

    public function history()
    {
        $patientRecord = Auth::user()->patient;
        if (! $patientRecord) {
            return response()->json([]);
        }

        $logs = MedicationLog::whereHas('prescription', function ($q) use ($patientRecord) {
            $q->whereHas('encounter', fn ($e) => $e->where('patient_id', $patientRecord->id));
        })->with(
            'prescription:id,medication_name,encounter_id',
            'prescription.encounter:id,appointment_id',
            'prescription.encounter.appointment:id,scheduled_date'
        )
            ->whereDate('log_date', '>=', now()->subDays(30))
            ->orderByDesc('taken_at')
            ->limit(100)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'medicine' => $log->prescription?->medication_name ?? 'Unknown',
                    'log_date' => $log->log_date->format('M d, Y'),
                    'taken_at' => $log->taken_at?->format('h:i A') ?? '—',
                    'scheduled_time' => $log->scheduled_time,
                    'status' => $log->status,
                    'adherence_flag' => $log->adherence_flag,
                    'appointment_date' => $log->prescription?->encounter?->appointment?->scheduled_date?->format('M d, Y') ?? '—',
                ];
            });

        return response()->json($logs);
    }

    public function patientAdherence(User $patient)
    {
        if (Auth::user()->role !== 'doctor') {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $patientRecord = $patient->patient;
        if (! $patientRecord) {
            return response()->json(['medications' => []]);
        }

        $encounters = $patientRecord->encounters()
            ->whereHas('prescriptions')
            ->with(['prescriptions' => fn ($q) => $q->where('is_freeform', false), 'appointment:id,scheduled_date'])
            ->orderByDesc('started_at')
            ->limit(10)
            ->get();

        $allLogs = MedicationLog::whereIn('prescription_id', $encounters->flatMap->prescriptions->pluck('id'))->get();

        $today = Carbon::today();

        $medications = [];
        foreach ($encounters as $encounter) {
            foreach ($encounter->prescriptions as $p) {
                if ($p->is_freeform) {
                    continue;
                }

                if ($p->status !== null && $p->status !== 'active') {
                    continue;
                }

                $expectedDaily = $this->triZone()->dailyDoseCount($p->frequency);
                if ($expectedDaily === 0) {
                    continue;
                }

                $rawStart = $p->start_date
                    ?? $encounter->appointment?->scheduled_date
                    ?? $encounter->started_at;
                if (! $rawStart) {
                    continue;
                }

                $courseStart = Carbon::parse($rawStart)->startOfDay();
                if ($courseStart->gt($today)) {
                    continue;
                }

                $courseEnd = Carbon::parse($p->end_date ?? $today)->startOfDay();
                if ($courseEnd->lt($courseStart)) {
                    continue;
                }
                if ($courseEnd->gt($today)) {
                    $courseEnd = $today->copy();
                }

                $windowStart = $courseStart->copy();
                if ($windowStart->diffInDays($courseEnd) + 1 > 90) {
                    $windowStart = $courseEnd->copy()->subDays(89);
                }

                $totalDays = min($windowStart->diffInDays($courseEnd) + 1, 90);

                $logs = $allLogs->where('prescription_id', $p->id)
                    ->filter(fn ($log) => $log->log_date->betweenIncluded($windowStart, $courseEnd));
                $takenLogs = $logs->where('status', MedicationTriZoneService::STATUS_TAKEN);
                $missedLogs = $logs->where('status', MedicationTriZoneService::STATUS_MISSED);
                $totalTaken = $takenLogs->count();

                $totalExpected = $totalDays * $expectedDaily;
                $adherenceRate = $totalExpected > 0 ? round(($totalTaken / $totalExpected) * 100) : 0;

                $dailyData = [];
                for ($d = 0; $d < $totalDays; $d++) {
                    $date = $windowStart->copy()->addDays($d)->format('Y-m-d');
                    $dailyData[] = [
                        'date' => $date,
                        'taken' => $takenLogs->filter(fn ($l) => $l->log_date->format('Y-m-d') === $date)->count(),
                        'missed' => $missedLogs->filter(fn ($l) => $l->log_date->format('Y-m-d') === $date)->count(),
                        'expected' => $expectedDaily,
                    ];
                }

                $medications[] = [
                    'prescription_id' => $p->id,
                    'medicine' => $p->medication_name,
                    'dosage' => $p->dosage ?? '',
                    'frequency' => $p->frequency ?? '',
                    'duration' => $p->duration_days ? $p->duration_days.' days' : '',
                    'adherence_rate' => $adherenceRate,
                    'total_expected' => $totalExpected,
                    'total_taken' => $totalTaken,
                    'total_missed' => $missedLogs->count(),
                    'daily' => $dailyData,
                ];
            }
        }

        return response()->json(['medications' => $medications]);
    }
}
