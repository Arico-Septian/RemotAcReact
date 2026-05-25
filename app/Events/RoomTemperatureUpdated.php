<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class RoomTemperatureUpdated implements ShouldBroadcastNow
{
    public $room;

    public $temperature;

    public function __construct(string $room, ?float $temperature)
    {
        $this->room = $room;
        $this->temperature = $temperature;
    }

    public function broadcastOn()
    {
        return new Channel('device-status');
    }

    public function broadcastAs(): string
    {
        return 'RoomTemperatureUpdated';
    }
}
