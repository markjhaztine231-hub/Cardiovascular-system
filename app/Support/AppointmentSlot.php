<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * The clinic books appointments in two half-day windows instead of exact
 * times. Every booking still stores an exact `scheduled_time` so the queue,
 * calendar sync, and double-booking checks keep working; the slot is only the
 * window the patient/receptionist chooses.
 */
class AppointmentSlot
{
    public const MORNING = 'morning';

    public const AFTERNOON = 'afternoon';

    /**
     * Minutes between the auto-assigned times inside a window.
     */
    public const STEP_MINUTES = 5;

    /**
     * @return array<string, array{label: string, start: string, end: string}>
     */
    public static function all(): array
    {
        return [
            self::MORNING => [
                'label' => 'Morning (6:00 AM - 12:00 PM)',
                'start' => '06:00:00',
                'end' => '12:00:00',
            ],
            self::AFTERNOON => [
                'label' => 'Afternoon (1:00 PM - 6:00 PM)',
                'start' => '13:00:00',
                'end' => '18:00:00',
            ],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::all())
            ->map(fn (array $slot, string $value) => [
                'value' => $value,
                'label' => $slot['label'],
            ])
            ->values()
            ->all();
    }

    public static function isValid(mixed $slot): bool
    {
        return is_string($slot) && array_key_exists($slot, self::all());
    }

    /**
     * Map an exact stored time to the window it belongs to.
     */
    public static function fromTime(?string $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        $minutes = self::minutes($time);

        // Legacy rows can still sit in the 12:00-13:00 lunch gap. Treat them
        // as morning for display so the UI never leaks a raw exact time.
        if ($minutes >= self::minutes('06:00') && $minutes < self::minutes('13:00')) {
            return self::MORNING;
        }

        if ($minutes >= self::minutes('13:00') && $minutes <= self::minutes('18:00')) {
            return self::AFTERNOON;
        }

        return null;
    }

    public static function labelForTime(?string $time): ?string
    {
        $slot = self::fromTime($time);

        return $slot !== null ? self::all()[$slot]['label'] : null;
    }

    /**
     * Human label for a schedule/shift range. A range that covers both
     * windows (for example the legacy 08:00-17:00 rows) is labelled as both.
     */
    public static function labelForRange(string $start, string $end): string
    {
        $startMinutes = self::minutes($start);
        $endMinutes = self::minutes($end);

        $coversMorning = $startMinutes < self::minutes('12:00')
            && $endMinutes > self::minutes('06:00');
        $coversAfternoon = $startMinutes < self::minutes('18:00')
            && $endMinutes > self::minutes('13:00');

        if ($coversMorning && $coversAfternoon) {
            return 'Morning & Afternoon (6:00 AM - 6:00 PM)';
        }

        if ($coversMorning) {
            return self::all()[self::MORNING]['label'];
        }

        if ($coversAfternoon) {
            return self::all()[self::AFTERNOON]['label'];
        }

        return $start.' - '.$end;
    }

    /**
     * Exact candidate times for a window, optionally intersected with a
     * doctor schedule's [start, end) range. End is exclusive, so a doctor who
     * works until 12:00 is never offered the 12:00 boundary.
     *
     * @return Collection<int, string>
     */
    public static function times(string $slot, ?string $start = null, ?string $end = null): Collection
    {
        if (! self::isValid($slot)) {
            return collect();
        }

        $window = self::all()[$slot];
        $from = max(self::minutes($window['start']), $start !== null ? self::minutes($start) : 0);
        $to = min(self::minutes($window['end']), $end !== null ? self::minutes($end) : 24 * 60);

        $times = collect();

        for ($minutes = $from; $minutes < $to; $minutes += self::STEP_MINUTES) {
            $times->push(sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60));
        }

        return $times;
    }

    public static function isPast(string $date, string $time): bool
    {
        if ($date !== Carbon::now()->toDateString()) {
            return false;
        }

        return Carbon::parse($date.' '.$time)->lte(Carbon::now());
    }

    private static function minutes(string $time): int
    {
        [$hour, $minute] = array_pad(array_map('intval', explode(':', $time)), 2, 0);

        return ($hour * 60) + $minute;
    }
}
