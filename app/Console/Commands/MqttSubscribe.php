<?php

namespace App\Console\Commands;

use App\Events\DeviceStatusUpdated;
use App\Events\RaspiTemperatureUpdated;
use App\Events\RoomTemperatureUpdated;
use App\Models\AcStatus;
use App\Models\AcUnit;
use App\Models\Notification;
use App\Models\Room;
use App\Models\RoomTemperature;
use App\Services\MqttService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';

    protected $description = 'MQTT Listener (Realtime IoT)';

    public function handle()
    {
        while (true) {
            try {
                // Client ID khusus subscriber agar tidak konflik dengan publisher (controller)
                $mqtt = new MqttService('subscriber');

                $this->info('MQTT LISTENER STARTED');

                $mqtt->subscribeMultiple([

                    /* === DEVICE ONLINE === */
                    'device/+/online' => function ($topic, $message, $retained = false, $matchedWildcards = []) use ($mqtt) {

                        if ($this->skipRetainedMessage($retained, 'ONLINE', $topic)) {
                            return;
                        }

                        $data = json_decode($message, true);

                        if (! $data || empty($data['device_id'])) {
                            $this->warn('JSON ONLINE tidak valid');

                            return;
                        }

                        $deviceId = $this->normalize($data['device_id']);

                        $this->info("ESP ONLINE: {$deviceId}");

                        $this->setOnline($deviceId);

                        $mqtt->resendConfig($deviceId);
                        Cache::put("device_{$deviceId}_config_sent", true, 300);

                        event(new DeviceStatusUpdated($deviceId, 'online'));
                    },

                    /* === PING === */
                    'device/+/ping' => function ($topic, $message = '', $retained = false, $matchedWildcards = []) use ($mqtt) {

                        if ($this->skipRetainedMessage($retained, 'PING', $topic)) {
                            return;
                        }

                        $deviceId = $this->extractDeviceId($topic, 'ping');
                        if (! $deviceId) {
                            return;
                        }

                        $this->setOnline($deviceId);

                        $this->line("PING: {$deviceId}");

                        // Kirim config jika belum pernah dikirim sejak mqtt:subscribe start
                        if (! Cache::get("device_{$deviceId}_config_sent")) {
                            $mqtt->resendConfig($deviceId);
                            Cache::put("device_{$deviceId}_config_sent", true, 300);
                            $this->info("CONFIG resent via ping -> {$deviceId}");
                        }

                        event(new DeviceStatusUpdated($deviceId, 'online'));
                    },

                    /* === STATUS (LWT) === */
                    'device/+/status' => function ($topic, $message, $retained = false, $matchedWildcards = []) {
                        $deviceId = $this->extractDeviceId($topic, 'status');
                        if (! $deviceId) {
                            return;
                        }

                        if ($message === 'offline') {

                            $this->error("ESP OFFLINE: {$deviceId}");

                            Cache::forget("device_{$deviceId}_last_seen");
                            Cache::put("device_status_{$deviceId}", 'offline', 300);
                            Cache::forget("device_unknown_{$deviceId}");
                            Cache::forget("device_{$deviceId}_config_sent");

                            AcStatus::whereHas('acUnit.room', function ($q) use ($deviceId) {
                                $q->where('device_id', $deviceId);
                            })->update([
                                'power' => 'OFF',
                            ]);
                            Room::where('device_id', $deviceId)->update([
                                'device_status' => 'offline',
                            ]);

                            $room = Room::whereRaw('LOWER(TRIM(device_id)) = ?', [$deviceId])->first();
                            if ($room) {
                                Notification::deviceOffline($room->name, $deviceId);
                            }

                            event(new DeviceStatusUpdated($deviceId, 'offline'));

                            Log::info('Device marked OFFLINE via LWT', ['device' => $deviceId]);
                        } elseif ($message === 'online') {

                            if ($this->skipRetainedMessage($retained, 'STATUS ONLINE', $topic)) {
                                return;
                            }

                            $this->info("STATUS ONLINE: {$deviceId}");

                            $this->setOnline($deviceId);

                            event(new DeviceStatusUpdated($deviceId, 'online'));
                        }
                    },

                    'room/+/ac/+/control' => function ($topic, $message, $retained = false, $matchedWildcards = []) {

                        if ($this->skipRetainedMessage($retained, 'CONTROL', $topic)) {
                            return;
                        }

                        $data = json_decode($message, true);

                        if (! is_array($data)) {
                            $this->warn('CONTROL tidak valid');

                            return;
                        }

                        $parts = explode('/', $topic);
                        $roomName = strtolower($parts[1] ?? '');
                        $acId = $parts[3] ?? null;

                        if (! $roomName || ! $acId) {
                            return;
                        }

                        $room = Room::whereRaw('REPLACE(LOWER(name), " ", "_") = ?', [$roomName])->first();
                        if (! $room) {
                            return;
                        }

                        $ac = AcUnit::where('room_id', $room->id)
                            ->where('ac_number', $acId)
                            ->first();

                        if (! $ac) {
                            return;
                        }

                        $status = AcStatus::firstOrNew(['ac_unit_id' => $ac->id]);

                        $status->fill([
                            'power' => array_key_exists('power', $data)
                                ? $this->normalizePower($data['power'])
                                : $this->normalizePower($status->power ?? 'OFF'),
                            'mode' => array_key_exists('mode', $data)
                                ? $this->normalizeMode($data['mode'])
                                : $this->normalizeMode($status->mode ?? 'COOL'),
                            'set_temperature' => $this->normalizeTemperature(
                                $data['temp'] ?? $data['ac_temp'] ?? $status->set_temperature ?? 24
                            ),
                            'fan_speed' => $this->normalizeFanSpeed(
                                $data['fan_speed'] ?? $status->fan_speed ?? 'AUTO'
                            ),
                            'swing' => $this->normalizeSwing(
                                $data['swing'] ?? $status->swing ?? 'OFF'
                            ),
                        ])->save();

                        $this->info("AC {$acId} di {$roomName} diupdate");
                    },

                    'room/+/ac/+/status' => function ($topic, $message, $retained = false, $matchedWildcards = []) {

                        try {

                            if ($this->skipRetainedMessage($retained, 'AC STATUS', $topic)) {
                                return;
                            }

                            $data = json_decode($message, true);

                            if (! is_array($data)) {
                                Log::warning('MQTT STATUS INVALID JSON', [
                                    'topic' => $topic,
                                    'message' => $message,
                                ]);

                                return;
                            }

                            $parts = explode('/', $topic);

                            if (count($parts) < 5) {
                                Log::warning('MQTT TOPIC INVALID', [
                                    'topic' => $topic,
                                ]);

                                return;
                            }

                            $roomName = strtolower(trim($parts[1]));
                            $acNumber = (int) $parts[3];

                            if (! $roomName || ! $acNumber) {
                                Log::warning('MQTT DATA TIDAK LENGKAP', compact('topic'));

                                return;
                            }

                            $room = Room::whereRaw('REPLACE(LOWER(name), " ", "_") = ?', [$roomName])->first();

                            if (! $room) {
                                Log::warning('ROOM TIDAK DITEMUKAN', [
                                    'room' => $roomName,
                                ]);

                                return;
                            }

                            $ac = AcUnit::where('room_id', $room->id)
                                ->where('ac_number', $acNumber)
                                ->first();

                            if (! $ac) {
                                Log::warning('AC TIDAK TERDAFTAR (DIABAIKAN)', [
                                    'room' => $roomName,
                                    'ac_number' => $acNumber,
                                ]);

                                return;
                            }

                            $status = AcStatus::firstOrNew(['ac_unit_id' => $ac->id]);

                            $power = array_key_exists('power', $data)
                                ? $this->normalizePower($data['power'])
                                : $this->normalizePower($status->power ?? 'OFF');
                            $mode = array_key_exists('mode', $data)
                                ? $this->normalizeMode($data['mode'])
                                : $this->normalizeMode($status->mode ?? 'COOL');
                            $temp = $this->normalizeTemperature(
                                $data['ac_temp'] ?? $data['temp'] ?? $status->set_temperature ?? 24
                            );
                            $fanSpeed = $this->normalizeFanSpeed(
                                $data['fan_speed'] ?? $status->fan_speed ?? 'AUTO'
                            );
                            $swing = $this->normalizeSwing(
                                $data['swing'] ?? $status->swing ?? 'OFF'
                            );

                            $status->fill([
                                'power' => $power,
                                'mode' => $mode,
                                'set_temperature' => $temp,
                                'fan_speed' => $fanSpeed,
                                'swing' => $swing,
                            ])->save();

                            Log::info('MQTT STATUS UPDATED', [
                                'room' => $roomName,
                                'ac' => $acNumber,
                                'power' => $power,
                                'mode' => $mode,
                                'temp' => $temp,
                                'fan_speed' => $fanSpeed,
                                'swing' => $swing,
                            ]);
                        } catch (\Throwable $e) {

                            Log::error('MQTT STATUS ERROR', [
                                'topic' => $topic,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    },

                    /* === RASPI TEMPERATURE === */
                    'raspi/temperature' => function ($topic, $message) {
                        $temp = (float) trim($message);
                        if ($temp > 0) {
                            Cache::put('raspi_temperature', $temp, 300);
                            $this->line("RASPI TEMP: {$temp}°C");
                            event(new RaspiTemperatureUpdated($temp));
                        }
                    },

                    /* === ROOM SENSOR (DHT from ESP32 per ruangan) === */
                    'room/+/sensor' => function ($topic, $message, $retained = false, $matchedWildcards = []) {
                        if ($this->skipRetainedMessage($retained, 'ROOM SENSOR', $topic)) {
                            return;
                        }

                        $data = json_decode($message, true);
                        if (! is_array($data)) {
                            $this->error("ROOM SENSOR: payload bukan JSON valid → {$message}");

                            return;
                        }

                        $room = $data['room'] ?? null;
                        $temp = $data['suhu'] ?? $data['temperature'] ?? null;
                        $sensorStatus = $this->sensorStatusFrom($data);

                        // fallback ambil room dari topic kalau tidak ada di payload
                        if (! $room) {
                            $parts = explode('/', $topic);
                            $room = $parts[1] ?? null;
                        }

                        if (! $room) {
                            $this->error('ROOM SENSOR: room kosong');

                            return;
                        }

                        $room = RoomTemperature::normalizeRoomName($room);

                        // Drop pesan dari ruangan yang tidak terdaftar di DB
                        $knownRooms = Cache::remember('known_room_names', 30, fn () => Room::pluck('name')
                            ->map(RoomTemperature::normalizeRoomName(...))
                            ->all());

                        if (! \in_array($room, $knownRooms, true)) {
                            $this->warn("ROOM SENSOR [{$room}]: room tidak terdaftar di DB, di-drop");

                            return;
                        }

                        if ($sensorStatus === 'offline') {
                            $this->markRoomTemperatureOffline($room);

                            return;
                        }

                        if ($temp === null) {
                            $this->error('ROOM SENSOR: suhu kosong');

                            return;
                        }

                        $temp = (float) $temp;

                        if ($temp <= 0 || $temp > 100) {
                            $this->error("ROOM SENSOR {$room}: suhu out of range ({$temp})");

                            return;
                        }

                        RoomTemperature::create([
                            'room' => $room,
                            'temperature' => $temp,
                        ]);

                        Cache::put("room_temp_{$room}", $temp, 300);
                        Cache::put("room_temp_status_{$room}", 'online', 300);
                        $this->line("ROOM TEMP [{$room}]: {$temp}°C");

                        event(new RoomTemperatureUpdated($room, $temp));
                    },

                    /* === HEARTBEAT === */
                    'device/+/heartbeat' => function ($topic, $message = '', $retained = false, $matchedWildcards = []) {

                        if ($this->skipRetainedMessage($retained, 'HEARTBEAT', $topic)) {
                            return;
                        }

                        $deviceId = $this->extractDeviceId($topic, 'heartbeat');
                        if (! $deviceId) {
                            return;
                        }

                        $this->setOnline($deviceId);
                    },

                    /* === DEVICE SENSOR (DHT per device, fallback kalau config belum diterima) === */
                    'device/+/sensor' => function ($topic, $message, $retained = false, $matchedWildcards = []) {
                        if ($this->skipRetainedMessage($retained, 'DEVICE SENSOR', $topic)) {
                            return;
                        }

                        $data = json_decode($message, true);
                        if (! is_array($data)) {
                            return;
                        }

                        $deviceId = $this->extractDeviceId($topic, 'sensor');
                        if (! $deviceId) {
                            return;
                        }

                        // Cari room berdasarkan device_id
                        $room = Room::whereRaw('LOWER(TRIM(device_id)) = ?', [$deviceId])->first();
                        if (! $room) {
                            $this->warn("DEVICE SENSOR [{$deviceId}]: tidak ada room dengan device_id ini");

                            return;
                        }

                        $roomName = RoomTemperature::normalizeRoomName($room->name);
                        $sensorStatus = $this->sensorStatusFrom($data);

                        if ($sensorStatus === 'offline') {
                            $this->markRoomTemperatureOffline($roomName);
                            $this->setOnline($deviceId);

                            return;
                        }

                        $temp = $data['suhu'] ?? $data['temperature'] ?? null;
                        if ($temp === null) {
                            return;
                        }

                        $temp = (float) $temp;
                        if ($temp <= 0 || $temp > 100) {
                            return;
                        }

                        RoomTemperature::create([
                            'room' => $roomName,
                            'temperature' => $temp,
                        ]);

                        Cache::put("room_temp_{$roomName}", $temp, 300);
                        Cache::put("room_temp_status_{$roomName}", 'online', 300);

                        // Sensor masuk = device pasti online (sekalian update last_seen)
                        $this->setOnline($deviceId);

                        $this->line("DEVICE SENSOR [{$deviceId}/{$roomName}]: {$temp}°C");

                        event(new RoomTemperatureUpdated($roomName, $temp));
                    },

                    /* === ADD AC === */
                    'room/+/ac/add' => function ($topic, $message, $retained = false, $matchedWildcards = []) {

                        if ($this->skipRetainedMessage($retained, 'AC ADD', $topic)) {
                            return;
                        }

                        $data = json_decode($message, true);

                        if (! $data || empty($data['id'])) {
                            $this->warn('AC ADD tidak valid');

                            return;
                        }

                        $parts = explode('/', $topic);
                        $roomName = strtolower(trim($parts[1] ?? ''));

                        if (! $roomName) {
                            return;
                        }

                        $room = Room::whereRaw('REPLACE(LOWER(name), " ", "_") = ?', [$roomName])->first();
                        if (! $room || ! $room->device_id) {
                            return;
                        }

                        $acNumber = (int) $data['id'];

                        if ($acNumber < 1 || $acNumber > 15) {
                            $this->warn("Nomor AC tidak valid: {$acNumber}");

                            return;
                        }

                        $ac = AcUnit::firstOrCreate(
                            [
                                'room_id' => $room->id,
                                'ac_number' => $acNumber,
                            ],
                            [
                                'name' => "AC {$acNumber}",
                                'brand' => $data['brand'] ?? 'Unknown',
                            ]
                        );

                        AcStatus::firstOrCreate(
                            ['ac_unit_id' => $ac->id],
                            [
                                'power' => 'OFF',
                                'mode' => 'COOL',
                                'set_temperature' => 24,
                                'fan_speed' => 'AUTO',
                                'swing' => 'OFF',
                            ]
                        );

                        $this->info("AC ditambahkan ke {$roomName}");
                    },

                ]);
            } catch (\Throwable $e) {
                Log::error('MQTT SUBSCRIBER ERROR', [
                    'error' => $e->getMessage(),
                ]);

                $this->error('MQTT subscriber error: '.$e->getMessage());
                $this->line('Retrying in 5 seconds...');

                sleep(5);
            }
        }
    }

    /* === HELPER: SET ONLINE === */
    private function setOnline($deviceId)
    {
        $deviceId = $this->normalize($deviceId);
        $now = now();

        Cache::put("device_{$deviceId}_last_seen", $now->toDateTimeString(), 300);
        Cache::put("device_status_{$deviceId}", 'online', 120);
        Cache::forget("device_unknown_{$deviceId}");

        // Track semua device yang pernah ping (untuk `device:list-active`)
        $seen = Cache::get('seen_device_ids', []);
        if (! \in_array($deviceId, $seen, true)) {
            $seen[] = $deviceId;
            Cache::put('seen_device_ids', $seen, 3600);
        }

        $updated = Room::whereRaw(
            'LOWER(TRIM(device_id)) = ?',
            [strtolower(trim($deviceId))]
        )->update([
            'device_status' => 'online',
            'last_seen' => $now,
        ]);

        $this->info("PING {$deviceId} -> DB updated rows: {$updated}");
    }

    private function skipRetainedMessage(bool $retained, string $label, string $topic): bool
    {
        if (! $retained) {
            return false;
        }

        if ($label === 'CONTROL') {
            return true;
        }

        $this->warn("{$label} retained di-skip ({$topic})");

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function sensorStatusFrom(array $data): ?string
    {
        $status = strtolower(trim((string) ($data['sensor_status'] ?? $data['status'] ?? '')));

        return $status !== '' ? $status : null;
    }

    private function markRoomTemperatureOffline(string $room): void
    {
        $room = RoomTemperature::normalizeRoomName($room);
        Cache::put("room_temp_status_{$room}", 'offline', 300);

        $lastTemp = RoomTemperature::where('room', $room)
            ->latest()
            ->value('temperature');

        $this->warn("ROOM SENSOR [{$room}]: sensor offline");

        event(new RoomTemperatureUpdated($room, (float) ($lastTemp ?? 0)));
    }

    /* === HELPER: EXTRACT DEVICE ID === */
    private function extractDeviceId($topic, $type)
    {
        if (! preg_match("/device\/(.+)\/{$type}/", $topic, $matches)) {
            return null;
        }

        return $this->normalize($matches[1]);
    }

    /* === HELPER: NORMALIZE ID === */
    private function normalize($value)
    {
        return strtolower(trim($value));
    }

    private function normalizePower($value)
    {
        $power = strtoupper(trim((string) $value));

        return in_array($power, ['ON', 'OFF'], true) ? $power : 'OFF';
    }

    private function normalizeMode($value)
    {
        $mode = strtoupper(trim((string) $value));

        return in_array($mode, ['COOL', 'HEAT', 'DRY', 'FAN'], true) ? $mode : 'COOL';
    }

    private function normalizeTemperature($value)
    {
        $temperature = (int) $value;

        return min(30, max(16, $temperature ?: 24));
    }

    private function normalizeFanSpeed($value)
    {
        $fanSpeed = strtoupper(trim((string) $value));

        return in_array($fanSpeed, ['AUTO', 'LOW', 'MEDIUM', 'HIGH'], true) ? $fanSpeed : 'AUTO';
    }

    private function normalizeSwing($value)
    {
        $swing = strtoupper(trim((string) $value));

        return in_array($swing, ['OFF', 'FULL', 'HALF', 'DOWN'], true) ? $swing : 'OFF';
    }
}
