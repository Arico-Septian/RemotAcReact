<?php

namespace App\Console\Commands;

use App\Models\Room;
use App\Models\RoomTemperature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

/**
 * @deprecated Use MqttSubscribe instead (mqtt:subscribe command)
 *
 * This command is a legacy sensor listener. The MqttSubscribe command now
 * handles all MQTT subscriptions including room/+/sensor, making this
 * command redundant and causing duplicate sensor data if both run.
 *
 * Keep this for backwards compatibility only. Do not run with mqtt:subscribe.
 */
class MqttListener extends Command
{
    protected $signature = 'app:mqtt-listener';

    protected $description = '[DEPRECATED] Use mqtt:subscribe instead. MQTT Listener for temperature data';

    public function handle()
    {
        $server = env('MQTT_HOST', 'broker.hivemq.com');
        $port = (int) env('MQTT_PORT', 1883);

        while (true) {

            try {

                $this->info('Connecting MQTT...');

                $mqtt = new MqttClient($server, $port, 'laravel-listener-' . uniqid());

                $settings = (new ConnectionSettings)
                    ->setUsername(env('MQTT_USERNAME'))
                    ->setPassword(env('MQTT_PASSWORD'))
                    ->setUseTls($port === 8883)
                    ->setTlsSelfSignedAllowed(false)
                    ->setKeepAliveInterval(60);

                $mqtt->connect($settings, true);

                $this->info('Connected to MQTT');

                $mqtt->subscribe('room/+/sensor', function ($topic, $message, $retained) {

                    if (! preg_match('#^room/[^/]+/sensor$#', $topic)) {
                        return;
                    }

                    $data = json_decode($message, true);

                    if (! is_array($data)) {
                        echo "JSON tidak valid: $message\n";

                        return;
                    }

                    if (! isset($data['suhu'], $data['room'])) {
                        echo "Data tidak lengkap\n";

                        return;
                    }

                    $roomKey = RoomTemperature::normalizeRoomName($data['room']);
                    if ($roomKey === '') {
                        echo "Data room kosong\n";

                        return;
                    }

                    $roomModel = Room::whereRaw(
                        'LOWER(TRIM(name)) = ?',
                        [$roomKey]
                    )->first();

                    $normalizedRoom = $roomModel
                        ? RoomTemperature::normalizeRoomName($roomModel->name)
                        : $roomKey;

                    if (! is_numeric($data['suhu'])) {
                        echo "Suhu bukan angka\n";

                        return;
                    }

                    $temperature = (float) $data['suhu'];

                    if ($temperature < 10 || $temperature > 60) {
                        echo "Suhu tidak wajar: $temperature\n";

                        return;
                    }

                    $dupKey = 'dup_' . md5($normalizedRoom . $temperature);
                    if (Cache::has($dupKey)) {
                        return;
                    }
                    Cache::put($dupKey, true, 5);

                    $now = now();
                    $key = "last_temp_{$normalizedRoom}";
                    $last = Cache::get($key);

                    if (! $last || $now->diffInSeconds($last, true) >= 5) {

                        RoomTemperature::create([
                            'room' => $normalizedRoom,
                            'temperature' => $temperature,
                        ]);

                        Cache::put($key, $now, 60);

                        Log::info('Temperature received', [
                            'room' => $normalizedRoom,
                            'temperature' => $temperature,
                        ]);

                        if (app()->environment('local')) {
                            echo "[{$normalizedRoom}] {$temperature} C\n";
                        }
                    }
                }, 1);

                $mqtt->loop(true);
            } catch (\Throwable $e) {

                $this->error('MQTT ERROR: ' . $e->getMessage());

                if (isset($mqtt)) {
                    try {
                        $mqtt->disconnect();
                    } catch (\Throwable $e2) {
                    }
                }

                sleep(3 + rand(0, 2));
            }
        }
    }
}
