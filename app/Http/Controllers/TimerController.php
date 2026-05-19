<?php

namespace App\Http\Controllers;

use App\Events\AcTimerUpdated;
use App\Models\AcUnit;
use App\Models\UserLog;
use App\Services\MqttService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TimerController extends Controller
{
    public function schedule(Request $request, $id)
    {
        $request->validate([
            'timer_on' => 'nullable|date_format:H:i',
            'timer_off' => 'nullable|date_format:H:i',
        ]);

        $ac = AcUnit::with('room')->findOrFail($id);

        // VALIDASI LOGIKA
        if ($request->timer_on && $request->timer_off) {
            if ($request->timer_on === $request->timer_off) {
                return back()->with('error', 'Timer ON dan OFF tidak boleh sama');
            }
        }

        $newTimerOn = $request->timer_on ?: null;
        $newTimerOff = $request->timer_off ?: null;
        $isDeletingTimer = $newTimerOn === null && $newTimerOff === null;

        $key = "timer_version_{$ac->id}";

        // UPDATE HANYA JIKA BERUBAH
        if (
            $ac->timer_on !== $newTimerOn ||
            $ac->timer_off !== $newTimerOff
        ) {

            if (! Cache::has($key)) {
                Cache::put($key, 1);
            } else {
                Cache::increment($key);
            }

            $ac->update([
                'timer_on' => $newTimerOn,
                'timer_off' => $newTimerOff,
            ]);

            // PUBLISH TIMER INFO KE MQTT
            try {
                $mqtt = new MqttService;
                $topic = 'room/'.MqttService::roomToTopic($ac->room->name)."/ac/{$ac->ac_number}/timer";

                $payload = [
                    'timer_on' => $newTimerOn,
                    'timer_off' => $newTimerOff,
                ];

                $mqtt->publish($topic, json_encode($payload), 1, true);
                Log::info('Timer published to MQTT', [
                    'ac_id' => $ac->id,
                    'topic' => $topic,
                    'payload' => $payload,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to publish timer to MQTT', [
                    'ac_id' => $ac->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $detail = [];
            if ($newTimerOn) {
                $detail[] = 'ON '.$newTimerOn;
            }
            if ($newTimerOff) {
                $detail[] = 'OFF '.$newTimerOff;
            }
            if (! $newTimerOn && ! $newTimerOff) {
                $detail[] = 'dihapus';
            }

            UserLog::create([
                'user_id' => Auth::id(),
                'room' => optional($ac->room)->name,
                'ac' => 'AC '.$ac->ac_number.($ac->name ? ' '.$ac->name : '').' ['.implode(', ', $detail).']',
                'activity' => 'set_timer',
            ]);

            event(new AcTimerUpdated($ac->fresh()->load('room')));
        }

        return back()->with('success', $isDeletingTimer ? 'Timer berhasil dihapus' : 'Timer berhasil disimpan');
    }
}
