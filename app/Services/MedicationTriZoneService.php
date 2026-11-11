<?php

namespace App\Services;

use App\Models\MedicationLog;
use App\Models\Prescription;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Implements the Pharmacokinetic Tri-Zone adherence model ("50% Halfway Rule"):
 *
 *   GREEN  · [sched − 1h, sched + 1h]        → TAKEN / ON_TIME
 *   AMBER  · ( sched + 1h, sched + half ]    → TAKEN / LATE
 *   RED    · ( sched + half, … ]             → MISSED / EXPIRED_WINDOW (safety lockout)
 *
 * where `half` is the midway point to the next scheduled dose (e.g. 4h for a
 * q8h / TID regimen). A dynamic minimum cooldown (default 5h) is enforced
 * between recorded intakes to discourage double dosing.
 */
class MedicationTriZoneService
{
    public const MINIMUM_GAP_HOURS = 5.0;

    public const STATUS_TAKEN = 'TAKEN';

    public const STATUS_MISSED = 'MISSED';

    public const FLAG_ON_TIME = 'ON_TIME';

    public const FLAG_LATE = 'LATE';

    public const FLAG_EXPIRED_WINDOW = 'EXPIRED_WINDOW';

    /**
     * Parses the textual frequency into expected doses per day. Mirrors the
     * legacy mapping used by MedicationController/MonthlyReportService.
     */
    public function dailyDoseCount(?string $frequency): int
    {
        $freq = strtolower($frequency ?? '1x a day');

        return match (true) {
            str_contains($freq, 'as needed') || str_contains($freq, 'prn') => 0,
            str_starts_with($freq, '1x') || str_contains($freq, 'once') => 1,
            str_starts_with($freq, '2x') || str_contains($freq, 'twice') => 2,
            str_starts_with($freq, '3x') || str_contains($freq, 'thrice') => 3,
            str_starts_with($freq, '4x') => 4,
            str_contains($freq, '4 hours') => 4,
            str_contains($freq, '6 hours') => 3,
            str_contains($freq, '8 hours') => 2,
            default => 1,
        };
    }

    /**
     * Seed dosing times for a frequency on a normal day. 1× → 08:00, 2× →
     * 08:00/20:00, and n× (n ≥ 3) evenly spaced across 24h starting at
     * 00:00 (so a TID / q8h regimen lands on 00:00 · 08:00 · 16:00).
     *
     * @return string[] list of "H:i" slots
     */
    public function scheduledTimes(?string $frequency): array
    {
        $count = $this->dailyDoseCount($frequency);
        if ($count === 0) {
            return [];
        }

        if ($count === 1) {
            return ['08:00'];
        }
        if ($count === 2) {
            return ['08:00', '20:00'];
        }

        $times = [];
        $stepMinutes = (24 * 60) / $count;
        for ($i = 0; $i < $count; $i++) {
            $times[] = Carbon::createFromTime(0, 0)
                ->addMinutes((int) round($i * $stepMinutes))
                ->format('H:i');
        }

        return $times;
    }

    public function intervalHours(int $dailyDoseCount): float
    {
        return 24 / max(1, $dailyDoseCount);
    }

    /** Minutes from the scheduled time to the "halfway" red threshold. */
    public function halfwayMinutes(int $dailyDoseCount): int
    {
        return (int) round(($this->intervalHours($dailyDoseCount) * 60) / 2);
    }

    /**
     * Classifies the current moment relative to a scheduled dose.
     * Returns GREEN | AMBER | RED | AHEAD.
     */
    public function zone(Carbon $now, Carbon $scheduledAt, int $dailyDoseCount): string
    {
        $greenStart = $scheduledAt->copy()->subHour();
        $greenEnd = $scheduledAt->copy()->addHour();
        $amberEnd = $scheduledAt->copy()->addMinutes($this->halfwayMinutes($dailyDoseCount));

        if ($now->lt($greenStart)) {
            return 'AHEAD';
        }
        if ($now->lte($greenEnd)) {
            return 'GREEN';
        }
        if ($now->lte($amberEnd)) {
            return 'AMBER';
        }

        return 'RED';
    }

