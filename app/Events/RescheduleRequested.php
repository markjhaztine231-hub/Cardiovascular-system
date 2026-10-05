<?php

namespace App\Events;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class RescheduleRequested implements ShouldBroadcast
{
    use InteractsWithSockets;

    public int $appointmentId;

    public string $patientName;

    public ?string $requestedDate;

    public ?string $requestedTime;

    public function __construct(Appointment $appointment)
    {
        $appointment->loadMissing('patient.user:id,name');

        $this->appointmentId = $appointment->id;
        $this->patientName = $appointment->patient?->user?->name ?? 'Unknown';
        $this->requestedDate = $appointment->reschedule_requested_date?->format('Y-m-d');
        $this->requestedTime = $appointment->reschedule_requested_time
            ? Carbon::parse($appointment->reschedule_requested_time)->format('H:i')
            : null;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('receptionist');
    }
}
