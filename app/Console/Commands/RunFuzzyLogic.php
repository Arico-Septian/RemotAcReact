<?php

namespace App\Console\Commands;

use App\Http\Controllers\AcControlController;
use App\Models\Notification;
use App\Models\Room;
use App\Models\RoomTemperature;
use App\Models\UserLog;
use App\Services\FuzzyMamdaniService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('fuzzy:run')]
#[Description('Apply fuzzy logic to automatically adjust AC setpoints based on room temperature and trends')]
class RunFuzzyLogic extends Command
{
    public function handle()
    {
        $rooms = Room::with(['acUnits.status'])->get();

        if ($rooms->isEmpty()) {
            $this->info('No rooms found');

            return Command::SUCCESS;
        }

        $processed = 0;
        $skipped = 0;
        $fuzzyService = new FuzzyMamdaniService;

        foreach ($rooms as $room) {
            $cooldownKey = 'fuzzy_room_'.$room->id;

            if (Cache::has($cooldownKey)) {
                $skipped++;

                continue;
            }
            $normalized = RoomTemperature::normalizeRoomName($room->name);

            $tempHistory = RoomTemperature::where('room', $normalized)
                ->latest()
                ->take(2)
                ->get();

            $deviceId = strtolower(trim((string) $room->device_id));
            $deviceStatus = Cache::get("device_status_{$deviceId}", $room->device_status ?? 'offline');
            $lastSeen = $this->lastSeenFrom(Cache::get("device_{$deviceId}_last_seen"))
                ?? $this->lastSeenFrom($room->last_seen);

            $isDeviceOnline = $deviceStatus === 'online'
                && $lastSeen
                && now()->diffInSeconds($lastSeen, true) <= 300;
            $sensorStatus = Cache::get("room_temp_status_{$normalized}");
            $latestTemp = $tempHistory->first();
            $isTempAvailable = $latestTemp
                && $sensorStatus !== 'offline'
                && $latestTemp->created_at
                && now()->diffInSeconds($latestTemp->created_at, true) <= 120;

            if (! $isTempAvailable) {
                Notification::fuzzyWarning($room->name, 'temperature_offline');

                continue;
            }

            if (! $isDeviceOnline) {
                Notification::fuzzyWarning($room->name, 'device_offline');

                continue;
            }

            $activeAcUnits = $room->acUnits->filter(
                fn ($ac) => strtoupper((string) ($ac->status?->power ?? 'OFF')) === 'ON'
            );

            Notification::fuzzyRecovery($room->name);

            if ($activeAcUnits->isEmpty()) {
                $skipped++;

                continue;
            }

            $currentTemp = $latestTemp->temperature;

            $deltaT = 0;
            if ($tempHistory->count() > 1) {
                $previousTemp = $tempHistory[1]->temperature;
                $timeDiffSeconds = max(1, $latestTemp->created_at->diffInSeconds($tempHistory[1]->created_at));

                if ($timeDiffSeconds <= 300 && $previousTemp !== null) {
                    $deltaT = ($currentTemp - $previousTemp) / ($timeDiffSeconds / 60);
                }
            }

            $fuzzyResult = $fuzzyService->calculate($currentTemp, $deltaT);

            $currentSetpoint = (int) round(
                $activeAcUnits
                    ->map(fn ($ac) => $ac->status?->set_temperature ?? 24)
                    ->avg()
            );

            $decision = $fuzzyService->decideAction($fuzzyResult, $currentSetpoint);

            Notification::fuzzyAction(
                $room->name,
                $decision['action'],
                $decision['setpoint_before'],
                $decision['setpoint_after']
            );

            if ($decision['action'] === 'DIAM') {
                continue;
            }

            Cache::put($cooldownKey, true, 60);

            $acController = new AcControlController;

            foreach ($activeAcUnits as $ac) {
                $acController->fuzzySetTemp(
                    $ac,
                    $decision['setpoint_after']
                );

                UserLog::create([
                    'user_id' => null,
                    'room' => $room->name,
                    'ac' => 'AC '.$ac->ac_number.($ac->name ? ' '.$ac->name : ''),
                    'activity' => 'fuzzy_'.strtolower($decision['action']).'_'.$decision['setpoint_after'],
                ]);
            }

            $processed++;
            $this->info("Room '{$room->name}': {$decision['action']} (setpoint {$decision['setpoint_before']}°C → {$decision['setpoint_after']}°C, temp: {$currentTemp}°C)");
        }

        $this->newLine();
        $this->info("Fuzzy logic applied to {$processed} room(s), {$skipped} skipped (cooldown)");

        return Command::SUCCESS;
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
