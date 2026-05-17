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

/**
 * Run AC timer ON/OFF with anti-double-execution and anti-miss logic.
 *
 * TIMING MECHANISM (3-layer approach):
 * ────────────────
 * 1. WINDOW (-30 to +60 seconds):
 *    Timer fires if current time within ±30s before or +60s after scheduled time.
 *    Recovers from delayed executions (e.g., system reboot at scheduled time).
 *
 * 2. LOCK (10 seconds):
 *    Cache::lock prevents concurrent executions across multiple cron instances.
 *    Expires after 10s to handle process crashes.
 *
 * 3. COOLDOWN (5 seconds):
 *    Per-AC cooldown prevents spam if command runs multiple times per minute.
 *
 * VERSION TRACKING:
 * ────────────────
 * Cache key includes version number (timer_version_{ac_id}) to handle timer updates.
 * When user changes timer, version increments → old cache keys stale → new execution allowed.
 *
 * EXECUTION FLOW:
 * ───────────────
 * 1. Acquire global lock (anti-concurrent)
 * 2. For each AC with timer_on or timer_off:
 *    a. Skip if in cooldown (anti-spam within 5s)
 *    b. Check if current time in execution window
 *    c. Check if already executed today (versioned cache key)
 *    d. Acquire cache lock, double-check, then execute MQTT publish
 *    e. Log execution and set cooldown + versioned cache
 */
class RunAcTimer extends Command
{
    protected $signature = 'ac:run-timer';
    protected $description = 'Run AC timer ON/OFF (Anti Miss + Anti Double)';

    const WINDOW_BEFORE = -30;
    const WINDOW_AFTER = 60;
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
