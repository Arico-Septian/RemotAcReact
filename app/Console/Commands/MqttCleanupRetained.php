<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\Room;
use App\Services\MqttService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

#[Signature('mqtt:cleanup-retained {--seconds=5 : How long to listen for retained messages} {--dry-run : Only list retained topics that would be cleared}')]
#[Description('Clear stale retained MQTT control topics that no longer match rooms and AC units')]
class MqttCleanupRetained extends Command
{
    public function handle(): int
    {
        $seconds = max(1, (int) $this->option('seconds'));
        $dryRun = (bool) $this->option('dry-run');
        $validTopics = $this->validControlTopics();
        $retainedTopics = [];

        $this->info('Connecting to MQTT broker...');

        $mqtt = $this->mqttClient();

        $mqtt->subscribe('room/+/ac/+/control', function (string $topic, string $message, bool $retained) use (&$retainedTopics) {
            if ($retained) {
                $retainedTopics[$topic] = true;
            }
        });

        $this->info("Scanning retained control topics for {$seconds} second(s)...");

        $until = microtime(true) + $seconds;
        while (microtime(true) < $until && $mqtt->isConnected()) {
            $mqtt->loopOnce(microtime(true), true);
        }

        $staleTopics = array_values(array_diff(array_keys($retainedTopics), $validTopics));
        sort($staleTopics);

        if ($staleTopics === []) {
            $this->info('No stale retained control topics found.');
            $mqtt->disconnect();

            return Command::SUCCESS;
        }

        foreach ($staleTopics as $topic) {
            if ($dryRun) {
                $this->warn("Would clear: {$topic}");
            } else {
                $mqtt->publish($topic, '', 1, true);
                $this->info("Cleared: {$topic}");
            }
        }

        if (! $dryRun) {
            $until = microtime(true) + 2;
            while (microtime(true) < $until && $mqtt->isConnected()) {
                $mqtt->loopOnce(microtime(true));
                usleep(100_000);
            }
        }

        $mqtt->disconnect();

        $count = count($staleTopics);
        $action = $dryRun ? 'Found' : 'Cleared';
        $this->info("{$action} {$count} stale retained control topic(s).");

        return Command::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function validControlTopics(): array
    {
        return Room::with('acUnits:id,room_id,ac_number')
            ->get(['id', 'name'])
            ->flatMap(fn (Room $room) => $room->acUnits
                ->map(fn ($ac) => 'room/'.MqttService::roomToTopic($room->name)."/ac/{$ac->ac_number}/control"))
            ->values()
            ->all();
    }

    private function mqttClient(): MqttClient
    {
        $server = AppSetting::mqttHost();
        $port = AppSetting::mqttPort();
        $useTls = $port === 8883;

        $settings = (new ConnectionSettings)
            ->setUsername(AppSetting::mqttUsername())
            ->setPassword(AppSetting::mqttPassword())
            ->setUseTls($useTls)
            ->setTlsSelfSignedAllowed(true)
            ->setTlsVerifyPeer(false)
            ->setTlsVerifyPeerName(false)
            ->setKeepAliveInterval(15)
            ->setConnectTimeout(5)
            ->setSocketTimeout(2)
            ->setResendTimeout(10);

        $client = new MqttClient($server, $port, 'laravel_retained_cleanup_'.uniqid());
        $client->connect($settings, true);

        return $client;
    }
}
