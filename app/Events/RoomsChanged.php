<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class RoomsChanged implements ShouldBroadcastNow
{
    public string $action;

    public ?string $room;

    public function __construct(string $action = 'changed', ?string $room = null)
    {
        $this->action = $action;
        $this->room = $room;
    }

    public function broadcastOn()
    {
        return new Channel('rooms');
    }

    public function broadcastAs(): string
    {
        return 'RoomsChanged';
    }
}
