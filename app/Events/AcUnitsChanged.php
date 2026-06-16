<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class AcUnitsChanged implements ShouldBroadcastNow
{
    public int $roomId;

    public string $action;

    public function __construct(int $roomId, string $action = 'changed')
    {
        $this->roomId = $roomId;
        $this->action = $action;
    }

    public function broadcastOn()
    {
        return new Channel('ac-units');
    }

    public function broadcastAs(): string
    {
        return 'AcUnitsChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
            'action' => $this->action,
        ];
    }
}
