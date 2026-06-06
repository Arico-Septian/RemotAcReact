<?php

namespace App\Http\Controllers;

use App\Models\AcStatus;
use App\Models\AcUnit;
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

class AcUnitController extends Controller
{
    public function index(int|string $id)
    {
        $room = Room::with(['acUnits.status'])->findOrFail($id);

        $this->setCurrentDeviceStatus($room);

        $acs = AcUnit::with('status')
            ->where('room_id', $id)
            ->get();

        $fuzzyService = new FuzzyMamdaniService;

        $normalized = RoomTemperature::normalizeRoomName($room->name);

        $tempHistory = RoomTemperature::where('room', $normalized)
            ->latest()
            ->take(2)
            ->get();

        $latestTempRecord = $tempHistory->first();
        $isFresh = $latestTempRecord
            && $latestTempRecord->created_at
            && now()->diffInSeconds($latestTempRecord->created_at, true) <= Room::TEMPERATURE_STALE_SECONDS;
        $currentTemp = $isFresh ? $latestTempRecord->temperature : null;

        if ($currentTemp !== null) {
            $deltaT = $this->calculateDeltaT($tempHistory);

            $room->temperature = round($currentTemp, 1);

            $room->delta_t = round($deltaT, 2);

            $fuzzyResult = $fuzzyService->calculate(
                $currentTemp,
                $deltaT
            );

            $room->fuzzy = $fuzzyResult;

            $currentSetpoint = (int) round(
                $room->acUnits
                    ->map(fn ($ac) => $ac->status?->set_temperature ?? 24)
                    ->avg()
            );

            $room->decision = $fuzzyService->decideAction(
                $fuzzyResult,
                $currentSetpoint
            );
        } else {

            $room->temperature = null;
            $room->delta_t = 0;
            $room->fuzzy = null;
            $room->decision = null;
        }

        return view('ac.index', compact('room', 'acs'));
    }

