<?php

namespace App\Events;

use App\Models\AcUnit;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class AcTimerUpdated implements ShouldBroadcastNow
{
    public array $payload;

    public function __construct(AcUnit $ac)
    {
        $ac->loadMissing('room');

        $this->payload = [
            'ac_unit_id' => $ac->id,
            'ac_number' => $ac->ac_number,
            'room_id' => optional($ac->room)->id,
            'room_name' => optional($ac->room)->name,
            'timer_on' => $ac->timer_on
                ? \Carbon\Carbon::parse($ac->timer_on)->setTimezone('Asia/Jakarta')->format('H:i')
                : null,
            'timer_off' => $ac->timer_off
                ? \Carbon\Carbon::parse($ac->timer_off)->setTimezone('Asia/Jakarta')->format('H:i')
                : null,
        ];
    }

    public function broadcastOn()
    {
        return new Channel('device-status');
    }

    public function broadcastAs(): string
    {
        return 'AcTimerUpdated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
