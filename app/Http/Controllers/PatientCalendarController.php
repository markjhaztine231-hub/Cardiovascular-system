<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Services\MedicationTriZoneService;
use App\Support\IcsBuilder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="cardiocare-calendar.ics"',
            'Cache-Control' => 'no-store, max-age=0',
        ]);
    }

    private function build(Patient $patient): string
    {
        $builder = new IcsBuilder('CardioCare - My Health Calendar');

        $this->addAppointments($builder, $patient);
        $this->addMedications($builder, $patient);

        return $builder->toString();
    }

    private function addAppointments(IcsBuilder $builder, Patient $patient): void
    {
        $appointments = $patient->appointments()
            ->with('provider.user:id,name')
            ->whereNotIn('status', ['Cancelled'])
            ->whereDate('scheduled_date', '>=', now()->subDays(30))
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        foreach ($appointments as $appointment) {
            $summary = $appointment->service_type ?: 'Appointment';
            $doctor = $appointment->provider?->user?->name;
            if ($doctor) {
                $summary .= ' with '.$doctor;
            }

            $builder->addEvent([
                'uid' => 'appt-'.$appointment->id.'@cardiocare',
                'start' => $this->appointmentStart($appointment),
                'durationMinutes' => $appointment->duration_minutes ?: 30,
                'summary' => $summary,
                'description' => 'Status: '.$appointment->status
                    .'. Please arrive 15 minutes early and bring a valid ID.',
                'location' => 'Cardiovascular Care Center',
                'categories' => 'Appointment',
            ]);
        }
    }

    private function appointmentStart(Appointment $appointment): Carbon
    {
        $date = $appointment->scheduled_date?->toDateString() ?? now()->toDateString();
        $time = $appointment->scheduled_time ?: '09:00:00';

        return Carbon::parse($date.' '.$time);
    }

    private function addMedications(IcsBuilder $builder, Patient $patient): void
    {
        $prescriptions = $patient->prescriptions()
            ->where('status', 'active')
            ->with('encounter.appointment:id,scheduled_date')
            ->get();

        foreach ($prescriptions as $prescription) {
            $startDate = $this->prescriptionStart($prescription);
            $times = $this->triZone->scheduledTimes($prescription->frequency);

            if (empty($times)) {
                $builder->addEvent([
                    'uid' => 'rx-'.$prescription->id.'-prn@cardiocare',
                    'start' => $startDate,
                    'allDay' => true,
                    'summary' => 'As needed: '.$this->medicationLabel($prescription),
                    'description' => trim('Take only when needed. '.$prescription->instructions),
                    'categories' => 'Medication',
                ]);

                continue;
            }

            $days = $this->prescriptionDays($prescription, $startDate);

            foreach ($times as $time) {
                $builder->addEvent([
                    'uid' => 'rx-'.$prescription->id.'-'.str_replace(':', '', $time).'@cardiocare',
                    'start' => $startDate->copy()->setTimeFromTimeString($time),
                    'durationMinutes' => 5,
                    'rrule' => 'FREQ=DAILY;COUNT='.$days,
                    'summary' => 'Take '.$this->medicationLabel($prescription),
                    'description' => trim($prescription->frequency.' '.$prescription->route.' '.$prescription->instructions),
                    'categories' => 'Medication',
                ]);
            }
        }
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
