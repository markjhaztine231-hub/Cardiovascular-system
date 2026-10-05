<?php

namespace App\Http\Controllers;

use App\Events\AppointmentRescheduled;
use App\Events\AppointmentStatusChanged;
use App\Events\ConsultationCompleted;
use App\Events\QueueUpdated;
use App\Events\RescheduleRequested;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Diagnosis;
use App\Models\DoctorSchedule;
use App\Models\DopplerStudy;
use App\Models\Echocardiogram;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Provider;
use App\Models\SoapNote;
use App\Models\TreadmillTest;
use App\Models\User;
use App\Models\VitalSign;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function __construct(protected GoogleCalendarService $calendar) {}

    // 1. Patient books a new appointment (max 2 per day enforced)
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required',
            'service' => 'required|string|in:Consultation,Lab Exam,Consultation with Lab Exam',
        ]);

        // Reject past/too-near slots when booking for today — mirrors the
        // 30-minute cut-off used by the availability endpoint so the dropdown
        // and the server never disagree.
        if ($request->date === now()->toDateString()) {
            $slotAt = Carbon::parse($request->date.' '.$request->time);
            // Slots arrive on a 30-min grid, so the first bookable one at
            // e.g. 7:32 PM is already 8:00 PM - no extra lead window.
            if ($slotAt->lte(Carbon::now())) {
                throw ValidationException::withMessages([
                    'time' => 'That time slot has already passed. Please select a future time.',
                ]);
            }
        }

        // Resolve the Patient record behind the authenticated user
        $patient = Auth::user()->patient;
        if (! $patient) {
            throw ValidationException::withMessages([
                'date' => 'No patient record is linked to your account.',
            ]);
        }

        // Enforce max 3 active appointments per patient per day
        $dailyCount = Appointment::where('patient_id', $patient->id)
            ->whereDate('scheduled_date', $request->date)
            ->whereNotIn('status', ['Cancelled', 'No-Show'])
            ->count();

        if ($dailyCount >= 3) {
            throw ValidationException::withMessages([
                'date' => 'You already have 3 appointments booked for this day. Maximum is 3 per day.',
            ]);
        }

        $providerId = self::autoAssignDoctor($request->date, $request->time);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $providerId,
            'scheduled_date' => $request->date,
            'scheduled_time' => $request->time,
            'duration_minutes' => $this->getDurationMinutes($patient->id),
            'service_type' => $request->service,
            'status' => 'Scheduled',
        ]);

        // Sync to Google Calendar
        $event = $this->calendar->createEvent($appointment);
        if ($event) {
            $appointment->update([
                'google_event_id' => $event['id'],
                'google_event_url' => $event['url'],
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Booked new appointment for '.$request->date.' (Assigned to Doctor ID: '.$providerId.')',
        ]);

        Cache::forget('receptionist_stats');
        Cache::forget('admin_dashboard_stats');
        $this->broadcastQueueUpdated($providerId);

        return back()->with('success', 'Appointment successfully booked! A doctor has been assigned.');
    }

    /**
     * Live slot-availability endpoint. Recomputes the bookable times for a
     * chosen date on every request: doctor coverage, existing appointments,
     * and (for today) slots too near the current time are all filtered out so
     * the patient form never shows a slot the server would reject.
     */
    public function availableSlots(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $date = $validated['date'];
        $dayInt = Carbon::parse($date)->dayOfWeekIso; // 1 = Monday .. 7 = Sunday
        $step = 30; // minutes between bookable slots

        $schedules = DoctorSchedule::whereJsonContains('day_of_week', $dayInt)->get();

        if ($schedules->isEmpty()) {
            return response()->json(['date' => $date, 'slots' => []]);
        }

        $candidateTimes = collect();
        foreach ($schedules as $schedule) {
            [$sh, $sm] = array_map('intval', explode(':', $schedule->start_time));
            [$eh, $em] = array_map('intval', explode(':', $schedule->end_time));
            $start = $sh * 60 + $sm;
            $end = $eh * 60 + $em;
            for ($t = $start; $t < $end; $t += $step) {
                $candidateTimes->push(sprintf('%02d:%02d:00', intdiv($t, 60), $t % 60));
            }
        }

        $isToday = $date === now()->toDateString();

        $slots = $candidateTimes
            ->unique()
            ->sort()
            ->values()
            ->filter(function (string $time) use ($date, $isToday) {
                if ($isToday) {
                    $slotAt = Carbon::parse($date.' '.$time);
                    // Next boundary on the 30-min grid after now (at 7:32 PM
                    // that's 8:00 PM) - no extra lead offset.
                    if ($slotAt->lte(Carbon::now())) {
                        return false;
                    }
                }

                return self::providersAvailableAt($date, $time)->isNotEmpty();
            })
            ->map(fn ($time) => [
                'value' => $time,
                'label' => Carbon::parse($time)->format('g:i A'),
            ])
            ->values();

        return response()->json(['date' => $date, 'slots' => $slots]);
    }

    // 1b. Patient cancels their own upcoming appointment
    public function cancelAppointment(Appointment $appointment)
    {
        if (! Auth::user()->patient || $appointment->patient_id !== Auth::user()->patient->id) {
            abort(403);
        }

        // Cannot cancel a terminal or in-progress appointment
        if (in_array($appointment->status, ['Completed', 'No-Show', 'In Progress', 'Cancelled'])) {
            return back()->withErrors([
                'error' => 'This appointment cannot be cancelled — it is already '.$appointment->status.'.',
            ]);
        }

        $appointment->update(['status' => 'Cancelled']);
        $this->calendar->updateStatus($appointment);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Patient cancelled appointment #'.$appointment->id,
        ]);

        Cache::forget('receptionist_stats');
        Cache::forget('admin_dashboard_stats');
        event(new AppointmentStatusChanged($appointment->fresh()));
        $this->broadcastQueueUpdated($appointment->provider_id);

        return back()->with('success', 'Your appointment has been cancelled.');
    }

    // 2. Doctor/Receptionist updates the status
    public function updateStatus(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Scheduled,Pending,Approved,Cancelled,No-Show,Completed,In Progress,Reschedule Requested',
        ]);

        $appointment->update(['status' => $validated['status']]);

        // Reflect new status on the Google Calendar event
        $this->calendar->updateStatus($appointment);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Updated appointment #'.$appointment->id.' status to '.$validated['status'],
        ]);

        Cache::forget('receptionist_stats');
        Cache::forget('admin_dashboard_stats');
        event(new AppointmentStatusChanged($appointment->fresh()));
        $this->broadcastQueueUpdated($appointment->provider_id);

        return back()->with('success', 'Status updated successfully!');
    }

    // 3. Show Manual Booking Page for Receptionist
    public function create()
    {
        // ✅ OPTIMIZED: clinics expose the Provider roster — the Vue form keeps
        // sending its selection back as `doctor_id` (now a provider ID).
        $doctors = Provider::with('user:id,name')
            ->get(['id', 'user_id', 'specialty'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->user?->name ?? 'Unknown',
                'specialty' => $p->specialty,
            ])
            ->values();

        return inertia('Appointments/Create', [
            'doctors' => $doctors,
        ]);
    }

    // 4. Handle Manual Walk-in Submission
    public function storeManual(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:1',
            'gender' => 'required|string|in:Male,Female',
            'phone' => 'required|string|max:20',
            'doctor_id' => 'required|exists:providers,id',
            'service_type' => 'required|string|in:Consultation,Lab Exam,Consultation with Lab Exam',
            'date' => 'required|date',
            'time' => 'required',
        ]);

        // Can't schedule a walk-in into a slot that already started today.
        if ($request->date === now()->toDateString()) {
            $slotAt = Carbon::parse($request->date.' '.$request->time);
            if ($slotAt->lte(Carbon::now())) {
                throw ValidationException::withMessages([
                    'time' => 'That time has already passed. Please select a future time.',
                ]);
            }
        }

        $conflict = Appointment::where('provider_id', $request->doctor_id)
            ->whereDate('scheduled_date', $request->date)
            ->whereTime('scheduled_time', $request->time)
            ->whereNotIn('status', ['Cancelled', 'No-Show'])
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'time' => 'This doctor already has an appointment at this time.',
            ]);
        }

        // Use a cryptographically random suffix to prevent email collisions
        $generatedEmail = strtolower(str_replace(' ', '', $request->name))
            .'_'.Str::random(10)
            .'@walkin.clinic.com';

        $patient = User::create([
            'name' => $request->name,
            'email' => $generatedEmail,
            'password' => Hash::make(Str::random(16)), // random password — walk-ins log in via receptionist
            'role' => 'patient',
            'status' => 'Walk-in Register',
        ]);

        // Walk-in demographics belong to the normalized patients table.
        // DOB is approximated from the declared age (YYYY-MM-DD of N years ago).
        $patientRecord = Patient::create([
            'user_id' => $patient->id,
            'date_of_birth' => now()->subYears($request->age)->toDateString(),
            'sex' => $request->gender,
            'contact_phone' => $request->phone,
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patientRecord->id,
            'provider_id' => $request->doctor_id,
            'scheduled_date' => $request->date,
            'scheduled_time' => $request->time,
            'duration_minutes' => $this->getDurationMinutes($patientRecord->id),
            'service_type' => $request->service_type,
            'status' => 'Pending',
            'notes' => 'Age: '.$request->age.' | Gender: '.$request->gender.' | Phone: '.$request->phone,
        ]);

        // Sync to Google Calendar
        $event = $this->calendar->createEvent($appointment);
        if ($event) {
            $appointment->update([
                'google_event_id' => $event['id'],
                'google_event_url' => $event['url'],
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Registered walk-in patient: '.$request->name,
        ]);

        Cache::forget('receptionist_stats');
        Cache::forget('admin_dashboard_stats');
        $this->broadcastQueueUpdated($request->doctor_id);

        return redirect()->route('receptionist.dashboard')->with('success', 'Walk-in added to queue!');
    }

    // 5. Show Returning Patient Booking Page
    public function createReturning()
    {
        // ✅ OPTIMIZED: patients now live in the normalized patients table
        $patients = Patient::with('user:id,name,email')
            ->get(['id', 'user_id'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->user?->anonymizedName() ?? 'Unknown',
                'email' => $p->user?->email ?? '',
            ])
            ->sortBy('name')
            ->take(500)  // ✅ Cap at 500 for frontend search
            ->values();

        $doctors = Provider::with('user:id,name')
            ->get(['id', 'user_id', 'specialty'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->user?->name ?? 'Unknown',
                'specialty' => $p->specialty,
            ])
            ->values();

        return inertia('Appointments/Returning', [
            'patients' => $patients,
            'doctors' => $doctors,
        ]);
    }

    // 6. Handle Returning Patient Booking
    public function storeReturning(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:providers,id',
            'service_type' => 'required|string|in:Consultation,Lab Exam,Consultation with Lab Exam',
            'date' => 'required|date',
            'time' => 'required',
        ]);

        // Can't book a returning patient into a slot that already started today.
        if ($request->date === now()->toDateString()) {
            $slotAt = Carbon::parse($request->date.' '.$request->time);
            if ($slotAt->lte(Carbon::now())) {
                throw ValidationException::withMessages([
                    'time' => 'That time has already passed. Please select a future time.',
                ]);
            }
        }

        $conflict = Appointment::where('provider_id', $request->doctor_id)
            ->whereDate('scheduled_date', $request->date)
            ->whereTime('scheduled_time', $request->time)
            ->whereNotIn('status', ['Cancelled', 'No-Show'])
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'time' => 'This doctor already has an appointment at this time.',
            ]);
        }

        $appointment = Appointment::create([
            'patient_id' => $request->patient_id,
            'provider_id' => $request->doctor_id,
            'scheduled_date' => $request->date,
            'scheduled_time' => $request->time,
            'duration_minutes' => $this->getDurationMinutes($request->patient_id),
            'service_type' => $request->service_type,
            'status' => 'Pending',
        ]);

        // Sync to Google Calendar
        $event = $this->calendar->createEvent($appointment);
        if ($event) {
            $appointment->update([
                'google_event_id' => $event['id'],
                'google_event_url' => $event['url'],
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Booked follow-up appointment for Patient ID: '.$request->patient_id,
        ]);

        Cache::forget('receptionist_stats');
        Cache::forget('admin_dashboard_stats');
        $this->broadcastQueueUpdated($request->doctor_id);

        return redirect()->route('receptionist.dashboard')->with('success', 'Returning patient successfully added to queue!');
    }

    // 7. Save Consultation (SOAP) Record — normalized into clinical sub-tables
    public function completeConsultation(Request $request, Appointment $appointment)
    {
        // Only the assigned doctor may complete their own consultation
        if (! $appointment->provider || $appointment->provider->user_id !== Auth::id()) {
            abort(403, 'Only the assigned doctor may complete this consultation.');
        }

        // Comprehensive validation with required fields and constraints
        $validated = $request->validate([
            'symptoms' => 'required|string|max:5000',
            'vitals' => 'required|string|max:5000',
            'diagnosis' => 'required|string|max:500',
            'plan' => 'nullable|string|max:5000',
            'consultation_type' => 'nullable|array',
            'consultation_type.*' => 'string|in:Type 1,Type 2,Type 3',
            'prescriptions' => 'nullable|array',
            'prescriptions.*.medicine' => 'nullable|string|max:255',
            'prescriptions.*.dosage' => 'nullable|string|max:100',
            'prescriptions.*.frequency' => 'nullable|string|max:100',
            'prescriptions.*.duration' => 'nullable|string|max:50',
            'ai_summary' => 'nullable|string|max:5000',
            'echo_data' => 'nullable|array',
            'tmt_data' => 'nullable|array',
            'doppler_data' => 'nullable|array',
        ]);

        // Sanitize text fields to prevent XSS
        $validated['symptoms'] = $this->sanitizeText($validated['symptoms']);
        $validated['vitals'] = $this->sanitizeText($validated['vitals']);
        $validated['diagnosis'] = $this->sanitizeText($validated['diagnosis']);
        $validated['plan'] = $this->sanitizeText($validated['plan'] ?? '');

        // Sanitize prescription data and filter out empty rows
        if (! empty($validated['prescriptions'])) {
            foreach ($validated['prescriptions'] as $i => $prescription) {
                if (! empty($prescription['medicine'])) {
                    $validated['prescriptions'][$i]['medicine'] = $this->sanitizeText($prescription['medicine']);
                    $validated['prescriptions'][$i]['dosage'] = $this->sanitizeText($prescription['dosage'] ?? '');
                    $validated['prescriptions'][$i]['frequency'] = $this->sanitizeText($prescription['frequency'] ?? '');
                    $validated['prescriptions'][$i]['duration'] = $this->sanitizeText($prescription['duration'] ?? '');
                }
            }
            $validated['prescriptions'] = array_values(array_filter(
                $validated['prescriptions'],
                fn ($p) => ! empty($p['medicine'])
            ));
        }

        $selectedTypes = $validated['consultation_type'] ?? [];

        if (! in_array('Type 1', $selectedTypes)) {
            $validated['echo_data'] = null;
        }

        if (! in_array('Type 2', $selectedTypes)) {
            $validated['tmt_data'] = null;
        }

        if (! in_array('Type 3', $selectedTypes)) {
            $validated['doppler_data'] = null;
        }

        $encounter = Encounter::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'patient_id' => $appointment->patient_id,
                'provider_id' => $appointment->provider_id,
                'encounter_type' => $this->resolveEncounterType($selectedTypes),
                'status' => 'completed',
                'chief_complaint' => $validated['symptoms'],
                'started_at' => Carbon::parse($appointment->scheduled_date->format('Y-m-d').' '.($appointment->scheduled_time ?? '08:00:00')),
                'ended_at' => now(),
            ]
        );

        SoapNote::updateOrCreate(
            ['encounter_id' => $encounter->id],
            [
                'subjective' => $validated['symptoms'],
                'objective' => $validated['vitals'],
                'assessment' => $validated['diagnosis'],
                'plan' => $validated['plan'] ?? '',
                'ai_summary' => $validated['ai_summary'] ?? null,
            ]
        );

        // Structured vitals — derive fields from the "BP: .. | HR: .." string
        $vitals = $this->parseVitalsText($validated['vitals']);
        if (! empty($vitals)) {
            VitalSign::updateOrCreate(
                ['encounter_id' => $encounter->id],
                array_merge($vitals, ['recorded_at' => now()])
            );
        }

        // Diagnosis rows (legacy single-string diagnosis → one ICD row)
        $encounter->diagnoses()->delete();
        $encounter->diagnoses()->create([
            'icd_code' => null,
            'description' => $validated['diagnosis'],
            'is_primary' => true,
            'diagnosed_at' => now(),
        ]);

        // Prescriptions (replaces the legacy JSON array; old adherence logs cascade)
        $encounter->prescriptions()->delete();
        foreach ($validated['prescriptions'] ?? [] as $rx) {
            $startDate = Carbon::parse($appointment->scheduled_date);
            $days = null;
            if (! empty($rx['duration'])) {
                if (preg_match('/^\s*(\d{1,3})\s*(day|days|week|weeks|month|months)?/i', $rx['duration'], $m)) {
                    $mult = match (strtolower($m[2] ?? 'day')) {
                        'week', 'weeks' => 7,
                        'month', 'months' => 30,
                        default => 1,
                    };
                    $days = max(1, (int) $m[1] * $mult);
                }
            }
            Prescription::create([
                'encounter_id' => $encounter->id,
                'patient_id' => $appointment->patient_id,
                'provider_id' => $appointment->provider_id,
                'medication_name' => $rx['medicine'],
                'dosage' => $rx['dosage'] ?? null,
                'frequency' => $rx['frequency'] ?? null,
                'duration_days' => $days,
                'start_date' => $startDate->toDateString(),
                'end_date' => $days ? $startDate->copy()->addDays($days)->toDateString() : null,
                'status' => 'active',
            ]);
        }

        // Structured cardiac studies (1:1 with the encounter)
        $encounter->echocardiogram()?->delete();
        if (! empty($validated['echo_data'])) {
            Echocardiogram::create(array_merge(
                $this->mapEchoData($validated['echo_data']),
                ['encounter_id' => $encounter->id, 'performed_at' => now()]
            ));
        }

        $encounter->treadmillTest()?->delete();
        if (! empty($validated['tmt_data'])) {
            TreadmillTest::create(array_merge(
                $this->mapTmtData($validated['tmt_data']),
                ['encounter_id' => $encounter->id, 'performed_at' => now()]
            ));
        }

        $encounter->dopplerStudy()?->delete();
        if (! empty($validated['doppler_data'])) {
            DopplerStudy::create(array_merge(
                $this->mapDopplerData($validated['doppler_data']),
                ['encounter_id' => $encounter->id, 'performed_at' => now()]
            ));
        }

        $appointment->update(['status' => 'Completed']);

        // Mark event as completed in Google Calendar
        $this->calendar->updateStatus($appointment);

        AuditLog::create([
            'user_id' => Auth::id(),
            'patient_id' => $appointment->patient_id,
            'action' => 'Completed consultation and saved EMR for Appointment #'.$appointment->id,
        ]);

        Cache::forget('receptionist_stats');
        Cache::forget('admin_dashboard_stats');
        $appointment->refresh();
        event(new AppointmentStatusChanged($appointment));
        event(new ConsultationCompleted($appointment));

        return back()->with('success', 'Consultation record saved successfully! Patient history updated.');
    }

    /**
     * Broadcast the current queue to a doctor's WebSocket channel.
     * The channel is keyed by the provider's user id (matches the doctor's
     * authenticated user), while the query is provider-scoped.
     */
    private function broadcastQueueUpdated(int $providerId): void
    {
        $queue = Appointment::with('patient:id,user_id', 'patient.user:id,name')
            ->where('provider_id', $providerId)
            ->whereDate('scheduled_date', today())
            ->whereNotIn('status', ['Completed', 'No-Show'])
            ->orderBy('scheduled_time')
            ->get();

        $userId = Provider::whereKey($providerId)->value('user_id');

        event(new QueueUpdated($userId, $queue));
    }

    /**
     * Determine appointment duration based on visit history.
     * First visit → 90 min (1h 30m), follow-up → 30 min.
     */
    private function getDurationMinutes(int $patientId): int
    {
        $hasPreviousVisits = Appointment::where('patient_id', $patientId)
            ->whereNotIn('status', ['Cancelled', 'No-Show'])
            ->exists();

        return $hasPreviousVisits ? 30 : 90;
    }

    /**
     * Sanitize user input to prevent XSS attacks
     * Remove HTML-like characters and cap length
     */
    private function sanitizeText(string $text, int $maxLength = 5000): string
    {
        return trim(
            htmlspecialchars(
                substr($text, 0, $maxLength),
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }

    /**
     * Derive the encounter_type from the selected consultation types.
     */
    private function resolveEncounterType(array $types): string
    {
        return match (true) {
            in_array('Type 1', $types) => 'echo',
            in_array('Type 2', $types) => 'tmt',
            in_array('Type 3', $types) => 'doppler',
            default => 'consultation',
        };
    }

    /**
     * Parse a legacy vitals string like "BP: 138/88 mmHg | HR: 82 bpm | ..."
     * into the structured vital_signs columns.
     */
    private function parseVitalsText(?string $text): array
    {
        if (! $text) {
            return [];
        }

        $row = [];

        if (preg_match('/BP:\s*(\d+)\/(\d+)/i', $text, $m)) {
            $row['blood_pressure_systolic'] = (int) $m[1];
            $row['blood_pressure_diastolic'] = (int) $m[2];
        }
        if (preg_match('/HR:\s*(\d+)/i', $text, $m)) {
            $row['heart_rate_bpm'] = (int) $m[1];
        }
        if (preg_match('/RR:\s*(\d+)/i', $text, $m)) {
            $row['respiratory_rate'] = (int) $m[1];
        }
        if (preg_match('/Temp:\s*([\d.]+)/i', $text, $m)) {
            $row['temperature_celsius'] = (float) $m[1];
        }
        if (preg_match('/SpO2:\s*(\d+)/i', $text, $m)) {
            $row['oxygen_saturation_percent'] = (int) $m[1];
        }
        if (preg_match('/Weight:\s*(\d+)/i', $text, $m)) {
            $row['weight_kg'] = (int) $m[1];
        }

        return $row;
    }

    /**
     * First non-empty numeric value among the given keys on the posted form.
     */
    private function num(array $data, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null) {
                $raw = trim((string) $data[$key]);
                // values may be formatted like "173 bpm" or "180 mmHg" - take the
                // first numeric token so they still fill the normalized column.
                if ($raw !== '' && preg_match('/-?\d+(?:\.\d+)?/', str_replace(',', '', $raw), $m)) {
                    return (float) $m[0];
                }
            }
        }

        return null;
    }

    /**
     * Serialize any leftover (unmapped) non-empty scalar form fields into a
     * JSON suffix so no clinical detail is lost during the schema migration.
     */
    private function leftoverNotes(array $data, array $usedKeys): string
    {
        $extra = collect($data)
            ->except($usedKeys)
            ->filter(fn ($v) => (is_array($v) ? count($v) > 0 : true) && $v !== '' && $v !== null)
            ->toArray();

        return $extra ? ' | '.json_encode($extra) : '';
    }

    /**
     * Normalize the legacy echo_data form blob into echocardiograms columns.
     */
    private function mapEchoData(array $d): array
    {
        $used = [
            'lvef_percent', 'lvfs_percent', 'lv_mass_grams', 'tapse_mm',
            'ivs_mm', 'lvpw_mm', 'lvidd_mm', 'lvids_mm', 'aortic_root_mm',
            'la_diameter_mm', 'rv_size', 'pericardial_effusion',
            'valve_findings', 'wall_motion', 'notes', 'findings',
        ];

        $mappedKeyMap = [
            'lvef_percent' => ['lv_ef'],
            'lvfs_percent' => ['lv_fs'],
            'lv_mass_grams' => ['lv_mass'],
            'tapse_mm' => ['tapse'],
            'ivs_mm' => ['ivs_dia'],
            'lvpw_mm' => ['lvpw_dia'],
            'lvidd_mm' => ['lv_dia'],
            'lvids_mm' => ['lv_sys'],
            'aortic_root_mm' => ['aortic_root'],
            'la_diameter_mm' => ['la_ap', 'la_rl'],
            'rv_size' => ['rv_ap'],
        ];

        $row = [];
        foreach ($mappedKeyMap as $col => $keys) {
            $val = $this->num($d, $keys);
            if ($val !== null) {
                $row[$col] = $val;
            }
        }

        if (! empty($d['findings'])) {
            $row['valve_findings'] = $this->sanitizeText((string) $d['findings'], 800);
        }
        if (! empty($d['tricuspid_valve'])) {
            $row['wall_motion'] = $this->sanitizeText('Tricuspid valve: '.$d['tricuspid_valve'], 800);
        }

        $peri = $d['peri_ant'] ?? $d['peri_post'] ?? '';
        $row['pericardial_effusion'] = $peri !== '' && $peri !== null;

        $row['notes'] = $this->sanitizeText(trim(
            ($d['notes'] ?? '').$this->leftoverNotes($d, $used)
        ), 2000);

        return $row;
    }

    /**
     * Normalize the legacy tmt_data form blob into treadmill_tests columns.
     */
    private function mapTmtData(array $d): array
    {
        $used = ['protocol', 'max_stage', 'peak_heart_rate_bpm', 'peak_bp_systolic',
            'mets_achieved', 'exercise_duration_seconds', 'recovery_time_minutes',
            'st_segment_changes', 'arrhythmias', 'symptoms_during_test',
            'reason_for_termination', 'notes', 'conclusion'];

        $row = [];

        if (! empty($d['protocol'])) {
            $row['protocol'] = $this->sanitizeText((string) $d['protocol'], 50);
        }
        if (($stage = $this->num($d, ['peak_stage', 'max_stage'])) !== null) {
            $row['max_stage'] = $stage;
        }
        if (($hr = $this->num($d, ['peak_hr', 'peak_heart_rate_bpm'])) !== null) {
            $row['peak_heart_rate_bpm'] = (int) $hr;
        }
        if (($sbp = $this->num($d, ['peak_sbp', 'peak_bp_systolic'])) !== null) {
            $row['peak_bp_systolic'] = (int) $sbp;
        }
        if (($mets = $this->num($d, ['peak_mets', 'mets_achieved'])) !== null) {
            $row['mets_achieved'] = $mets;
        }
        if (($mins = $this->num($d, ['peak_minutes'])) !== null) {
            $row['exercise_duration_seconds'] = (int) ($mins * 60);
        }

        $stParts = array_filter([$d['st_shape'] ?? null, $d['st_displacement'] ?? null, $d['st_duration'] ?? null]);
        if ($stParts) {
            $row['st_segment_changes'] = $this->sanitizeText(implode(' | ', $stParts), 800);
        }
        if (! empty($d['arrhythmias'])) {
            $row['arrhythmias'] = $this->sanitizeText((string) $d['arrhythmias'], 800);
        }
        if (! empty($d['response_to_exercise'])) {
            $row['symptoms_during_test'] = $this->sanitizeText((string) $d['response_to_exercise'], 800);
        }
        if (! empty($d['termination_reason'])) {
            $row['reason_for_termination'] = $this->sanitizeText((string) $d['termination_reason'], 100);
        }

        $row['notes'] = $this->sanitizeText(trim(
            ($d['conclusion'] ?? '').$this->leftoverNotes($d, $used)
        ), 2000);

        return $row;
    }

    /**
     * Normalize the legacy doppler_data form blob into doppler_studies columns.
     */
    private function mapDopplerData(array $d): array
    {
        $used = ['ea_ratio', 'tissue_doppler_e', 'tissue_doppler_a',
            'mitral_gradient', 'aortic_gradient', 'tricuspid_gradient',
            'pulmonic_gradient', 'mitral_regurgitation', 'aortic_regurgitation',
            'tricuspid_regurgitation', 'valve_findings', 'notes', 'conclusion'];

        $row = [];

        if (($ea = $this->num($d, ['mv_ea_ratio', 'ea_ratio'])) !== null) {
            $row['ea_ratio'] = $ea;
        }
        if (($e = $this->num($d, ['td_septal_e', 'tissue_doppler_e'])) !== null) {
            $row['tissue_doppler_e'] = $e;
        }
        if (($a = $this->num($d, ['td_septal_a', 'tissue_doppler_a'])) !== null) {
            $row['tissue_doppler_a'] = $a;
        }
        if (($mg = $this->num($d, ['mv_mean_pressure', 'mv_peak_grad_e', 'mitral_gradient'])) !== null) {
            $row['mitral_gradient'] = $mg;
        }
        if (($ag = $this->num($d, ['av_mean_grad', 'av_press_grad_after', 'aortic_gradient'])) !== null) {
            $row['aortic_gradient'] = $ag;
        }
        if (($tg = $this->num($d, ['tv_peak_tr', 'tricuspid_gradient'])) !== null) {
            $row['tricuspid_gradient'] = $tg;
        }
        if (($pg = $this->num($d, ['pv_press_grad', 'pulmonic_gradient'])) !== null) {
            $row['pulmonic_gradient'] = $pg;
        }

        $notable = [];
        foreach (['mv_dectime', 'mv_ivrt', 'mv_regurg_frac', 'td_septal_ee', 'tv_e_wave', 'pv_accel_time', 'qp_qs'] as $k) {
            if (! empty($d[$k])) {
                $notable[] = "{$k}: {$d[$k]}";
            }
        }
        if ($notable) {
            $row['valve_findings'] = $this->sanitizeText(implode(' | ', $notable), 800);
        }

        $row['notes'] = $this->sanitizeText(trim(
            ($d['conclusion'] ?? '').$this->leftoverNotes($d, $used)
        ), 2000);

        return $row;
    }

    // 8. Transfer patient to a different doctor
    public function transfer(Request $request, Appointment $appointment)
    {
        $request->validate(['doctor_id' => 'required|exists:providers,id']);

        $appointment->update(['provider_id' => $request->doctor_id]);

        // Update the Google Calendar event to reflect the new doctor
        $this->calendar->updateEvent($appointment);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Transferred Appointment #'.$appointment->id.' to new Doctor.',
        ]);

        return back()->with('success', 'Patient successfully transferred!');
    }

    // 9. Doctor Schedule Plotter
    public function storeSchedule(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:providers,id',
            'day_of_week' => 'required|array',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        DoctorSchedule::create([
            'provider_id' => $validated['doctor_id'],
            'day_of_week' => $this->normalizeDays($validated['day_of_week']),
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);

        Cache::forget('doctor_list');
        Cache::forget('doctor_schedules_all');
        Cache::forget('doctor_schedules_patient');

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Plotted recurring schedule for Doctor ID: '.$request->doctor_id,
        ]);

        return redirect()->route('receptionist.dashboard')
            ->with('success', 'Doctor availability successfully plotted!');
    }

    // 10. Update an existing schedule
    public function updateSchedule(Request $request, DoctorSchedule $schedule)
    {
        $validated = $request->validate([
            'doctor_id' => 'sometimes|exists:providers,id',
            'day_of_week' => 'required|array',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $schedule->update([
            'provider_id' => $validated['doctor_id'] ?? $schedule->provider_id,
            'day_of_week' => $this->normalizeDays($validated['day_of_week']),
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);

        Cache::forget('doctor_list');
        Cache::forget('doctor_schedules_all');
        Cache::forget('doctor_schedules_patient');

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Updated recurring schedule for Doctor ID: '.$schedule->provider_id,
        ]);

        return redirect()->route('receptionist.dashboard')
            ->with('success', 'Schedule successfully updated!');
    }

    // 11. Delete an existing schedule
    public function deleteSchedule(DoctorSchedule $schedule)
    {
        $schedule->delete();

        Cache::forget('doctor_list');
        Cache::forget('doctor_schedules_all');
        Cache::forget('doctor_schedules_patient');

        return back()->with('success', 'Schedule successfully deleted!');
    }

    // 12. Auto-assign helper (returns the provider id)
    /**
     * Doctors (users with role `doctor`) who are scheduled for the given slot
     * and have no conflicting appointment in it. Slot times must fall inside
     * the schedule's [start, end) window — end_time is exclusive so a doctor
     * who works until 12:00 is never offered a 12:00 slot.
     */
    public static function providersAvailableAt($date, $time)
    {
        $dayInt = Carbon::parse($date)->dayOfWeekIso; // 1 = Monday .. 7 = Sunday

        return User::with('provider:id,user_id')
            ->where('role', 'doctor')
            ->whereHas('schedules', function ($q) use ($dayInt, $time) {
                $q->whereJsonContains('day_of_week', $dayInt)
                    ->whereTime('start_time', '<=', $time)
                    ->whereTime('end_time', '>', $time);
            })
            ->whereDoesntHave('doctorAppointments', function ($q) use ($date, $time) {
                $q->whereDate('scheduled_date', $date)
                    ->whereTime('scheduled_time', $time)
                    ->whereNotIn('status', ['Cancelled', 'No-Show']);
            })
            ->get();
    }

    private static function autoAssignDoctor($date, $time)
    {
        $availableDoctor = self::providersAvailableAt($date, $time)->first();

        if (! $availableDoctor || ! $availableDoctor->provider) {
            throw ValidationException::withMessages([
                'time' => 'No doctors are scheduled or available at this specific date and time.',
            ]);
        }

        return $availableDoctor->provider->id;
    }

    /**
     * Coerce day identifiers to ISO day numbers (1=Mon..7=Sun).
     * Accepts "Monday" or 1..7, matching the doctor_schedules jsonb column.
     */
    private static function normalizeDays(array $days): array
    {
        return collect($days)
            ->map(function ($day) {
                if (is_numeric($day)) {
                    return (int) $day;
                }

                return Carbon::parse((string) $day)->dayOfWeekIso;
            })
            ->unique()
            ->values()
            ->all();
    }

    // 13b. Patient submits a reschedule REQUEST (receptionist must approve)
    public function requestReschedule(Request $request, Appointment $appointment)
    {
        if (! Auth::user()->patient || $appointment->patient_id !== Auth::user()->patient->id) {
            abort(403);
        }

        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required',
        ]);

        $appointment->update([
            'status' => 'Reschedule Requested',
            'reschedule_requested_date' => $request->date,
            'reschedule_requested_time' => $request->time,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Patient requested reschedule for appointment #'.$appointment->id.' to '.$request->date.' at '.$request->time,
        ]);

        $appointment->refresh();
        event(new RescheduleRequested($appointment));
        event(new AppointmentStatusChanged($appointment));

        return back()->with('success', 'Reschedule request submitted. The receptionist will review and confirm availability.');
    }

    // 13c. Receptionist approves a reschedule request
    public function approveReschedule(Appointment $appointment)
    {
        if (! $appointment->reschedule_requested_date) {
            return back()->withErrors(['error' => 'No pending reschedule request found.']);
        }

        // H6 FIX: verify the doctor is scheduled to work on the requested day/time
        $dayInt = Carbon::parse($appointment->reschedule_requested_date)->dayOfWeekIso;
        $dayLabel = Carbon::parse($appointment->reschedule_requested_date)->format('l');
        $doctorScheduled = DoctorSchedule::where('provider_id', $appointment->provider_id)
            ->whereJsonContains('day_of_week', $dayInt)
            ->whereTime('start_time', '<=', $appointment->reschedule_requested_time)
            ->whereTime('end_time', '>=', $appointment->reschedule_requested_time)
            ->exists();

        if (! $doctorScheduled) {
            return back()->withErrors([
                'time' => 'The doctor is not scheduled to work on '.$dayLabel
                    .' at '.Carbon::parse($appointment->reschedule_requested_time)->format('h:i A')
                    .'. Please deny the request and ask the patient to select a valid slot.',
            ]);
        }

        // Verify the requested slot has no appointment conflict
        $conflict = Appointment::where('provider_id', $appointment->provider_id)
            ->whereDate('scheduled_date', $appointment->reschedule_requested_date)
            ->whereTime('scheduled_time', $appointment->reschedule_requested_time)
            ->where('id', '!=', $appointment->id)
            ->whereNotIn('status', ['Cancelled', 'No-Show', 'Reschedule Requested'])
            ->exists();

        if ($conflict) {
            return back()->withErrors([
                'time' => 'This slot is already taken. Please deny the request and ask the patient to choose another time.',
            ]);
        }

        $appointment->update([
            'scheduled_date' => $appointment->reschedule_requested_date,
            'scheduled_time' => $appointment->reschedule_requested_time,
            'status' => 'Scheduled',
            'reschedule_requested_date' => null,
            'reschedule_requested_time' => null,
        ]);

        $this->calendar->updateEvent($appointment);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Approved reschedule for appointment #'.$appointment->id,
        ]);

        $appointment->refresh();
        event(new AppointmentStatusChanged($appointment));

        return back()->with('success', 'Reschedule approved — appointment updated.');
    }

    // 13d. Receptionist edits and approves a reschedule request directly
    public function editReschedule(Request $request, Appointment $appointment)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required',
        ]);

        // Normalize any accepted "HH:MM" / "HH:MM:SS" time to "HH:MM:SS"
        // before it touches the `time` column (rejects stray "h:i A" strings
        // that would otherwise crash the query — no 500s for bad input).
        $sentTime = trim($request->time);
        if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $sentTime)) {
            throw ValidationException::withMessages([
                'time' => 'Please provide a valid time (HH:MM).',
            ]);
        }
        $requestTime = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $sentTime)
            ? $sentTime.':00'
            : $sentTime;

        $oldDate = $appointment->scheduled_date instanceof Carbon
            ? $appointment->scheduled_date->format('Y-m-d')
            : $appointment->scheduled_date;
        $oldTime = $appointment->scheduled_time;

        if ($oldDate === $request->date && $oldTime === $requestTime) {
            return back()->with('success', 'Schedule remained the same.');
        }

        $conflict = Appointment::where('provider_id', $appointment->provider_id)
            ->whereDate('scheduled_date', $request->date)
            ->whereTime('scheduled_time', $requestTime)
            ->whereNotIn('status', ['Cancelled', 'No-Show'])
            ->where('id', '!=', $appointment->id)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'time' => 'The doctor already has an appointment at this time.',
            ]);
        }

        $appointment->update([
            'scheduled_date' => $request->date,
            'scheduled_time' => $requestTime,
            'status' => 'Scheduled',
            'reschedule_requested_date' => null,
            'reschedule_requested_time' => null,
        ]);

        $this->calendar->updateEvent($appointment);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Receptionist rescheduled appointment #'.$appointment->id
                        .' for Patient ID: '.$appointment->patient_id
                        .' from '.$oldDate.' '.$oldTime
                        .' to '.$request->date.' '.$request->time,
        ]);

        $appointment->refresh();
        $appointment->load('provider.user:id,name');
        event(new AppointmentRescheduled($appointment));
        event(new AppointmentStatusChanged($appointment));
        $this->broadcastQueueUpdated($appointment->provider_id);

        return back()->with('success', 'Appointment rescheduled successfully!');
    }

    // 13e. Receptionist denies a reschedule request
    public function denyReschedule(Appointment $appointment)
    {
        $appointment->update([
            'status' => 'Scheduled',
            'reschedule_requested_date' => null,
            'reschedule_requested_time' => null,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Denied reschedule request for appointment #'.$appointment->id.' — original schedule kept.',
        ]);

        $appointment->refresh();
        event(new AppointmentStatusChanged($appointment));

        return back()->with('success', 'Reschedule request denied. The original schedule is kept.');
    }

    // 14. Doctor books a follow-up appointment after consultation
    public function storeFollowUp(Request $request, Appointment $appointment)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today', // H5 FIX: consistent with patient booking
            'time' => 'required',
        ]);

        // C3 FIX: conflict check — same as storeManual / storeReturning
        $conflict = Appointment::where('provider_id', $appointment->provider_id)
            ->whereDate('scheduled_date', $request->date)
            ->whereTime('scheduled_time', $request->time)
            ->whereNotIn('status', ['Cancelled', 'No-Show'])
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'time' => 'The doctor already has an appointment at this time. Please choose a different slot.',
            ]);
        }

        $followUp = Appointment::create([
            'patient_id' => $appointment->patient_id,
            'provider_id' => $appointment->provider_id,
            'scheduled_date' => $request->date,
            'scheduled_time' => $request->time,
            'duration_minutes' => 30,
            'service_type' => 'Follow-up Consultation',
            'status' => 'Scheduled',
            'notes' => 'Follow-up from consultation on '.
                                  Carbon::parse($appointment->scheduled_date)->format('M d, Y'),
        ]);

        $event = $this->calendar->createEvent($followUp);
        if ($event) {
            $followUp->update([
                'google_event_id' => $event['id'],
                'google_event_url' => $event['url'],
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Doctor scheduled follow-up for Patient #'.$appointment->patient_id.' from Appointment #'.$appointment->id,
        ]);

        Cache::forget('receptionist_stats');
        Cache::forget('admin_dashboard_stats');
        $this->broadcastQueueUpdated($appointment->provider_id);

        return back()->with('success', 'Follow-up appointment scheduled for '.$request->date.'.');
    }

    // 15. Patient reschedules their appointment (KEPT for backward compat — direct reschedule)
    public function reschedule(Request $request, Appointment $appointment)
    {
        if (! Auth::user()->patient || $appointment->patient_id !== Auth::user()->patient->id) {
            abort(403);
        }

        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required',
        ]);

        if ($appointment->scheduled_date->toDateString() === $request->date && $appointment->scheduled_time === $request->time) {
            return back()->with('success', 'Schedule remained the same.');
        }

        // M1 FIX: delegate to autoAssignDoctor instead of duplicating logic
        $providerId = self::autoAssignDoctor($request->date, $request->time);

        $appointment->update([
            'scheduled_date' => $request->date,
            'scheduled_time' => $request->time,
            'provider_id' => $providerId,
            'status' => 'Scheduled',
        ]);

        $this->calendar->updateEvent($appointment);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Rescheduled appointment #'.$appointment->id.' to '.$request->date,
        ]);

        $appointment->refresh();
        event(new AppointmentStatusChanged($appointment));
        $this->broadcastQueueUpdated($appointment->provider_id);

        return back()->with('success', 'Appointment successfully rescheduled!');
    }
}
