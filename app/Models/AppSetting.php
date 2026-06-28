<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class AppSetting extends Model
{
    public const NOTIFICATION_RETENTION_DAYS = 'notification_retention_days';

    public const TEMPERATURE_RETENTION_DAYS = 'temperature_retention_days';

    public const ACTIVITY_LOG_RETENTION_DAYS = 'activity_log_retention_days';

    public const SENSOR_OFFLINE_SECONDS = 'sensor_offline_seconds';

    public const SENSOR_OFFLINE_MINUTES = 'sensor_offline_minutes';

    public const DEVICE_CHECK_INTERVAL_MINUTES = 'device_check_interval_minutes';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * @return array<string, array{label: string, description: string, default: int, min: int, max: int}>
     */
    public static function retentionDefinitions(): array
    {
        return [
            self::NOTIFICATION_RETENTION_DAYS => [
                'label' => 'Notifications',
                'description' => 'System alerts, device online/offline messages, and app notifications.',
                'default' => 3,
                'min' => 1,
                'max' => 60,
            ],
            self::TEMPERATURE_RETENTION_DAYS => [
                'label' => 'Room Temperature',
                'description' => 'Historical temperature samples saved from room ESP devices.',
                'default' => 7,
                'min' => 1,
                'max' => 90,
            ],
            self::ACTIVITY_LOG_RETENTION_DAYS => [
                'label' => 'Activity Log',
                'description' => 'User actions such as login, AC control, room management, and user changes.',
                'default' => 30,
                'min' => 1,
                'max' => 365,
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, description: string, default: int, min: int, max: int, unit: string}>
     */
    public static function monitoringDefinitions(): array
    {
        return [
            self::SENSOR_OFFLINE_MINUTES => [
                'label' => 'Sensor Offline Timeout',
                'description' => 'Device dan sensor suhu dianggap offline jika tidak mengirim data selama durasi ini.',
                'default' => 3,
                'min' => 1,
                'max' => 30,
                'unit' => 'minutes',
            ],
            self::DEVICE_CHECK_INTERVAL_MINUTES => [
                'label' => 'Device Check Interval',
                'description' => 'Seberapa sering sistem memeriksa status online/offline device.',
                'default' => 1,
                'min' => 1,
                'max' => 60,
                'unit' => 'minutes',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, description: string, default: int, min: int, max: int, unit?: string}>
     */
    public static function settingDefinitions(): array
    {
        return [
            ...self::retentionDefinitions(),
            ...self::monitoringDefinitions(),
        ];
    }

    public static function settingValue(string $key): int
    {
        $definition = self::settingDefinitions()[$key] ?? null;

        if ($definition === null) {
            return 1;
        }

        try {
            $value = self::query()->where('key', $key)->value('value');

            if ($value === null && $key === self::SENSOR_OFFLINE_MINUTES) {
                $legacySeconds = self::query()->where('key', self::SENSOR_OFFLINE_SECONDS)->value('value');

                if (is_numeric($legacySeconds)) {
                    $value = (string) ceil(((int) $legacySeconds) / 60);
                }
            }
        } catch (QueryException) {
            return $definition['default'];
        }

        if ($value === null || ! is_numeric($value)) {
            return $definition['default'];
        }

        return min($definition['max'], max($definition['min'], (int) $value));
    }

    public static function retentionDays(string $key): int
    {
        return self::settingValue($key);
    }

    public static function sensorOfflineSeconds(): int
    {
        return self::settingValue(self::SENSOR_OFFLINE_MINUTES) * 60;
    }

    public static function deviceCheckIntervalMinutes(): int
    {
        return self::settingValue(self::DEVICE_CHECK_INTERVAL_MINUTES);
    }

    /**
     * @return array<int, array{key: string, label: string, description: string, value: int, default: int, min: int, max: int}>
     */
    public static function retentionSettings(): array
    {
        return collect(self::retentionDefinitions())
            ->map(fn (array $definition, string $key) => [
                'key' => $key,
                ...$definition,
                'value' => self::retentionDays($key),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{key: string, label: string, description: string, value: int, default: int, min: int, max: int, unit: string}>
     */
    public static function monitoringSettings(): array
    {
        return collect(self::monitoringDefinitions())
            ->map(fn (array $definition, string $key) => [
                'key' => $key,
                ...$definition,
                'value' => self::settingValue($key),
            ])
            ->values()
            ->all();
    }
}