    /**
     * The daily dose slots for a prescription, each classified against `$now`.
     *
     * On the day a prescription was written and started (the normal flow, where
     * `start_date` is the same calendar day as `created_at`), any slot whose
     * intake window had already closed before the prescription existed is
     * discarded. Without this a prescription entered at 15:00 would instantly
     * show the 08:00 dose as MISSED and lock the patient out of their very
     * first dose. Pre-dated or back-dated courses are unaffected because their
     * `start_date` differs from the creation day.
     *
     * @return array<int, array{time: string, scheduled_at: Carbon, zone: string}>
     */
    public function slotsFor(Prescription $prescription, CarbonInterface $date, CarbonInterface $now): array
    {
        $count = $this->dailyDoseCount($prescription->frequency);
        if ($count === 0) {
            return [];
        }

        $date = Carbon::instance($date);
        $now = Carbon::instance($now);
        $activation = $this->activationMoment($prescription, $date);

        $slots = [];
        foreach ($this->scheduledTimes($prescription->frequency) as $time) {
            $scheduledAt = $date->copy()->setTimeFromTimeString($time);

            // The patient never had the medication while this window was open.
            if ($activation && $this->zone($activation, $scheduledAt, $count) === 'RED') {
                continue;
            }

            $slots[] = [
                'time' => $time,
                'scheduled_at' => $scheduledAt,
                'zone' => $this->zone($now, $scheduledAt, $count),
            ];
        }

        return $slots;
    }

    /**
     * The moment a prescription became actionable on `$date`, or null when the
     * full-day schedule applies. Only the calendar day on which a prescription
     * was both written and started is time-gated.
     */
    private function activationMoment(Prescription $prescription, CarbonInterface $date): ?Carbon
    {
        $createdAt = $prescription->created_at;

        if (! $createdAt) {
            return null;
        }

        $startDay = $prescription->start_date?->copy()->startOfDay()
            ?? $createdAt->copy()->startOfDay();

        // Back-dated / pre-created courses use an explicit clinical start date,
        // so the creation clock must not shrink their first day.
        if (! $startDay->isSameDay($createdAt)) {
            return null;
        }

        return $date->isSameDay($startDay) ? Carbon::instance($createdAt) : null;
    }

    /**
     * Number of expected doses for a prescription on a given day, excluding
     * slots that were already closed when the prescription was written.
     */
    public function expectedDosesOn(Prescription $prescription, CarbonInterface $date): int
    {
        return count($this->slotsFor($prescription, $date->copy(), $date->copy()->endOfDay()));
    }

    /**
     * Whether a MISSED row is an artefact of a start-day sweep: the slot had
     * already closed before the prescription was written, so the patient never
     * had an opportunity to take it. Legacy rows created before the start-day
     * filter existed must not be counted against adherence.
     */
    public function isPreActivationMissed(Prescription $prescription, CarbonInterface $date, ?string $scheduledTime): bool
    {
        if ($scheduledTime === null) {
            return false;
        }

        $activation = $this->activationMoment($prescription, $date);
        $count = $this->dailyDoseCount($prescription->frequency);

        if (! $activation || $count === 0) {
            return false;
        }

        $slotAt = Carbon::instance($date)->startOfDay()->setTimeFromTimeString($scheduledTime);

        return $this->zone($activation, $slotAt, $count) === 'RED';
    }

    /**
     * For a prescription and calendar day, writes MISSED / EXPIRED_WINDOW rows
     * for any scheduled slot whose actionable window has already closed without
     * being recorded. Idempotent (relies on the per-dose unique constraint).
     */
    public function syncMissedSlots(Prescription $prescription, CarbonInterface $date, CarbonInterface $now): void
    {
        $count = $this->dailyDoseCount($prescription->frequency);
        if ($count === 0) {
            return;
        }

        foreach ($this->slotsFor($prescription, $date, $now) as $slot) {
            if ($slot['zone'] !== 'RED') {
                continue;
            }

            $recorded = MedicationLog::where('prescription_id', $prescription->id)
                ->whereDate('log_date', $date->toDateString())
                ->where('scheduled_time', $slot['time'])
                ->exists();

            if ($recorded) {
                continue;
            }

            MedicationLog::create([
                'prescription_id' => $prescription->id,
                'log_date' => $date->toDateString(),
                'scheduled_time' => $slot['time'],
                'status' => self::STATUS_MISSED,
                'adherence_flag' => self::FLAG_EXPIRED_WINDOW,
                'taken_at' => null,
            ]);
        }
    }

    /**
     * Minimum-gap cooldown between recorded intakes. Enforced before any new
     * TAKEN write so the patient can never double-dose within the safety gap.
     *
     * @return array{active: bool, last_taken_at: Carbon|null, next_available_at: Carbon|null}
     */
    public function cooldown(int $prescriptionId, Carbon $now): array
    {
        $lastTaken = MedicationLog::where('prescription_id', $prescriptionId)
            ->where('status', self::STATUS_TAKEN)
            ->orderByDesc('taken_at')
            ->first();

        if (! $lastTaken?->taken_at) {
            return ['active' => false, 'last_taken_at' => null, 'next_available_at' => null];
        }

        $lastTakenAt = $lastTaken->taken_at;
        $nextAvailableAt = $lastTakenAt->copy()->addMinutes((int) (self::MINIMUM_GAP_HOURS * 60));

        return [
            'active' => $now->lte($nextAvailableAt),
            'last_taken_at' => $lastTakenAt,
            'next_available_at' => $nextAvailableAt,
        ];
    }
}
