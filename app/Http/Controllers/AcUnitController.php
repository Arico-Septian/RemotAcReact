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
use Illuminate\Validation\Rule;

class AcUnitController extends Controller
{
    public function index($id)
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

        $currentTemp = $tempHistory->first()?->temperature;

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

    public function store(Request $request, $roomId)
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

        (new MqttService)->resendConfig($room->device_id);

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => 'AC '.$ac->ac_number,
            'activity' => 'add_ac',
        ]);

        return back()->with('new_ac_id', $ac->id);
    }

    public function update(Request $request, $id)
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

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => 'AC '.$ac->ac_number.($ac->name ? ' '.$ac->name : ''),
            'activity' => 'edit_ac',
        ]);

        return back()->with('success', 'AC unit berhasil diperbarui');
    }

    public function destroy($id)
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

        $mqtt = new MqttService;

        $mqtt->clearRetained("room/{$roomTopic}/ac/{$acNumber}/control");
        $mqtt->clearRetained("room/{$roomTopic}/ac/{$acNumber}/status");
        $mqtt->clearRetained("room/{$roomTopic}/ac/{$acNumber}/timer");
        $mqtt->resendConfig($room->device_id);

        return redirect('/rooms/'.$room_id.'/ac');
    }

    public function applyFuzzy($id)
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

        $currentSetpoint = (int) round(
            $room->acUnits
                ->map(fn ($ac) => $ac->status?->set_temperature ?? 24)
                ->avg()
        );

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

        Cache::put($cooldownKey, true, 30);

        $acController = new AcControlController;

        foreach ($room->acUnits as $ac) {

            $acController->fuzzySetTemp(
                $ac,
                $targetSetpoint
            );
        }

        return back()->with(
            'success',
            'Fuzzy berhasil diterapkan ke semua AC'
        );
    }

    private function calculateDeltaT($tempHistory): float
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
            && now()->diffInSeconds($lastSeen, true) <= 300;

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

    public function schedule(Request $request, $id)
    {
        $request->validate([
            'timer_on' => 'nullable|date_format:H:i',
            'timer_off' => 'nullable|date_format:H:i',
        ]);

        // OFF & ON boleh kapan saja (termasuk cross-midnight). Yang dilarang hanya identik.
        if ($request->timer_on && $request->timer_off && $request->timer_on === $request->timer_off) {
            return back()->withErrors([
                'Timer ON dan OFF tidak boleh sama',
            ])->withInput();
        }

        $ac = AcUnit::findOrFail($id);

        // UX: Cek jika data tidak berubah untuk menghemat resource
        if ($ac->timer_on === $request->timer_on && $ac->timer_off === $request->timer_off) {
            return back();
        }

        $ac->update([
            'timer_on' => $request->timer_on,
            'timer_off' => $request->timer_off,
        ]);

        // Security & Sync: Kirim ke MQTT agar ESP32 tahu ada perubahan timer
        try {
            $mqtt = new MqttService;
            $room = Room::findOrFail($ac->room_id);
            $topic = 'room/'.MqttService::roomToTopic($room->name)."/ac/{$ac->ac_number}/timer";

            if (!$request->timer_on && !$request->timer_off) {
                $mqtt->clearRetained($topic);
            } else {
                $mqtt->publish($topic, json_encode([
                    'timer_on' => $request->timer_on,
                    'timer_off' => $request->timer_off,
                ]), 1, true);
            }
        } catch (\Throwable $e) {
            \Log::warning('MQTT Timer sync failed in AcUnitController', [
                'ac_id' => $ac->id,
                'error' => $e->getMessage()
            ]);
        }

        return back()->with('success', 'Timer disimpan');
    }
}
