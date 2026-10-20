<?php

namespace App\Events;

use App\Models\Appointment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class RescheduleDenied implements ShouldBroadcast
{
    use InteractsWithSockets;

    public int $appointmentId;

    public int $patientId;

    public string $scheduledDate;

    public string $scheduledTime;

    public ?string $doctorName;

    public function __construct(Appointment $appointment)
    {
        $appointment->loadMissing([
            'patient.user:id,name',
            'provider.user:id,name',
        ]);

        $this->appointmentId = $appointment->id;
        $this->patientId = $appointment->patient?->user_id ?? $appointment->patient_id;
        $this->scheduledDate = $appointment->scheduled_date instanceof CarbonInterface
            ? $appointment->scheduled_date->format('M d, Y')
            : (string) $appointment->scheduled_date;
        $this->scheduledTime = $appointment->scheduled_time
            ? Carbon::parse($appointment->scheduled_time)->format('h:i A') : '';
        $this->doctorName = $appointment->provider?->user?->name;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('patient.'.$this->patientId);
    }
}
