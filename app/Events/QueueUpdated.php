<?php

namespace App\Events;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Collection;

class QueueUpdated implements ShouldBroadcast
{
    use InteractsWithSockets;

    public int $doctorId;

    public Collection $queueList;

    /**
     * @param  int  $doctorId  channel is the doctor's USER id
     * @param  Collection<Appointment>  $queue
     */
    public function __construct(int $doctorId, Collection $queue)
    {
        $this->doctorId = $doctorId;
        $this->queueList = $queue->map(fn (Appointment $a) => [
            'id' => $a->id,
            'patient_name' => $a->patient?->user?->name,
            'patient_id' => $a->patient?->user_id,
            'time' => $a->scheduled_time ? Carbon::parse($a->scheduled_time)->format('H:i') : null,
            'status' => $a->status,
            'service_type' => $a->service_type,
        ]);
    }

    public function broadcastOn(): Channel
    {
        return new Channel('doctor.'.$this->doctorId);
    }
}
