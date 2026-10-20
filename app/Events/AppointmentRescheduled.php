<?php

namespace App\Events;

use App\Models\Appointment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AppointmentRescheduled implements ShouldBroadcast
{
    use InteractsWithSockets;

    public int $appointmentId;

    public int $patientId;

    public string $newDate;

    public string $newTime;

    public ?string $doctorName;

    public function __construct(Appointment $appointment)
    {
        $appointment->loadMissing([
            'patient.user:id,name',
            'provider.user:id,name',
        ]);

        $this->appointmentId = $appointment->id;
        // Patient channel fans out to the patient's USER id (what the patient UI
        // knows about itself); appointment.patient_id is the patient record id.
        $this->patientId = $appointment->patient?->user_id ?? $appointment->patient_id;
        $this->newDate = $appointment->scheduled_date instanceof CarbonInterface
            ? $appointment->scheduled_date->format('M d, Y')
            : (string) $appointment->scheduled_date;
        $this->newTime = $appointment->scheduled_time
            ? Carbon::parse($appointment->scheduled_time)->format('h:i A') : '';
        $this->doctorName = $appointment->provider?->user?->name;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('patient.'.$this->patientId);
    }
}
