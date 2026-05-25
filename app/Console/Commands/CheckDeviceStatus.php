<?php

namespace App\Console\Commands;

use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckDeviceStatus extends Command
{
    protected $signature = 'device:check-status';

    protected $description = 'Check device online/offline status';

    const STATUS_ONLINE = 'online';

    const STATUS_OFFLINE = 'offline';

    const OFFLINE_THRESHOLD = 90;

    const STATUS_TTL = 300;

    const UNKNOWN_TTL = 60;

    public function handle()
    {
        $lock = Cache::lock('device_check_lock', 90);

        if (! $lock->get()) {
            $this->warn('Another instance is already running');

            return self::SUCCESS;
        }

        try {
            $this->info('Starting device status checker...');

            for ($i = 0; $i < 12; $i++) {

                $now = now('Asia/Jakarta');

                // Get all devices from rooms table
                $devices = Room::whereNotNull('device_id')
                    ->select('id', 'device_id')
                    ->get();

                foreach ($devices as $room) {
                    // Normalize like MqttSubscribe to share the same cache keys
                    $deviceId = strtolower(trim((string) $room->device_id));

                    $this->checkDeviceStatus($deviceId, $now);
                }

                // Also check for devices not in rooms table (if any)
                $this->checkOrphanDevices($now);

                sleep(5);
            }

            $this->info('Device status check completed');

        } catch (\Throwable $e) {
            Log::error('Device status check error: '.$e->getMessage());
            $this->error('Error: '.$e->getMessage());
        } finally {
            optional($lock)->release();
        }

        return self::SUCCESS;
    }

    private function checkDeviceStatus(string $deviceId, Carbon $now): void
    {
        $lastSeen = $this->lastSeenFrom(Cache::get("device_{$deviceId}_last_seen"))
            ?? $this->lastSeenFrom(Room::whereRaw('LOWER(TRIM(device_id)) = ?', [$deviceId])->value('last_seen'));
        $statusKey = "device_status_{$deviceId}";
        $unknownKey = "device_unknown_{$deviceId}";

        // UNKNOWN - never seen
        if (! $lastSeen) {
            if (! Cache::get($unknownKey)) {
                Log::info('Device UNKNOWN', ['device' => $deviceId]);
                $this->warn("UNKNOWN -> {$deviceId}");

                Cache::put($unknownKey, true, self::UNKNOWN_TTL);
            }

            return;
        }

        // Check if offline
        $diff = $now->diffInSeconds($lastSeen, true);
        $isOffline = $diff > self::OFFLINE_THRESHOLD;
        $currentStatus = Cache::get($statusKey);

        // OFFLINE
        if ($isOffline && $currentStatus !== self::STATUS_OFFLINE) {
            Log::info('Device OFFLINE', [
                'device' => $deviceId,
                'last_seen' => $lastSeen->toDateTimeString(),
                'diff_seconds' => $diff,
            ]);

            $this->error("OFFLINE -> {$deviceId} ({$diff}s ago)");

            Cache::put($statusKey, self::STATUS_OFFLINE, self::STATUS_TTL);
            Cache::forget($unknownKey);

            $this->updateDeviceInDatabase($deviceId, 'offline', $lastSeen);

            // Fire persistent notification so admins see this even without a browser open
            $room = Room::whereRaw('LOWER(TRIM(device_id)) = ?', [$deviceId])->first();
            if ($room) {
                \App\Models\Notification::deviceOffline($room->name, $deviceId);
            }
        }
        // ONLINE
        elseif (! $isOffline && $currentStatus !== self::STATUS_ONLINE) {
            Log::info('Device ONLINE', [
                'device' => $deviceId,
                'last_seen' => $lastSeen->toDateTimeString(),
                'diff_seconds' => $diff,
            ]);

            $this->info("ONLINE -> {$deviceId} ({$diff}s ago)");

            Cache::put($statusKey, self::STATUS_ONLINE, self::STATUS_TTL);
            Cache::forget($unknownKey);

            $this->updateDeviceInDatabase($deviceId, 'online', $lastSeen);

            $room = Room::whereRaw('LOWER(TRIM(device_id)) = ?', [$deviceId])->first();
            if ($room) {
                \App\Models\Notification::deviceOnline($room->name, $deviceId);
            }
        }
    }

    private function checkOrphanDevices($now)
    {
        $cacheKeys = Cache::get('seen_device_ids', []);

        foreach ($cacheKeys as $deviceId) {
            $roomExists = Room::where('device_id', $deviceId)->exists();

            if (! $roomExists) {
                $this->line("Orphan device found in cache: {$deviceId}");
            }
        }
    }

    private function updateDeviceInDatabase(string $deviceId, string $status, ?Carbon $lastSeen = null): void
    {
        try {
            $room = Room::whereRaw('LOWER(TRIM(device_id)) = ?', [$deviceId])->first();

            if ($room) {
                $data = ['device_status' => $status];

                if ($lastSeen) {
                    $data['last_seen'] = $lastSeen;
                }

                $room->update($data);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update device status in DB: '.$e->getMessage());
        }
    }

    private function lastSeenFrom(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
