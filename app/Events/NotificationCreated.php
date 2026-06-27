<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class NotificationCreated implements ShouldBroadcastNow
{
    public array $payload;

    public function __construct(Notification $notification)
    {
        $this->payload = [
            'id' => $notification->id,
            'user_id' => $notification->user_id,
            'type' => $notification->type,
            'severity' => $notification->severity,
            'title' => $notification->title,
            'message' => $notification->message,
            'link' => $notification->link,
            'meta' => $notification->meta,
            'created_at' => optional($notification->created_at)->toIso8601String(),
            'time_ago' => optional($notification->created_at)->diffForHumans(),
        ];
    }

    public function broadcastOn()
    {
        return new Channel('notifications');
    }

    public function broadcastAs(): string
    {
        return 'NotificationCreated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
