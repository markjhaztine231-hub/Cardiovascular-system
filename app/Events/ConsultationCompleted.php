<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ConsultationCompleted implements ShouldBroadcast
{
    use InteractsWithSockets;

    public int $appointmentId;

    public int $patientId;

    public string $doctorName;

    public string $date;

    public function __construct(Appointment $appointment)
    {
        $appointment->loadMissing([
            'patient.user:id,name',
            'provider.user:id,name',
        ]);

        $this->appointmentId = $appointment->id;
        $this->patientId = $appointment->patient?->user_id ?? $appointment->patient_id;
        $this->doctorName = $appointment->provider?->user?->name ?? 'Unknown';
        $this->date = $appointment->scheduled_date?->format('Y-m-d');
    }

    public function broadcastOn(): Channel
    {
        return new Channel('patient.'.$this->patientId);
    }
}
