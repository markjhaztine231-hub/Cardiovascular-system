<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Services\MedicationTriZoneService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Properties\TextProperty;

class PatientCalendarController extends Controller
{
    public function __construct(
        private readonly MedicationTriZoneService $triZone,
    ) {}

    /** Session-authenticated download of the patient's own calendar file. */
    public function download(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'patient' || ! $user->patient) {
            abort(403);
        }

        return $this->calendarResponse($user->patient, inline: false);
    }

    /**
     * Signed, session-free feed so calendar apps can subscribe and refresh.
     * The route's `signed` middleware has already verified the signature.
     */
    public function feed(User $user)
    {
        if ($user->role !== 'patient' || ! $user->patient) {
            abort(404);
        }

        return $this->calendarResponse($user->patient, inline: true);
    }

    private function calendarResponse(Patient $patient, bool $inline)
    {
        $ics = $this->build($patient);
        $filename = 'cardiocare-calendar.ics';
        $disposition = ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"; filename*=UTF-8\'\''.$filename;

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => $disposition,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Times are emitted as floating local time (no TZID / trailing Z) because
     * the clinic stores wall-clock appointment and dosing times; calendar
     * clients then render them in the device's own timezone, which is what a
     * patient expects. `withoutTimezone()` is what keeps the wall-clock intact.
     */
    private function build(Patient $patient): string
    {
        $calendar = Calendar::create('CardioCare - My Health Calendar')
            ->productIdentifier('-//Cardiovascular Care Center//CardioCare Patient Calendar//EN')
            ->refreshInterval(60)
            ->withoutTimezone();

        $this->addAppointments($calendar, $patient);
        $this->addMedications($calendar, $patient);

        $calendar->appendProperty(TextProperty::create('CALSCALE', 'GREGORIAN'));
        $calendar->appendProperty(TextProperty::create('METHOD', 'PUBLISH'));

        return $calendar->get();
    }

    private function addAppointments(Calendar $calendar, Patient $patient): void
    {
        $appointments = $patient->appointments()
            ->with('provider.user:id,name')
            ->whereNotIn('status', ['Cancelled'])
            ->whereDate('scheduled_date', '>=', now()->subDays(30))
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        $events = [];

        foreach ($appointments as $appointment) {
            $summary = $appointment->service_type ?: 'Appointment';
            $doctor = $appointment->provider?->user?->name;
            if ($doctor) {
                $summary .= ' with '.$doctor;
            }

            $start = $this->appointmentStart($appointment);

            $event = Event::create($summary)
                ->uniqueIdentifier('appt-'.$appointment->id.'@cardiocare')
                ->startsAt($start)
                ->endsAt($start->copy()->addMinutes($appointment->duration_minutes ?: 30))
                ->address('Cardiovascular Care Center')
                ->description('Status: '.$appointment->status
                    .'. Please arrive 15 minutes early and bring a valid ID.');
            $event->appendProperty(TextProperty::create('CATEGORIES', 'Appointment'));

            $events[] = $event;
        }

        $calendar->event($events);
    }

    private function appointmentStart(Appointment $appointment): Carbon
    {
        $date = $appointment->scheduled_date?->toDateString() ?? now()->toDateString();
        $time = $appointment->scheduled_time ?: '09:00:00';

        return Carbon::parse($date.' '.$time);
    }

    private function addMedications(Calendar $calendar, Patient $patient): void
    {
        $prescriptions = $patient->prescriptions()
            ->where('status', 'active')
            ->with('encounter.appointment:id,scheduled_date')
            ->get();

        $events = [];

        foreach ($prescriptions as $prescription) {
            $startDate = $this->prescriptionStart($prescription);
            $times = $this->triZone->scheduledTimes($prescription->frequency);

            if (empty($times)) {
                $event = Event::create('As needed: '.$this->medicationLabel($prescription))
                    ->uniqueIdentifier('rx-'.$prescription->id.'-prn@cardiocare')
                    ->fullDay()
                    ->startsAt($startDate)
                    ->endsAt($startDate->copy()->addDay())
                    ->description(trim('Take only when needed. '.$prescription->instructions));
                $event->appendProperty(TextProperty::create('CATEGORIES', 'Medication'));

                $events[] = $event;

                continue;
            }

            $days = $this->prescriptionDays($prescription, $startDate);

            foreach ($times as $time) {
                $start = $startDate->copy()->setTimeFromTimeString($time);

                $event = Event::create('Take '.$this->medicationLabel($prescription))
                    ->uniqueIdentifier('rx-'.$prescription->id.'-'.str_replace(':', '', $time).'@cardiocare')
                    ->startsAt($start)
                    ->endsAt($start->copy()->addMinutes(5))
                    ->rruleAsString('FREQ=DAILY;COUNT='.$days)
                    ->description(trim($prescription->frequency.' '.$prescription->route.' '.$prescription->instructions));
                $event->appendProperty(TextProperty::create('CATEGORIES', 'Medication'));

                $events[] = $event;
            }
        }

        $calendar->event($events);
    }

    private function prescriptionStart(Prescription $prescription): Carbon
    {
        $date = $prescription->start_date
            ?? $prescription->encounter?->appointment?->scheduled_date
            ?? $prescription->created_at
            ?? now();

        return Carbon::parse($date)->startOfDay();
    }

    private function prescriptionDays(Prescription $prescription, Carbon $startDate): int
    {
        $days = null;

        if ($prescription->duration_days) {
            $days = (int) $prescription->duration_days;
        }

        if ($prescription->end_date) {
            $days = $startDate->diffInDays(Carbon::parse($prescription->end_date)) + 1;
        }

        return max(1, min($days ?: 30, 180));
    }

    private function medicationLabel(Prescription $prescription): string
    {
        return trim($prescription->medication_name.' '.$prescription->dosage);
    }
}
