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

    public const MQTT_HOST = 'mqtt_host';

    public const MQTT_PORT = 'mqtt_port';

    public const MQTT_USERNAME = 'mqtt_username';

    public const MQTT_PASSWORD = 'mqtt_password';

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
            self::DEVICE_CHECK_INTERVAL_MINUTES => [
                'label' => 'Device Check Interval',
                'description' => 'Seberapa sering sistem memeriksa status online/offline device.',
                'default' => 1,
                'min' => 1,
                'max' => 60,
                'unit' => 'minutes',
            ],
            self::SENSOR_OFFLINE_MINUTES => [
                'label' => 'Sensor Offline Timeout',
                'description' => 'Device dan sensor suhu dianggap offline jika tidak mengirim data selama durasi ini.',
                'default' => 3,
                'min' => 1,
                'max' => 30,
                'unit' => 'minutes',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, description: string, default: int|string, min?: int, max?: int, unit?: string, type?: string, rules?: string[]}>
     */
    public static function mqttDefinitions(): array
    {
        return [
            self::MQTT_HOST => [
                'label' => 'MQTT Host',
                'description' => 'Hostname atau alamat IP broker MQTT.',
                'default' => 'broker.hivemq.com',
                'type' => 'text',
                'rules' => ['required', 'string', 'max:255'],
            ],
            self::MQTT_PORT => [
                'label' => 'MQTT Port',
                'description' => 'Port broker MQTT.',
                'default' => 1883,
                'type' => 'number',
                'min' => 1,
                'max' => 65535,
                'unit' => 'port',
                'rules' => ['required', 'integer', 'min:1', 'max:65535'],
            ],
            self::MQTT_USERNAME => [
                'label' => 'MQTT Username',
                'description' => 'Username untuk autentikasi broker MQTT, jika diperlukan.',
                'default' => '',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
            ],
            self::MQTT_PASSWORD => [
                'label' => 'MQTT Password',
                'description' => 'Password untuk autentikasi broker MQTT, jika diperlukan.',
                'default' => '',
                'type' => 'password',
                'rules' => ['nullable', 'string', 'max:255'],
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, description: string, default: int|string, min?: int, max?: int, unit?: string, type?: string, rules?: string[]}>
     */
    public static function settingDefinitions(): array
    {
        return [
            ...self::retentionDefinitions(),
            ...self::monitoringDefinitions(),
            ...self::mqttDefinitions(),
        ];
    }

    public static function settingValue(string $key): int|string
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
                    $value = (string) ceil(((int) $legacySeconds / 60));
                }
            }
        } catch (QueryException) {
            return $definition['default'];
        }

        if ($value === null) {
            return $definition['default'];
        }

        if (isset($definition['type']) && in_array($definition['type'], ['text', 'password'], true)) {
            return (string) $value;
        }

        if (isset($definition['rules']) && in_array('string', $definition['rules'], true)) {
            return (string) $value;
        }

        if (! is_numeric($value)) {
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
     * @return array<int, array{key: string, label: string, description: string, value: int|string, default: int|string, min?: int, max?: int, unit?: string, type?: string, rules?: string[]}>
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

    /**
     * @return array<int, array{key: string, label: string, description: string, value: int|string, default: int|string, min?: int, max?: int, unit?: string, type?: string, rules?: string[]}>
     */
    public static function mqttSettings(): array
    {
        return collect(self::mqttDefinitions())
            ->map(fn (array $definition, string $key) => [
                'key' => $key,
                ...$definition,
                'value' => self::settingValue($key),
            ])
            ->values()
            ->all();
    }

    public static function mqttHost(): string
    {
        return trim((string) self::settingValue(self::MQTT_HOST));
    }

    public static function mqttPort(): int
    {
        return self::settingValue(self::MQTT_PORT);
    }

    public static function mqttUsername(): ?string
    {
        return self::nullableStringSetting(self::MQTT_USERNAME, true);
    }

    public static function mqttPassword(): ?string
    {
        return self::nullableStringSetting(self::MQTT_PASSWORD);
    }

    private static function nullableStringSetting(string $key, bool $trim = false): ?string
    {
        $value = (string) self::settingValue($key);

        if (trim($value) === '') {
            return null;
        }

        return $trim ? trim($value) : $value;
    }
}
