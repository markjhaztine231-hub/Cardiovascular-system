<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Minimal RFC 5545 (iCalendar) writer.
 *
 * Times are emitted as floating local time (no TZID / trailing Z) because the
 * clinic stores wall-clock appointment and dosing times; calendar clients then
 * render them in the device's own timezone, which is what a patient expects.
 */
class IcsBuilder
{
    /** @var array<int, array<string, mixed>> */
    private array $events = [];

    public function __construct(
        private string $calendarName = 'CardioCare',
    ) {}

    /**
     * @param  array{
     *     uid: string,
     *     start: CarbonInterface,
     *     summary: string,
     *     durationMinutes?: int,
     *     allDay?: bool,
     *     rrule?: string,
     *     description?: string,
     *     location?: string,
     *     categories?: string,
     *     dtstamp?: CarbonInterface
     * }  $event
     */
    public function addEvent(array $event): self
    {
        $this->events[] = $event;

        return $this;
    }

    public function toString(): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Cardiovascular Care Center//CardioCare Patient Calendar//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->escape($this->calendarName),
        ];

        foreach ($this->events as $event) {
            $lines = array_merge($lines, $this->eventLines($event));
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([$this, 'fold'], $lines))."\r\n";
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<int, string>
     */
    private function eventLines(array $event): array
    {
        $lines = ['BEGIN:VEVENT', 'UID:'.$this->escape((string) $event['uid'])];

        $dtstamp = $event['dtstamp'] ?? now();
        $lines[] = 'DTSTAMP:'.$dtstamp->copy()->utc()->format('Ymd\THis\Z');

        /** @var CarbonInterface $start */
        $start = $event['start'];

        if (! empty($event['allDay'])) {
            $lines[] = 'DTSTART;VALUE=DATE:'.$start->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:'.$start->copy()->addDay()->format('Ymd');
        } else {
            $lines[] = 'DTSTART:'.$start->format('Ymd\THis');
            $minutes = max(1, (int) ($event['durationMinutes'] ?? 30));
            $lines[] = 'DURATION:PT'.$minutes.'M';
        }

        if (! empty($event['rrule'])) {
            $lines[] = 'RRULE:'.$event['rrule'];
        }

        $lines[] = 'SUMMARY:'.$this->escape((string) ($event['summary'] ?? ''));

        if (! empty($event['description'])) {
            $lines[] = 'DESCRIPTION:'.$this->escape((string) $event['description']);
        }

        if (! empty($event['location'])) {
            $lines[] = 'LOCATION:'.$this->escape((string) $event['location']);
        }

        if (! empty($event['categories'])) {
            $lines[] = 'CATEGORIES:'.$this->escape((string) $event['categories']);
        }

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    private function escape(string $value): string
    {
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace(["\r\n", "\r", "\n"], '\\n', $value);
        $value = str_replace(',', '\\,', $value);
        $value = str_replace(';', '\\;', $value);

        return $value;
    }

    /** Fold lines longer than 75 octets with CRLF + a single leading space. */
    private function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $folded = '';
        foreach (str_split($line, 73) as $index => $chunk) {
            $folded .= ($index === 0 ? '' : "\r\n ").$chunk;
        }

        return $folded;
    }
}
