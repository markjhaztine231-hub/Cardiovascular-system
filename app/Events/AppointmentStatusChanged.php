<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AppointmentStatusChanged implements ShouldBroadcast
{
    use InteractsWithSockets;

    public int $appointmentId;

    public string $status;

    public string $patientName;

    public ?string $doctorName;

    public function __construct(Appointment $appointment)
    {
        $appointment->loadMissing([
            'patient.user:id,name',
            'provider.user:id,name',
        ]);

        $this->appointmentId = $appointment->id;
        $this->status = $appointment->status;
        $this->patientName = $appointment->patient?->user?->name ?? 'Unknown';
        $this->doctorName = $appointment->provider?->user?->name;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('receptionist');
    }
}
