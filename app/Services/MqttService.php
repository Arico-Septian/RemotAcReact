<?php

namespace App\Services;

use App\Models\AcStatus;
use App\Models\AcUnit;
use App\Models\Room;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttService
{
    private $mqtt;

    public function __construct(?string $clientIdSuffix = null)
    {
        $server = env('MQTT_HOST', 'broker.hivemq.com');
        $port = (int) env('MQTT_PORT', 1883);

        // Client ID TETAP per role agar reconnect menggantikan koneksi lama
        // (bukan menambah koneksi baru — HiveMQ Cloud free tier batasi jumlah koneksi)
        $clientId = 'laravel_'.($clientIdSuffix ?? 'default');

        $useTls = (int) env('MQTT_PORT', 1883) === 8883;

        $connectionSettings = (new ConnectionSettings)
            ->setUsername(env('MQTT_USERNAME'))
            ->setPassword(env('MQTT_PASSWORD'))
            ->setUseTls($useTls)
            ->setTlsSelfSignedAllowed(true)
            ->setTlsVerifyPeer(false)
            ->setTlsVerifyPeerName(false)
            ->setKeepAliveInterval(15)
            ->setConnectTimeout(15)
            ->setSocketTimeout(20)
            ->setResendTimeout(10);

        $this->mqtt = new MqttClient($server, $port, $clientId);

        $this->mqtt->connect($connectionSettings, true);
    }

    public static function roomToTopic(string $roomName): string
    {
        // Sanitize: lowercase, trim, replace any internal whitespace with underscore
        // so legacy room names with spaces don't break MQTT topics
        return strtolower(preg_replace('/\s+/', '_', trim($roomName)));
    }

    public function publish($topic, $message, $qos = 1, $retain = false)
    {
        $this->mqtt->publish($topic, $message, $qos, $retain);
    }

    public function clearRetained(string $topic, int $qos = 1): void
    {
        $this->publish($topic, '', $qos, true);
    }

    public function subscribe($topic, $callback)
    {
        $this->mqtt->subscribe($topic, $callback);
        $this->mqtt->loop(true);
    }

    public function resendConfig($deviceId)
    {
        $deviceId = strtolower(trim($deviceId));
        $room = Room::whereRaw('LOWER(TRIM(device_id)) = ?', [$deviceId])->first();

        if (! $room) {
            return;
        }

        $acs = AcUnit::where('room_id', $room->id)->get();

        $this->publish(
            "device/{$deviceId}/config",
            json_encode([
                'room' => $room->name,
                'acs' => $acs->map(fn ($ac) => [
                    'id' => (int) $ac->ac_number,
                    'brand' => $ac->brand,
                ]),
            ]),
            1,
            true
        );

        foreach ($acs as $ac) {

            $status = AcStatus::where('ac_unit_id', $ac->id)->first();

            if (! $status) {
                continue;
            }

            $topic = 'room/'.self::roomToTopic($room->name)."/ac/{$ac->ac_number}/control";

            $this->publish(
                $topic,
                json_encode([
                    'power' => $status->power,
                    'mode' => $status->mode,
                    'temp' => (int) ($status->set_temperature ?? 24),
                    'fan_speed' => $status->fan_speed ?? 'AUTO',
                    'swing' => $status->swing ?? 'OFF',
                ]),
                1,
                true
            );
        }

        echo "CONFIG + STATUS DIKIRIM KE {$deviceId}\n";
    }

    public function subscribeMultiple(array $topics, int $idleTimeoutSeconds = 180)
    {
        $lastMessageTime = time();

        // Wrap setiap callback untuk track waktu pesan terakhir
        foreach ($topics as $topic => $callback) {
            $this->mqtt->subscribe($topic, function (...$args) use ($callback, &$lastMessageTime) {
                $lastMessageTime = time();
                $callback(...$args);
            });
        }

        // Loop non-blocking — bisa deteksi idle/stuck connection
        while ($this->mqtt->isConnected()) {
            $this->mqtt->loopOnce(microtime(true), true);

            // Watchdog: kalau tidak ada pesan masuk dalam X detik, anggap koneksi stuck
            // dan trigger reconnect via exception (caught di MqttSubscribe::handle())
            if (time() - $lastMessageTime > $idleTimeoutSeconds) {
                throw new \RuntimeException(
                    "MQTT idle timeout ({$idleTimeoutSeconds}s) - reconnecting"
                );
            }
        }

        throw new \RuntimeException('MQTT connection lost');
    }
}
