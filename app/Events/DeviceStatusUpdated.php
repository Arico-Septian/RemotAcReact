<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class DeviceStatusUpdated implements ShouldBroadcastNow
{
    public string $deviceId;
    public string $status;

    public function __construct(string $deviceId, string $status)
    {
        $this->deviceId = $deviceId;
        $this->status = $status;
    }

    public function broadcastOn()
    {
        return new Channel('device-status');
    }

    public function broadcastAs(): string
    {
        return 'DeviceStatusUpdated';
    }
}
