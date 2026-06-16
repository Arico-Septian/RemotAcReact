<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UsersChanged implements ShouldBroadcastNow
{
    public string $action;

    public function __construct(string $action = 'changed')
    {
        $this->action = $action;
    }

    public function broadcastOn()
    {
        return new Channel('users');
    }

    public function broadcastAs(): string
    {
        return 'UsersChanged';
    }
}
