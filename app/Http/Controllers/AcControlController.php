<?php

namespace App\Http\Controllers;

use App\Models\AcStatus;
use App\Models\AcUnit;
use App\Models\Room;
use App\Models\UserLog;
use App\Services\MqttService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AcControlController extends Controller
{
    private const MODES = ['COOL', 'HEAT', 'DRY', 'FAN'];

    private const FAN_SPEEDS = ['AUTO', 'LOW', 'MEDIUM', 'HIGH'];

    private const SWINGS = ['OFF', 'FULL', 'HALF', 'DOWN'];

    private $mqtt;

    public function __construct()
    {
        $this->mqtt = null;
    }

    private function statusFor(AcUnit $ac): AcStatus
    {
        return AcStatus::firstOrCreate(
            ['ac_unit_id' => $ac->id],
            [
                'power' => 'OFF',
                'mode' => 'COOL',
                'set_temperature' => 24,
                'fan_speed' => 'AUTO',
                'swing' => 'OFF',
            ]
        );
    }

    private function normalizeTemperature($value): int
    {
        return min(30, max(16, (int) $value ?: 24));
    }

    private function normalizeMode($mode): string
    {
        $mode = strtoupper(trim((string) $mode));

        abort_unless(in_array($mode, self::MODES, true), 422, 'Mode AC tidak valid');

        return $mode;
    }

    private function normalizePower($power): string
    {
        $power = strtoupper(trim((string) $power));

        abort_unless(in_array($power, ['ON', 'OFF'], true), 422, 'Power AC tidak valid');

        return $power;
    }

    private function normalizeFanSpeed($fanSpeed): string
    {
        $fanSpeed = strtoupper(trim((string) $fanSpeed));

        abort_unless(in_array($fanSpeed, self::FAN_SPEEDS, true), 422, 'Fan speed AC tidak valid');

        return $fanSpeed;
    }

    private function normalizeSwing($swing): string
    {
        $swing = strtoupper(trim((string) $swing));

        abort_unless(in_array($swing, self::SWINGS, true), 422, 'Swing AC tidak valid');

        return $swing;
    }

    private function sendFullState(AcUnit $ac, Room $room, AcStatus $status): bool
    {
        try {
            $this->mqtt ??= new MqttService;

            $this->mqtt->publish(
                'room/'.MqttService::roomToTopic($room->name)."/ac/{$ac->ac_number}/control",
                json_encode([
                    'power' => $status->power ?? 'OFF',
                    'mode' => $status->mode ?? 'COOL',
                    'temp' => $this->normalizeTemperature($status->set_temperature ?? 24),
                    'fan_speed' => $status->fan_speed ?? 'AUTO',
                    'swing' => $status->swing ?? 'OFF',
                ]),
                1,
                true
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('MQTT AC control publish failed', [
                'ac_unit_id' => $ac->id,
                'room_id' => $room->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function powerOn($id)
    {
        $ac = AcUnit::findOrFail($id);
        $room = Room::findOrFail($ac->room_id);

        $status = $this->statusFor($ac);

        $status->power = 'ON';
        $status->save();

        $sent = $this->sendFullState($ac, $room, $status);

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => 'AC '.$ac->ac_number.($ac->name ? ' '.$ac->name : ''),
            'activity' => 'on',
        ]);

        return $sent ? back() : back()->with('warning', 'AC diperbarui, tetapi perintah gagal terkirim ke perangkat.');
    }

    public function powerOff($id)
    {
        $ac = AcUnit::findOrFail($id);
        $room = Room::findOrFail($ac->room_id);

        $status = $this->statusFor($ac);

        $status->power = 'OFF';
        $status->save();

        $sent = $this->sendFullState($ac, $room, $status);

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => 'AC '.$ac->ac_number.($ac->name ? ' '.$ac->name : ''),
            'activity' => 'off',
        ]);

        return $sent ? back() : back()->with('warning', 'AC diperbarui, tetapi perintah gagal terkirim ke perangkat.');
    }

    public function setTemp($id, $value)
    {
        $ac = AcUnit::findOrFail($id);
        $room = Room::findOrFail($ac->room_id);

        $value = $this->normalizeTemperature($value);
        $status = $this->statusFor($ac);

        $status->set_temperature = $value;
        $status->save();

        $sent = $this->sendFullState($ac, $room, $status);

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => 'AC '.$ac->ac_number.($ac->name ? ' '.$ac->name : ''),
            'activity' => 'set_temp_'.$value,
        ]);

        return $sent ? back() : back()->with('warning', 'AC diperbarui, tetapi perintah gagal terkirim ke perangkat.');
    }

    public function fuzzySetTemp(AcUnit $ac, int $targetTemp): bool
    {
        $room = Room::findOrFail($ac->room_id);

        $status = $this->statusFor($ac);

        $targetTemp = $this->normalizeTemperature($targetTemp);

        if ((int) $status->set_temperature === $targetTemp) {
            return true;
        }

        $status->set_temperature = $targetTemp;
        $status->save();

        return $this->sendFullState($ac, $room, $status);
    }

    public function setMode($id, $mode)
    {
        $ac = AcUnit::findOrFail($id);
        $room = Room::findOrFail($ac->room_id);

        $mode = $this->normalizeMode($mode);
        $status = $this->statusFor($ac);

        $status->mode = $mode;
        $status->save();

        $sent = $this->sendFullState($ac, $room, $status);

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => 'AC '.$ac->ac_number.($ac->name ? ' '.$ac->name : ''),
            'activity' => 'mode_'.$mode,
        ]);

        return $sent ? back() : back()->with('warning', 'AC diperbarui, tetapi perintah gagal terkirim ke perangkat.');
    }

    public function setFanSpeed($id, $speed)
    {
        $ac = AcUnit::findOrFail($id);
        $room = Room::findOrFail($ac->room_id);

        $speed = $this->normalizeFanSpeed($speed);
        $status = $this->statusFor($ac);

        $status->fan_speed = $speed;
        $status->save();

        $sent = $this->sendFullState($ac, $room, $status);

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => 'AC '.$ac->ac_number.($ac->name ? ' '.$ac->name : ''),
            'activity' => 'fan_speed_'.$speed,
        ]);

        return $sent ? back() : back()->with('warning', 'AC diperbarui, tetapi perintah gagal terkirim ke perangkat.');
    }

    public function setSwing($id, $swing)
    {
        $ac = AcUnit::findOrFail($id);
        $room = Room::findOrFail($ac->room_id);

        $swing = $this->normalizeSwing($swing);
        $status = $this->statusFor($ac);

        $status->swing = $swing;
        $status->save();

        $sent = $this->sendFullState($ac, $room, $status);

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => 'AC '.$ac->ac_number.($ac->name ? ' '.$ac->name : ''),
            'activity' => 'swing_'.$swing,
        ]);

        return $sent ? back() : back()->with('warning', 'AC diperbarui, tetapi perintah gagal terkirim ke perangkat.');
    }

    public function togglePower(Request $request, $id)
    {
        $ac = AcUnit::findOrFail($id);
        $room = Room::findOrFail($ac->room_id);

        $status = $this->statusFor($ac);

        $status->power = $request->filled('power')
            ? $this->normalizePower($request->input('power'))
            : (($status->power === 'ON') ? 'OFF' : 'ON');
        $status->save();

        $sent = $this->sendFullState($ac, $room, $status);

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => 'AC '.$ac->ac_number.($ac->name ? ' '.$ac->name : ''),
            'activity' => strtolower($status->power),
        ]);

        return $sent ? back() : back()->with('warning', 'AC diperbarui, tetapi perintah gagal terkirim ke perangkat.');
    }

    public function bulkPower(Request $request, $roomId)
    {
        $power = $this->normalizePower($request->input('power'));
        $room = Room::findOrFail($roomId);
        $acs = AcUnit::where('room_id', $roomId)->get();

        $allSent = true;
        foreach ($acs as $ac) {
            $status = $this->statusFor($ac);
            $status->power = $power;
            $status->save();
            $allSent = $this->sendFullState($ac, $room, $status) && $allSent;
        }

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => 'Semua AC ('.$acs->count().' unit)',
            'activity' => $power === 'ON' ? 'bulk_on' : 'bulk_off',
        ]);

        $action = $power === 'ON' ? 'dinyalakan' : 'dimatikan';

        return $allSent
            ? back()->with('success', "Semua AC di {$room->name} berhasil {$action}")
            : back()->with('warning', "Semua AC di {$room->name} {$action}, tetapi sebagian perintah gagal terkirim ke perangkat.");
    }

    public function control(Request $request, $id)
    {
        $ac = AcUnit::findOrFail($id);
        $room = Room::findOrFail($ac->room_id);

        $status = $this->statusFor($ac);

        $power = $this->normalizePower($request->input('power', $status->power));
        $mode = $this->normalizeMode($request->input('mode', $status->mode));
        $temp = $this->normalizeTemperature($request->input('temp', $status->set_temperature));
        $fanSpeed = $this->normalizeFanSpeed($request->input('fan_speed', $status->fan_speed ?? 'AUTO'));
        $swing = $this->normalizeSwing($request->input('swing', $status->swing ?? 'OFF'));

        $status->update([
            'power' => $power,
            'mode' => $mode,
            'set_temperature' => $temp,
            'fan_speed' => $fanSpeed,
            'swing' => $swing,
        ]);

        $this->sendFullState($ac, $room, $status);

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => $room->name,
            'ac' => 'AC '.$ac->ac_number,
            'activity' => 'control_ac',
        ]);

        return response()->json(['success' => true]);
    }
}
