<?php

namespace App\Http\Controllers;

use App\Events\RoomsChanged;
use App\Models\AcUnit;
use App\Models\Notification;
use App\Models\Room;
use App\Models\RoomTemperature;
use App\Models\UserLog;
use App\Services\FuzzyMamdaniService;
use App\Services\MqttService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $rooms = Room::with(['acUnits.status'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%');
            })
            ->orderBy('floor')
            ->orderBy('name')
            ->get();

        $recentByRoom = RoomTemperature::recentByNormalizedRoom(perRoom: 2, maxAgeSeconds: 3600);
        // Suhu terakhir tanpa filter umur — untuk fallback tampilan walau device offline.
        $latestByRoom = RoomTemperature::latestByNormalizedRoom();

        $fuzzyService = new FuzzyMamdaniService;

        foreach ($rooms as $room) {

            $deviceId = strtolower(trim((string) $room->device_id));

            $status = Cache::get("device_status_{$deviceId}", $room->device_status ?? 'offline');

            $lastSeen = $this->lastSeenFrom(
                Cache::get("device_{$deviceId}_last_seen")
            ) ?? $this->lastSeenFrom($room->last_seen);

            $isOnline =
                ($status === 'online' || $status === 'available')
                && $lastSeen
                && now()->diffInSeconds($lastSeen, true) <= Room::onlineThresholdSeconds();

            $room->device_status = $isOnline ? 'online' : 'offline';

            $roomKey = RoomTemperature::normalizeRoomName($room->name);
            $sensorStatus = Cache::get("room_temp_status_{$roomKey}");
            $tempHistory = $recentByRoom->get($roomKey) ?? collect();
            $lastTempRecord = $tempHistory->first();

            // last_temperature = pembacaan terakhir (umur berapa pun) supaya tetap tampil saat offline.
            $latestRecord = $latestByRoom->get($roomKey);
            $room->last_temperature = optional($latestRecord)->temperature;
            $room->temperature = $room->last_temperature;

            // Check if temperature data is stale (offline)
            $room->temperature_is_offline = ! $isOnline || $sensorStatus === 'offline';
            if ($latestRecord && $latestRecord->created_at) {
                $secondsSinceLastTemp = now()->diffInSeconds($latestRecord->created_at, true);
                $room->temperature_is_offline = $room->temperature_is_offline || $secondsSinceLastTemp > Room::temperatureStaleSeconds();
            } else {
                $room->temperature_is_offline = true;
            }

            if ($room->temperature_is_offline) {
                $room->temperature = null;
            }

            $currentTemp = $room->temperature_is_offline ? null : $tempHistory->first()?->temperature;

            $deltaT = 0;
            if ($currentTemp !== null && $tempHistory->count() > 1) {
                $previousTemp = $tempHistory[1]->temperature;
                $currentCreatedAt = $tempHistory->first()->created_at;
                $previousCreatedAt = $tempHistory[1]->created_at;
                $timeDiffSeconds = max(1, $currentCreatedAt->diffInSeconds($previousCreatedAt));

                if ($timeDiffSeconds <= 300 && $previousTemp !== null) {
                    $deltaT = ($currentTemp - $previousTemp) / ($timeDiffSeconds / 60);
                }
            }

            if ($currentTemp !== null) {

                $fuzzyResult = $fuzzyService->calculate(
                    $currentTemp,
                    $deltaT
                );

                $room->temperature = round($currentTemp, 1);

                $room->delta_t = round($deltaT, 2);

                $room->fuzzy = $fuzzyResult;

                $activeAcUnits = $room->acUnits->filter(
                    fn ($ac) => strtoupper((string) ($ac->status?->power ?? 'OFF')) === 'ON'
                );
                $currentSetpoint = $activeAcUnits->isNotEmpty()
                    ? (int) round($activeAcUnits->map(fn ($ac) => $ac->status?->set_temperature ?? 24)->avg())
                    : 24;

                $decision = $fuzzyService->decideAction(
                    $fuzzyResult,
                    $currentSetpoint
                );

                $room->decision = $decision;
            } else {

                $room->delta_t = 0;
                $room->fuzzy = null;
                $room->decision = null;
            }
        }

        $roomsData = $rooms->map(function (Room $room) {
            $activeAcs = $room->acUnits->filter(fn ($ac) => $ac->status && $ac->status->power === 'ON')->count();

            return [
                'id' => $room->id,
                'name' => $room->name,
                'floor' => $room->floor ?: 'Lainnya',
                'device_id' => $room->device_id,
                'device_status' => $room->device_status,
                'temperature' => $room->temperature !== null ? (float) $room->temperature : null,
                'last_temperature' => $room->last_temperature !== null ? (float) $room->last_temperature : null,
                'temperature_is_offline' => (bool) $room->temperature_is_offline,
                'delta_t' => $room->delta_t ?? 0,
                'fuzzy' => $room->fuzzy,
                'decision' => $room->decision,
                'ac_active_count' => $activeAcs,
                'ac_idle_count' => $room->acUnits->count() - $activeAcs,
                'ac_units_count' => $room->acUnits->count(),
            ];
        })->values();

        return Inertia::render('RoomsManage', [
            'rooms' => $roomsData,
            'search' => $request->query('search', ''),
        ]);
    }

    /* === CREATE ROOM === */
    public function store(Request $request)
    {
        $request->merge([
            'name' => strtolower(trim((string) $request->name)),
            'device_id' => strtolower(trim((string) $request->device_id)),
            'floor' => strtolower(trim((string) $request->floor)),
        ]);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('rooms', 'name'),
            ],
            'device_id' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('rooms', 'device_id'),
            ],
            'floor' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_]*$/',
            ],
        ], [
            'name.regex' => 'Nama ruangan hanya boleh berisi huruf, angka, dan underscore (tanpa spasi).',
            'device_id.regex' => 'Device ID hanya boleh berisi huruf, angka, underscore, dan strip (tanpa spasi).',
            'floor.regex' => 'Lantai/Zone hanya boleh berisi huruf, angka, dan underscore (tanpa spasi).',
        ]);

        $deviceId = $request->device_id;

        $room = Room::create([
            'name' => $request->name,
            'device_id' => $deviceId,
            'floor' => $request->filled('floor') ? trim($request->floor) : null,
        ]);

        $mqttPublished = true;

        try {
            $mqtt = new MqttService;
            $topic = "device/{$deviceId}/config";

            $mqtt->publish(
                $topic,
                json_encode([
                    'room' => $room->name,
                ]),
                1,
                true
            );
        } catch (\Throwable $e) {
            $mqttPublished = false;

            Log::warning('Failed to publish room config to MQTT', [
                'room_id' => $room->id,
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);
        }

        Cache::put("device_status_{$deviceId}", 'offline', 300);
        Cache::forget("device_{$deviceId}_last_seen");

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => '-',
            'activity' => 'add_room',
        ]);

        $message = $mqttPublished
            ? 'Room berhasil ditambahkan'
            : 'Room berhasil ditambahkan, tetapi konfigurasi MQTT gagal dikirim';

        try {
            event(new RoomsChanged('created', $room->name));
        } catch (\Throwable $e) {
            Log::warning('Broadcast RoomsChanged (created) gagal: '.$e->getMessage());
        }

        return redirect('/rooms')->with('success', $message);
    }

    /* === DELETE ROOM === */
    public function destroy(int|string $id)
    {
        $room = Room::with('acUnits:id,room_id,ac_number')->findOrFail($id);

        $deviceId = strtolower(trim((string) $room->device_id));

        $mqttPublished = true;

        try {
            $mqtt = new MqttService;

            $mqtt->publish(
                "device/{$deviceId}/clear",
                json_encode(new \stdClass),
                1,
                false
            );

            foreach ($this->retainedTopicsForDeletedRoom($room, $deviceId) as $topic) {
                $mqtt->clearRetained($topic);
            }
        } catch (\Throwable $e) {
            $mqttPublished = false;

            Log::warning('Failed to publish room clear command to MQTT', [
                'room_id' => $room->id,
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);
        }

        Cache::forget("device_status_{$deviceId}");
        Cache::forget("device_{$deviceId}_last_seen");

        $normalizedRoom = RoomTemperature::normalizeRoomName($room->name);

        $deletedTemps = RoomTemperature::whereRaw("REPLACE(LOWER(TRIM(room)), ' ', '_') = ?", [$normalizedRoom])->delete();
        $deletedNotifs = Notification::where('meta->room', $room->name)
            ->orWhere('meta->room', $normalizedRoom)
            ->delete();

        Cache::forget("notification_state:device:{$normalizedRoom}");
        Cache::forget("notification_state:fuzzy_action:{$normalizedRoom}");
        Cache::forget("room_temp_{$normalizedRoom}");
        Cache::forget("room_temp_status_{$normalizedRoom}");
        foreach (['temperature_offline', 'device_offline', 'recovered'] as $reason) {
            Cache::forget("notification_state:fuzzy_warning:{$normalizedRoom}:{$reason}");
        }
        Cache::forget('known_room_names');

        Log::info('Room cascade cleanup', [
            'room' => $room->name,
            'temperatures_deleted' => $deletedTemps,
            'notifications_deleted' => $deletedNotifs,
        ]);

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => '-',
            'activity' => 'delete_room',
        ]);

        $room->delete();

        $message = $mqttPublished
            ? 'Room berhasil dihapus'
            : 'Room berhasil dihapus, tetapi perintah clear ke MQTT gagal dikirim';

        try {
            event(new RoomsChanged('deleted', $room->name));
        } catch (\Throwable $e) {
            Log::warning('Broadcast RoomsChanged (deleted) gagal: '.$e->getMessage());
        }

        return redirect('/rooms')->with('success', $message);
    }

    /**
     * @return array<int, string>
     */
    private function retainedTopicsForDeletedRoom(Room $room, string $deviceId): array
    {
        $topics = [
            "device/{$deviceId}/config",
            "device/{$deviceId}/clear",
            "device/{$deviceId}/sensor",
            "device/{$deviceId}/status",
        ];

        foreach ($this->roomTopicAliases($room->name) as $roomTopic) {
            $topics[] = "room/{$roomTopic}/sensor";

            foreach ($room->acUnits as $ac) {
                $topics[] = "room/{$roomTopic}/ac/{$ac->ac_number}/control";
                $topics[] = "room/{$roomTopic}/ac/{$ac->ac_number}/status";
                $topics[] = "room/{$roomTopic}/ac/{$ac->ac_number}/timer";
            }
        }

        return array_values(array_unique($topics));
    }

    /**
     * @return array<int, string>
     */
    private function roomTopicAliases(string $roomName): array
    {
        $topic = MqttService::roomToTopic($roomName);

        return array_values(array_unique(array_filter([
            $topic,
            str_replace(' ', '_', $topic),
            str_replace(' ', '-', $topic),
        ])));
    }

    /* === OVERVIEW ALL ROOMS === */
    public function overview()
    {
        $rooms = Room::with(['acUnits.status'])
            ->orderBy('floor')
            ->orderBy('name')
            ->get();
        $latestTemperatures = RoomTemperature::latestByNormalizedRoom();

        $onlineRooms = 0;
        $offlineRooms = 0;

        foreach ($rooms as $room) {
            $roomKey = RoomTemperature::normalizeRoomName($room->name);
            $sensorStatus = Cache::get("room_temp_status_{$roomKey}");
            $lastTempRecord = $latestTemperatures->get($roomKey);
            $room->last_temperature = optional($lastTempRecord)->temperature;
            $room->temperature = $room->last_temperature;

            $deviceId = strtolower(trim((string) $room->device_id));
            $status = Cache::get("device_status_{$deviceId}", $room->device_status ?? 'offline');
            $lastSeen = $this->lastSeenFrom(Cache::get("device_{$deviceId}_last_seen"))
                ?? $this->lastSeenFrom($room->last_seen);

            $isOnline = ($status === 'online' || $status === 'available')
                && $lastSeen
                && now()->diffInSeconds($lastSeen, true) <= Room::onlineThresholdSeconds();

            $room->device_status = $isOnline ? 'online' : 'offline';

            $isOnline ? $onlineRooms++ : $offlineRooms++;

            $room->temperature_is_offline = ! $isOnline || $sensorStatus === 'offline';
            if ($lastTempRecord && $lastTempRecord->created_at) {
                $secondsSinceLastTemp = now()->diffInSeconds($lastTempRecord->created_at, true);
                $room->temperature_is_offline = $room->temperature_is_offline || $secondsSinceLastTemp > Room::temperatureStaleSeconds();
            } elseif (! $lastTempRecord) {
                $room->temperature_is_offline = true;
            }

            if ($room->temperature_is_offline) {
                $room->temperature = null;
            }
        }

        $roomsData = $rooms->map(function (Room $room) {
            $activeCount = $room->acUnits->filter(fn ($ac) => optional($ac->status)->power === 'ON')->count();

            return [
                'id' => $room->id,
                'name' => $room->name,
                'floor' => $room->floor ?: 'Lainnya',
                'device_id' => $room->device_id,
                'device_status' => $room->device_status,
                'temperature' => $room->temperature !== null ? (float) $room->temperature : null,
                'last_temperature' => $room->last_temperature !== null ? (float) $room->last_temperature : null,
                'temperature_is_offline' => (bool) $room->temperature_is_offline,
                'ac_units_count' => $room->acUnits->count(),
                'ac_active_count' => $activeCount,
                'ac_idle_count' => $room->acUnits->count() - $activeCount,
            ];
        })->values();

        return Inertia::render('RoomsOverview', [
            'rooms' => $roomsData,
        ]);
    }

    /* === DETAIL STATUS AC === */
    public function status(int|string $id)
    {
        $room = Room::findOrFail($id);

        // Single room — query only its latest record, not all rooms
        $roomKey = RoomTemperature::normalizeRoomName($room->name);
        $sensorStatus = Cache::get("room_temp_status_{$roomKey}");

        $lastTempRecord = RoomTemperature::where('room', $roomKey)
            ->latest()
            ->first();
        $room->last_temperature = optional($lastTempRecord)->temperature;
        $room->temperature = $room->last_temperature;

        $deviceId = strtolower(trim((string) $room->device_id));
        $status = Cache::get("device_status_{$deviceId}", $room->device_status ?? 'offline');
        $lastSeen = $this->lastSeenFrom(Cache::get("device_{$deviceId}_last_seen"))
            ?? $this->lastSeenFrom($room->last_seen);

        $isOnline = ($status === 'online' || $status === 'available')
            && $lastSeen
            && now()->diffInSeconds($lastSeen, true) <= Room::onlineThresholdSeconds();

        $room->device_status = $isOnline ? 'online' : 'offline';

        $room->temperature_is_offline = ! $isOnline || $sensorStatus === 'offline';
        if ($lastTempRecord && $lastTempRecord->created_at) {
            $secondsSinceLastTemp = now()->diffInSeconds($lastTempRecord->created_at, true);
            $room->temperature_is_offline = $room->temperature_is_offline || $secondsSinceLastTemp > Room::temperatureStaleSeconds();
        } elseif (! $lastTempRecord) {
            $room->temperature_is_offline = true;
        }

        if ($room->temperature_is_offline) {
            $room->temperature = null;
        }

        $acs = AcUnit::with('status')
            ->where('room_id', $id)
            ->orderBy('ac_number')
            ->get();

        $acsData = $acs->map(function (AcUnit $ac) {
            $formatTime = fn ($t) => $t ? Carbon::parse($t)->setTimezone('Asia/Jakarta')->format('H:i') : null;

            return [
                'id' => $ac->id,
                'ac_number' => $ac->ac_number,
                'label' => $ac->name ?: $ac->brand,
                'power' => strtoupper($ac->status?->power ?? 'OFF'),
                'set_temperature' => $ac->status?->set_temperature ?? 24,
                'mode' => strtoupper($ac->status?->mode ?? 'COOL'),
                'fan_speed' => strtoupper($ac->status?->fan_speed ?? 'AUTO'),
                'swing' => strtoupper($ac->status?->swing ?? 'OFF'),
                'timer_on' => $formatTime($ac->timer_on),
                'timer_off' => $formatTime($ac->timer_off),
            ];
        })->values();

        return Inertia::render('RoomDetail', [
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
                'device_status' => $room->device_status,
            ],
            'acs' => $acsData,
        ]);
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
