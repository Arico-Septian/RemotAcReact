<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    /**
     * Seconds since last_seen after which a device is considered offline.
     * Used by the UI views and CheckDeviceStatus so notifications and
     * on-screen status always agree. Sized for a 60 s device ping (3x).
     */
    public const ONLINE_THRESHOLD_SECONDS = 180;

    /**
     * Seconds since the last RoomTemperature record after which the reading
     * is treated as stale (sensor offline / value shown as null). Larger than
     * the device threshold because the ESP only sends a temperature heartbeat
     * every 60 s when the value is stable (report-by-exception), so 180 s ≈ 3x.
     */
    public const TEMPERATURE_STALE_SECONDS = 180;

    protected $fillable = [
        'name',
        'device_id',
        'floor',
        'device_status',
        'last_seen',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    public function acUnits()
    {
        return $this->hasMany(AcUnit::class);
    }

    public static function onlineThresholdSeconds(): int
    {
        return AppSetting::sensorOfflineSeconds();
    }

    public static function temperatureStaleSeconds(): int
    {
        return AppSetting::sensorOfflineSeconds();
    }
}
