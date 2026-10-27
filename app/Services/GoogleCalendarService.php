<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Spatie\GoogleCalendar\Event;

class GoogleCalendarService
{
    // -------------------------------------------------------------------------
    // PUBLIC API
    // -------------------------------------------------------------------------

    /**
     * Create a Google Calendar event for an appointment.
     * Returns ['id' => ..., 'url' => ...] on success, null on failure.
     */
    public function createEvent(Appointment $appointment): ?array
    {
        if (! $this->isConfigured() || ! $appointment->scheduled_time) {
            return null;
        }

        try {
            $appointment->loadMissing(['patient.user:id,name', 'provider.user:id,name']);

            $event = new Event;
            $event->name = $this->buildTitle($appointment);
            $event->description = $this->buildDescription($appointment);
            $event->startDateTime = $this->buildStart($appointment);
            $event->endDateTime = $this->buildEnd($appointment);

            $saved = $event->save();

            return [
                'id' => $saved->id,
                'url' => $saved->htmlLink,
            ];
        } catch (\Exception $e) {
            Log::error('[GoogleCalendar] createEvent failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Update an existing Google Calendar event (e.g. after reschedule or transfer).
     */
    public function updateEvent(Appointment $appointment): void
    {
        if (! $this->isConfigured() || ! $appointment->google_event_id) {
            return;
        }

        try {
            $appointment->loadMissing(['patient.user:id,name', 'provider.user:id,name']);

            $event = Event::find($appointment->google_event_id);
            if (! $event) {
                return;
            }

            $event->name = $this->buildTitle($appointment);
            $event->description = $this->buildDescription($appointment);
            $event->startDateTime = $this->buildStart($appointment);
            $event->endDateTime = $this->buildEnd($appointment);

            $event->save();
        } catch (\Exception $e) {
            Log::error('[GoogleCalendar] updateEvent failed: '.$e->getMessage());
        }
    }

    /**
     * Update the event title/description to reflect a new status (Cancelled, No-Show, etc.).
     */
    public function updateStatus(Appointment $appointment): void
    {
        if (! $this->isConfigured() || ! $appointment->google_event_id) {
            return;
        }

        try {
            $appointment->loadMissing(['patient.user:id,name', 'provider.user:id,name']);

            $event = Event::find($appointment->google_event_id);
            if (! $event) {
                return;
            }

            $prefix = in_array($appointment->status, ['Cancelled', 'No-Show', 'Completed'])
                ? '['.strtoupper($appointment->status).'] '
                : '';
            $event->name = $prefix.$this->buildTitle($appointment);
            $event->description = $this->buildDescription($appointment);

            $event->save();
        } catch (\Exception $e) {
            Log::error('[GoogleCalendar] updateStatus failed: '.$e->getMessage());
        }
    }

    /**
     * Delete a Google Calendar event entirely.
     */
    public function deleteEvent(string $eventId): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        try {
            $event = Event::find($eventId);
            $event?->delete();
        } catch (\Exception $e) {
            Log::error('[GoogleCalendar] deleteEvent failed: '.$e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    private function isConfigured(): bool
    {
        return ! empty(config('google-calendar.calendar_id'));
    }

    private function buildTitle(Appointment $appointment): string
    {
        $patient = $appointment->patient?->user?->name ?? 'Patient';
        $doctor = $appointment->provider?->user?->name ?? 'TBD';
        $service = $appointment->service_type ?? 'Consultation';

        return "{$service} — {$patient} with {$doctor}";
    }

    private function buildDescription(Appointment $appointment): string
    {
        $lines = [
            '🏥 CardioCare Cardiovascular Clinic',
            '',
            'Patient : '.($appointment->patient?->user?->name ?? 'N/A'),
            'Doctor  : '.($appointment->provider?->user?->name ?? 'TBD'),
            'Service : '.($appointment->service_type ?? 'Consultation'),
            'Status  : '.($appointment->status ?? 'Scheduled'),
        ];

        if ($appointment->notes) {
            $lines[] = '';
            $lines[] = 'Notes: '.$appointment->notes;
        }

        return implode("\n", $lines);
    }

    private function buildStart(Appointment $appointment): Carbon
    {
        $date = Carbon::parse($appointment->scheduled_date)->format('Y-m-d');
        $time = Carbon::parse($appointment->scheduled_time)->format('H:i:s');

        return Carbon::parse("{$date} {$time}", config('app.timezone'));
    }

    private function buildEnd(Appointment $appointment): Carbon
    {
        $minutes = $appointment->duration_minutes ?? 30;

        return $this->buildStart($appointment)->addMinutes($minutes);
    }
}
