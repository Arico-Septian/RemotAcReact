<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomTemperature extends Model
{
    protected $fillable = ['room', 'temperature'];

    public static function normalizeRoomName($room): string
    {
        // Consistent with MqttService::roomToTopic — strip internal whitespace too
        return strtolower(preg_replace('/\s+/', '_', trim((string) $room)));
    }

    public static function latestByNormalizedRoom()
    {
        // ambil id terakhir per room langsung dari database
        $latestIds = static::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('room')
            ->pluck('id');

        return static::query()
            ->whereIn('id', $latestIds)
            ->get()
            ->keyBy(fn ($t) => static::normalizeRoomName($t->room));
    }
}