    public function store(Request $request, int|string $roomId)
    {
        $room = Room::findOrFail($roomId);

        if ($room->acUnits()->count() >= 15) {
            return back()->with('error', 'Maksimal 15 AC per ruangan');
        }

        $request->merge([
            'name' => strtolower(trim((string) $request->name)),
            'brand' => strtolower(trim((string) $request->brand)),
        ]);

        $request->validate([
            'name' => 'required|string|max:50|regex:/^\S*$/u',
            'brand' => 'required|string|max:50|regex:/^\S*$/u',
            'ac_number' => [
                'required',
                'integer',
                'min:1',
                'max:15',
                Rule::unique('ac_units')->where(fn ($q) => $q->where('room_id', $roomId)),
            ],
        ], [
            'name.regex' => 'Nama AC tidak boleh mengandung spasi.',
            'brand.regex' => 'Brand tidak boleh mengandung spasi.',
            'ac_number.unique' => 'Nomor AC ini sudah terdaftar di ruangan ini.',
        ]);

        $ac = AcUnit::create([
            'name' => $request->name,
            'room_id' => $roomId,
            'brand' => $request->brand,
            'ac_number' => $request->ac_number,
        ]);

        AcStatus::create([
            'ac_unit_id' => $ac->id,
            'power' => 'OFF',
            'mode' => 'COOL',
            'set_temperature' => 24,
            'fan_speed' => 'AUTO',
            'swing' => 'OFF',
        ]);

        $mqttSynced = $this->syncRoomConfig($room);

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => 'AC '.$ac->ac_number,
            'activity' => 'add_ac',
        ]);

        $response = back()->with('new_ac_id', $ac->id);

        return $mqttSynced
            ? $response
            : $response->with('warning', 'AC berhasil ditambahkan, tetapi konfigurasi MQTT gagal dikirim ke perangkat.');
    }

    public function update(Request $request, int|string $id)
    {
        $ac = AcUnit::findOrFail($id);
        $room = Room::findOrFail($ac->room_id);

        $request->merge([
            'name' => strtolower(trim((string) $request->name)),
            'brand' => strtolower(trim((string) $request->brand)),
        ]);

        $request->validate([
            'name' => 'required|string|max:50|regex:/^\S*$/u',
            'brand' => 'required|string|max:50|regex:/^\S*$/u',
            'ac_number' => [
                'required',
                'integer',
                'min:1',
                'max:15',
                Rule::unique('ac_units')
                    ->where(fn ($q) => $q->where('room_id', $ac->room_id))
                    ->ignore($ac->id),
            ],
        ], [
            'name.regex' => 'Nama AC tidak boleh mengandung spasi.',
            'brand.regex' => 'Brand tidak boleh mengandung spasi.',
        ]);

        $ac->update([
            'name' => $request->name,
            'brand' => $request->brand,
            'ac_number' => $request->ac_number,
        ]);

        $mqttSynced = $this->syncRoomConfig($room);

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => 'AC '.$ac->ac_number.($ac->name ? ' '.$ac->name : ''),
            'activity' => 'edit_ac',
        ]);

        return $mqttSynced
            ? back()->with('success', 'AC unit berhasil diperbarui')
            : back()->with('warning', 'AC unit berhasil diperbarui, tetapi konfigurasi MQTT gagal dikirim ke perangkat.');
    }

    public function destroy(int|string $id)
    {
        $ac = AcUnit::findOrFail($id);

        $room = Room::findOrFail($ac->room_id);

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => 'AC '.$ac->ac_number,
            'activity' => 'delete_ac',
        ]);

        $room_id = $ac->room_id;
        $acNumber = $ac->ac_number;
        $roomTopic = MqttService::roomToTopic($room->name);

        $ac->delete();

        $mqttSynced = $this->clearDeletedAcMqtt($room, $roomTopic, (int) $acNumber);

        return $mqttSynced
            ? redirect('/rooms/'.$room_id.'/ac')->with('success', 'AC unit berhasil dihapus')
            : redirect('/rooms/'.$room_id.'/ac')->with('warning', 'AC unit berhasil dihapus, tetapi sinkronisasi MQTT gagal.');
    }

    private function syncRoomConfig(Room $room): bool
    {
        try {
            (new MqttService)->resendConfig($room->device_id);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to resend AC room config to MQTT', [
                'room_id' => $room->id,
                'device_id' => $room->device_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function clearDeletedAcMqtt(Room $room, string $roomTopic, int $acNumber): bool
    {
        try {
            $mqtt = new MqttService;

            $mqtt->clearRetained("room/{$roomTopic}/ac/{$acNumber}/control");
            $mqtt->clearRetained("room/{$roomTopic}/ac/{$acNumber}/status");
            $mqtt->clearRetained("room/{$roomTopic}/ac/{$acNumber}/timer");
            $mqtt->resendConfig($room->device_id);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to clear deleted AC MQTT retained state', [
                'room_id' => $room->id,
                'device_id' => $room->device_id,
                'ac_number' => $acNumber,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function applyFuzzy(int|string $id)
    {
        $room = Room::with(['acUnits.status'])->findOrFail($id);

        $fuzzyService = new FuzzyMamdaniService;

        $normalized = RoomTemperature::normalizeRoomName($room->name);

        $tempHistory = RoomTemperature::where('room', $normalized)
            ->latest()
            ->take(2)
            ->get();

        $currentTemp = $tempHistory->first()?->temperature;

        if ($currentTemp === null) {
            return back()->with('error', 'Data suhu belum tersedia');
        }

        $deltaT = $this->calculateDeltaT($tempHistory);

        $fuzzyResult = $fuzzyService->calculate(
            $currentTemp,
            $deltaT
        );

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

        $targetSetpoint = (int) (
            $decision['setpoint_after'] ?? $currentSetpoint
        );

        $cooldownKey = 'fuzzy_room_'.$room->id;

        if (Cache::has($cooldownKey)) {
            return back()->with(
                'warning',
                'Cooldown fuzzy masih aktif'
            );
        }

        $acController = new AcControlController;

        if ($activeAcUnits->isEmpty()) {
            return back()->with('warning', 'Tidak ada AC yang aktif');
        }

        Cache::put($cooldownKey, true, 30);

        foreach ($activeAcUnits as $ac) {
            $acController->fuzzySetTemp(
                $ac,
                $targetSetpoint
            );
        }

        return back()->with(
            'success',
            'Fuzzy berhasil diterapkan ke semua AC aktif'
        );
    }

    private function calculateDeltaT(\Illuminate\Database\Eloquent\Collection $tempHistory): float
    {
        if ($tempHistory->count() < 2) {
            return 0;
        }

        $currentTemp = $tempHistory->first()?->temperature;
        $previousTemp = $tempHistory[1]->temperature;

        if ($currentTemp === null || $previousTemp === null) {
            return 0;
        }

        $currentCreatedAt = $tempHistory->first()->created_at;
        $previousCreatedAt = $tempHistory[1]->created_at;

        $timeDiffSeconds = max(1, $currentCreatedAt->diffInSeconds($previousCreatedAt));

        if ($timeDiffSeconds > 300) {
            return 0;
        }

        // Normalisasi menjadi perubahan derajat per menit
        return ($currentTemp - $previousTemp) / ($timeDiffSeconds / 60);
    }

    private function setCurrentDeviceStatus(Room $room): void
    {
        $deviceId = strtolower(trim((string) $room->device_id));

        if ($deviceId === '') {
            $room->device_status = 'offline';

            return;
        }

        $status = Cache::get("device_status_{$deviceId}", $room->device_status ?? 'offline');
        $lastSeen = $this->lastSeenFrom(Cache::get("device_{$deviceId}_last_seen"))
            ?? $this->lastSeenFrom($room->last_seen);

        $isOnline = ($status === 'online' || $status === 'available')
            && $lastSeen
            && now()->diffInSeconds($lastSeen, true) <= Room::ONLINE_THRESHOLD_SECONDS;

        $room->device_status = $isOnline ? 'online' : 'offline';
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
