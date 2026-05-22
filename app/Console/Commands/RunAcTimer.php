<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AcStatus;
use App\Models\AcUnit;
use App\Models\UserLog;
use App\Services\MqttService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RunAcTimer extends Command
{
    protected $signature = 'ac:run-timer';
    protected $description = 'Run AC timer ON/OFF (Anti Miss + Anti Double)';

    // Window simetris ±30s — tidak overlap antara slot 1 menit, jadi timer ON & OFF
    // berjarak 1 menit tidak saling ke-trigger di iterasi yang sama.
    const WINDOW_BEFORE = -30;
    const WINDOW_AFTER = 30;
    const EXECUTION_BUFFER = 60;
    const COOLDOWN_SECONDS = 5;

    public function handle()
    {
        $now   = Carbon::now('Asia/Jakarta');
        $today = Carbon::today('Asia/Jakarta');

        $this->info("Checking AC timers at " . $now->toDateTimeString());

        try {
            $mqtt = new MqttService();
        } catch (\Throwable $e) {
            Log::error("MQTT TIMER CONNECTION ERROR", [
                'error' => $e->getMessage(),
            ]);

            $this->error("MQTT connection failed: " . $e->getMessage());

            return Command::FAILURE;
        }

        $acs = AcUnit::with(['room:id,name', 'status:id,ac_unit_id,power,mode,set_temperature,fan_speed,swing'])
            ->select('id', 'room_id', 'ac_number', 'timer_on', 'timer_off')
            ->whereHas('room')
            ->where(function ($q) {
                $q->whereNotNull('timer_on')
                  ->orWhereNotNull('timer_off');
            })
            ->get();

        if ($acs->isEmpty()) {
            $this->line("No active timers found.");
        }

        foreach ($acs as $ac) {

            // Cek cooldown untuk mencegah spam
            $cooldownKey = "ac_cooldown_{$ac->id}";
            if (Cache::has($cooldownKey)) {
                continue;
            }

            $version = Cache::get("timer_version_{$ac->id}", 1);
            $roomName = $ac->room->name;
            $topic = 'room/' . \App\Services\MqttService::roomToTopic($roomName) . "/ac/{$ac->ac_number}/control";
            $status = $ac->status ?: AcStatus::firstOrCreate(
                ['ac_unit_id' => $ac->id],
                [
                    'power' => 'OFF',
                    'mode' => 'COOL',
                    'set_temperature' => 24,
                    'fan_speed' => 'AUTO',
                    'swing' => 'OFF',
                ]
            );

            foreach (['on', 'off'] as $type) {

                $timerField     = "timer_{$type}";
                $expectedStatus = strtoupper($type);

                if (!$ac->$timerField) continue;

                $timer = $today->copy()->setTimeFromTimeString($ac->$timerField);

                $diff = $now->diffInSeconds($timer, false);
                $alreadyExecuted = $now->gt($timer->copy()->addSeconds(self::EXECUTION_BUFFER));

                $key = "timer_{$type}_{$ac->id}_v{$version}_" . $timer->format('Y-m-d_H:i');

                if (
                    $diff >= self::WINDOW_BEFORE &&
                    $diff <= self::WINDOW_AFTER &&
                    !$alreadyExecuted &&
                    $status->power !== $expectedStatus
                ) {

                    $lock = Cache::lock("lock:{$key}", 10);

                    if (!$lock->get()) {
                        $this->warn("Lock not acquired for AC {$ac->ac_number} {$type}");
                        continue;
                    }

                    try {
                        // Double check (extra safety)
                        if (Cache::has($key)) {
                            $this->line("Already executed: AC {$ac->ac_number} {$type}");
                            continue;
                        }

                        $this->info("Executing timer: AC {$ac->ac_number} -> {$expectedStatus}");

                        // Kirim perintah ke MQTT
                        $mqtt->publish($topic, json_encode([
                            "power" => $expectedStatus,
                            "mode"  => $status->mode ?? 'COOL',
                            "temp"  => (int)($status->set_temperature ?? 24),
                            "fan_speed" => $status->fan_speed ?? 'AUTO',
                            "swing" => $status->swing ?? 'OFF',
                        ]), 1, true);

                        // Update database
                        $status->update([
                            'power' => $expectedStatus
                        ]);

                        UserLog::create([
                            'user_id' => null,
                            'room'    => $roomName,
                            'ac'      => 'AC ' . $ac->ac_number,
                            'activity' => 'timer_' . strtolower($type),
                        ]);

                        Cache::put($key, true, 300);

                        Cache::put($cooldownKey, true, self::COOLDOWN_SECONDS);

                        Log::info("TIMER {$expectedStatus} SUCCESS", [
                            'ac_id'    => $ac->ac_number,
                            'room'     => $roomName,
                            'time'     => $now->toDateTimeString(),
                            'timer_at' => $timer->toDateTimeString()
                        ]);

                        $this->info("TIMER {$expectedStatus} -> AC {$ac->ac_number}");

                    } catch (\Exception $e) {
                        Log::error("MQTT {$expectedStatus} ERROR", [
                            'ac_id'  => $ac->ac_number,
                            'error'  => $e->getMessage(),
                            'topic'  => $topic
                        ]);
                        $this->error("Failed: AC {$ac->ac_number} - " . $e->getMessage());
                    } finally {
                        optional($lock)->release();
                    }
                }
            }
        }

        $this->info("Timer check completed");
        return Command::SUCCESS;
    }
}
