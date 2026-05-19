<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomTemperature;
use App\Models\UserLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $rooms = Room::with(['acUnits.status'])
            ->orderBy('floor')
            ->orderBy('name')
            ->get();
        $latestTemperatures = RoomTemperature::latestByNormalizedRoom();

        $recentActivities = UserLog::with('user')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->map(fn ($log) => $this->formatLog($log));

        $onlineRooms = 0;
        $offlineRooms = 0;

        foreach ($rooms as $room) {
            $roomKey = RoomTemperature::normalizeRoomName($room->name);
            $sensorStatus = Cache::get("room_temp_status_{$roomKey}");
            $latestTemperature = $latestTemperatures->get($roomKey);
            $room->last_temperature = optional($latestTemperature)->temperature;
            $room->temperature = $room->last_temperature;

            $deviceId = strtolower(trim((string) $room->device_id));
            $status = Cache::get("device_status_{$deviceId}", $room->device_status ?? 'offline');
            $lastSeen = $this->lastSeenFrom(Cache::get("device_{$deviceId}_last_seen"))
                ?? $this->lastSeenFrom($room->last_seen);

            $isOnline = ($status === 'online' || $status === 'available')
                && $lastSeen
                && now()->diffInSeconds($lastSeen, true) <= 300;

            $room->device_status = $isOnline ? 'online' : 'offline';

            $isOnline ? $onlineRooms++ : $offlineRooms++;

            $temperatureIsOffline = ! $isOnline || $sensorStatus === 'offline';
            if ($latestTemperature && $latestTemperature->created_at) {
                $temperatureIsOffline = $temperatureIsOffline
                    || now()->diffInSeconds($latestTemperature->created_at, true) > 30;
            } else {
                $temperatureIsOffline = true;
            }

            if ($temperatureIsOffline) {
                $room->temperature = null;
            }
        }

        $totalRooms = $rooms->count();

        $allAcUnits = $rooms->flatMap->acUnits;
        $totalAc = $allAcUnits->count();
        $activeAc = $allAcUnits->filter(fn ($ac) => optional($ac->status)->power === 'ON')->count();
        $inactiveAc = $totalAc - $activeAc;

        return view('dashboard.dashboard', compact(
            'rooms',
            'totalRooms',
            'totalAc',
            'activeAc',
            'inactiveAc',
            'onlineRooms',
            'offlineRooms',
            'recentActivities'
        ));
    }

    public function recentActivities()
    {
        $logs = UserLog::with('user')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->map(fn ($log) => $this->formatLog($log));

        return response()->json($logs);
    }

    public function stats()
    {
        $rooms = Room::with(['acUnits.status'])->get();

        $onlineRooms = 0;
        $offlineRooms = 0;
        foreach ($rooms as $room) {
            $deviceId = strtolower(trim((string) $room->device_id));
            $status = Cache::get("device_status_{$deviceId}", $room->device_status ?? 'offline');
            $lastSeen = $this->lastSeenFrom(Cache::get("device_{$deviceId}_last_seen"))
                ?? $this->lastSeenFrom($room->last_seen);

            $isOnline = ($status === 'online' || $status === 'available')
                && $lastSeen
                && now()->diffInSeconds($lastSeen, true) <= 300;

            $isOnline ? $onlineRooms++ : $offlineRooms++;
        }

        $allAcUnits = $rooms->flatMap->acUnits;
        $totalAc = $allAcUnits->count();
        $activeAc = $allAcUnits->filter(fn ($ac) => optional($ac->status)->power === 'ON')->count();

        return response()->json([
            'total_rooms' => $rooms->count(),
            'online_rooms' => $onlineRooms,
            'offline_rooms' => $offlineRooms,
            'total_ac' => $totalAc,
            'active_ac' => $activeAc,
            'inactive_ac' => $totalAc - $activeAc,
        ]);
    }

    private function formatLog(UserLog $log): array
    {
        $name = $log->user->name ?? 'System';
        $activity = (string) $log->activity;
        $meta = $this->describeActivity($activity);

        return [
            'id' => $log->id,
            'user_name' => $name,
            'user_initial' => mb_strtoupper(mb_substr($name, 0, 1)),
            'user_id' => $log->user_id,
            'user_avatar' => $log->user?->avatar_url,
            'raw_activity' => $activity,
            'description' => $meta['description'],
            'icon' => $meta['icon'],
            'tone' => $meta['tone'],
            'room' => $log->room,
            'ac' => $log->ac,
            'time' => $log->created_at?->format('H:i'),
            'time_human' => $log->created_at?->diffForHumans(),
        ];
    }

    private function describeActivity(string $activity): array
    {
        $a = strtolower(trim($activity));

        if (in_array($a, ['on', 'bulk_on'], true)) {
            return ['description' => $a === 'bulk_on' ? 'Menyalakan semua AC' : 'Menyalakan AC', 'icon' => 'fa-solid fa-power-off', 'tone' => 'mint'];
        }
        if (in_array($a, ['off', 'bulk_off'], true)) {
            return ['description' => $a === 'bulk_off' ? 'Mematikan semua AC' : 'Mematikan AC', 'icon' => 'fa-solid fa-power-off', 'tone' => 'slate'];
        }
        if (str_starts_with($a, 'set_temp_')) {
            $v = substr($a, 9);

            return ['description' => "Set suhu {$v}°C", 'icon' => 'fa-solid fa-temperature-half', 'tone' => 'cyan'];
        }
        if (str_starts_with($a, 'mode_')) {
            $v = ucfirst(substr($a, 5));

            return ['description' => "Ubah mode → {$v}", 'icon' => 'fa-solid fa-sliders', 'tone' => 'lavender'];
        }
        if (str_starts_with($a, 'fan_speed_')) {
            $v = ucfirst(substr($a, 10));

            return ['description' => "Kecepatan fan → {$v}", 'icon' => 'fa-solid fa-fan', 'tone' => 'sky'];
        }
        if (str_starts_with($a, 'swing_')) {
            $v = ucfirst(substr($a, 6));

            return ['description' => "Swing → {$v}", 'icon' => 'fa-solid fa-arrows-left-right', 'tone' => 'lavender'];
        }
        if ($a === 'login') {
            return ['description' => 'Login ke sistem', 'icon' => 'fa-solid fa-right-to-bracket', 'tone' => 'mint'];
        }
        if ($a === 'logout') {
            return ['description' => 'Logout dari sistem', 'icon' => 'fa-solid fa-right-from-bracket', 'tone' => 'slate'];
        }
        if (str_contains($a, 'delete')) {
            return ['description' => ucfirst(str_replace('_', ' ', $activity)), 'icon' => 'fa-solid fa-trash', 'tone' => 'coral'];
        }
        if (str_contains($a, 'create') || str_contains($a, 'add')) {
            return ['description' => ucfirst(str_replace('_', ' ', $activity)), 'icon' => 'fa-solid fa-plus', 'tone' => 'cyan'];
        }
        if (str_contains($a, 'update') || str_contains($a, 'edit')) {
            return ['description' => ucfirst(str_replace('_', ' ', $activity)), 'icon' => 'fa-solid fa-pen-to-square', 'tone' => 'amber'];
        }
        if (str_contains($a, 'timer') || str_contains($a, 'schedule')) {
            return ['description' => ucfirst(str_replace('_', ' ', $activity)), 'icon' => 'fa-solid fa-clock', 'tone' => 'amber'];
        }
        if ($a === 'control_ac') {
            return ['description' => 'Kontrol AC', 'icon' => 'fa-solid fa-snowflake', 'tone' => 'cyan'];
        }

        return ['description' => ucfirst(str_replace('_', ' ', $activity)), 'icon' => 'fa-solid fa-circle-info', 'tone' => 'slate'];
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
