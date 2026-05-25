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
    private bool $isSubscriber;

    public function __construct(?string $clientIdSuffix = null)
    {
        $server = env('MQTT_HOST', 'broker.hivemq.com');
        $port = (int) env('MQTT_PORT', 1883);

        // Subscriber pakai client ID tetap supaya reconnect menggantikan koneksi lama.
        // Publisher pakai client ID unik per-instance — kalau pakai ID sama, dua
        // request publish yang bersamaan akan saling menendang dari broker dan
        // pesan QoS 1 yang sedang di-handshake bisa hilang.
        $this->isSubscriber = $clientIdSuffix === 'subscriber';
        $clientId = $this->isSubscriber
            ? 'laravel_subscriber'
            : 'laravel_pub_'.bin2hex(random_bytes(4));

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

        // QoS 1 butuh PUBACK dari broker — kalau script PHP-FPM langsung exit
        // tanpa loop, bytes-nya memang sudah ke socket tapi broker belum tentu
        // sempat ack-nya, dan pesan retain bisa tidak tersimpan. Loop sebentar
        // agar handshake selesai sebelum koneksi ditutup.
        if (! $this->isSubscriber && $qos > 0) {
            try {
                $this->mqtt->loopOnce(microtime(true), true);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('MQTT loop after publish failed', [
                    'topic' => $topic,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function clearRetained(string $topic, int $qos = 1): void
    {
        $this->publish($topic, '', $qos, true);
    }

    public function disconnect(): void
    {
        try {
            if ($this->mqtt && $this->mqtt->isConnected()) {
                $this->mqtt->disconnect();
            }
        } catch (\Throwable) {
        }
    }

    public function __destruct()
    {
        $this->disconnect();
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

        \Illuminate\Support\Facades\Log::info("CONFIG + STATUS DIKIRIM KE {$deviceId}");
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
