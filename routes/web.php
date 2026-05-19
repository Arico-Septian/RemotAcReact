<?php

use App\Http\Controllers\AcControlController;
use App\Http\Controllers\AcUnitController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TimerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserLogController;
use App\Models\AcStatus;
use App\Models\Notification;
use App\Models\Room;
use App\Models\RoomTemperature;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('auth.login');
})->name('login');

Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()
        ->route('login')
        ->with('error', 'Registrasi publik dinonaktifkan. Hubungi admin untuk membuat akun.');
});

Route::get('/system-check', function () {
    return response()->json(['status' => 'online']);
});

Route::middleware(['auth', 'activity'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/session/ping', fn () => response()->json(['ok' => true]))->name('session.ping');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/recent-activities', [DashboardController::class, 'recentActivities'])->name('dashboard.recent-activities');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/profile', [UserController::class, 'profile']);
    Route::post('/profile/avatar', [UserController::class, 'uploadAvatar'])->name('profile.avatar.upload');
    Route::delete('/profile/avatar', [UserController::class, 'deleteAvatar'])->name('profile.avatar.delete');
    Route::post('/change-password', [UserController::class, 'changePassword']);
    Route::get('/rooms/overview', [RoomController::class, 'overview'])->name('rooms.overview');
    Route::get('/rooms/{id}/status', [RoomController::class, 'status']);

    Route::get('/api/ac-status', function () {
        return AcStatus::with('acUnit.room')->get();
    });

    Route::get('/ac-status', function () {
        return AcStatus::with('acUnit')->get();
    });

    Route::get('/device-status', function () {
        return Room::whereNotNull('device_id')
            ->orderBy('name')
            ->get()
            ->map(function ($room) {
                $deviceId = strtolower(trim($room->device_id));
                $lastSeen = Cache::get("device_{$deviceId}_last_seen") ?: $room->last_seen;
                $status = Cache::get("device_status_{$deviceId}", $room->device_status ?? 'offline');

                $lastSeenAt = null;
                $isOnline = false;

                if ($lastSeen) {
                    $lastSeenAt = $lastSeen instanceof Carbon ? $lastSeen : Carbon::parse($lastSeen);
                    $isOnline = $status === 'online' && now()->diffInSeconds($lastSeenAt, true) <= 30;
                }

                // State-based notifications: only notify on state CHANGE
                if (! $isOnline && $lastSeenAt && now()->diffInMinutes($lastSeenAt, true) >= 2) {
                    Notification::deviceOffline($room->name, $deviceId);
                } elseif ($isOnline) {
                    Notification::deviceOnline($room->name, $deviceId);
                }

                return [
                    'room_id' => $room->id,
                    'room_name' => $room->name,
                    'device_id' => $deviceId,
                    'is_online' => $isOnline,
                    'status' => $isOnline ? 'online' : 'offline',
                    'last_seen' => optional($lastSeenAt)->toDateTimeString(),
                ];
            })
            ->values();
    });

    // ==================== NOTIFICATIONS ====================
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent', [NotificationController::class, 'recent']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    Route::post('/update-activity', function () {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user) {
            $user->last_activity = now();
            $user->save();
        }

        return response()->json(['status' => 'ok']);
    });

    Route::get('/my-status', function () {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->last_activity) {
            return response()->json(['status' => 'offline']);
        }

        return response()->json([
            'status' => now()->diffInSeconds($user->last_activity, true) < 60 ? 'online' : 'offline',
        ]);
    });

    $roomDeviceIsOnline = function (Room $room): bool {
        $deviceId = strtolower(trim((string) $room->device_id));

        if ($deviceId === '') {
            return false;
        }

        $lastSeen = Cache::get("device_{$deviceId}_last_seen") ?: $room->last_seen;
        $status = Cache::get("device_status_{$deviceId}", $room->device_status ?? 'offline');

        if (! $lastSeen) {
            return false;
        }

        $lastSeenAt = $lastSeen instanceof Carbon ? $lastSeen : Carbon::parse($lastSeen);

        return in_array($status, ['online', 'available'], true)
            && now()->diffInSeconds($lastSeenAt, true) <= 30;
    };

    $temperatureEndpoint = function () use ($roomDeviceIsOnline) {
        $latestTemperatures = RoomTemperature::latestByNormalizedRoom();

        return Room::orderBy('name')
            ->get()
            ->map(function ($room) use ($latestTemperatures, $roomDeviceIsOnline) {
                $roomKey = RoomTemperature::normalizeRoomName($room->name);
                $record = $latestTemperatures->get($roomKey);
                $lastTemperature = $record?->temperature;
                $temperature = $lastTemperature;
                $isOffline = ! $roomDeviceIsOnline($room) || Cache::get("room_temp_status_{$roomKey}") === 'offline';

                // Stale check: kalau record terakhir > 30s, anggap sensor mati → null
                if ($record && $record->created_at) {
                    $isOffline = $isOffline || now()->diffInSeconds($record->created_at, true) > 30;
                } else {
                    $isOffline = true;
                }

                if ($isOffline) {
                    $temperature = null;
                }

                return [
                    'id' => $room->id,
                    'name' => $room->name,
                    'temp' => $temperature,
                    'temperature' => $temperature,
                    'last_temp' => $lastTemperature,
                    'is_offline' => $isOffline,
                    'last_seen' => optional($record?->created_at)->toDateTimeString(),
                ];
            })
            ->values();
    };

    Route::get('/temperature', $temperatureEndpoint);
    Route::get('/temperatures', $temperatureEndpoint);

    Route::get('/temperature/history/{id}', function ($id) {
        $room = Room::findOrFail($id);
        $normalized = RoomTemperature::normalizeRoomName($room->name);

        $rows = RoomTemperature::where('room', $normalized)
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at')
            ->get();

        $grouped = $rows
            ->groupBy(fn ($t) => $t->created_at->format('H:00'))
            ->map(fn ($g) => round($g->avg('temperature'), 1));

        return response()->json(
            $grouped->map(fn ($temp, $hour) => ['time' => $hour, 'temp' => $temp])->values()
        );
    });

    Route::get('/temperature/trend', function (Request $request) use ($roomDeviceIsOnline) {
        $limit = (int) $request->query('limit', 5);
        $range = $request->query('range', '1h');

        // Konfigurasi range: total jam, interval menit, label format
        $rangeConfig = [
            '1h' => ['hours' => 1,  'interval' => 5,  'slots' => 12, 'label' => 'H:i'],
            '3h' => ['hours' => 3,  'interval' => 10, 'slots' => 18, 'label' => 'H:i'],
            '6h' => ['hours' => 6,  'interval' => 15, 'slots' => 24, 'label' => 'H:i'],
            '24h' => ['hours' => 24, 'interval' => 60, 'slots' => 24, 'label' => 'H:00'],
        ];
        $cfg = $rangeConfig[$range] ?? $rangeConfig['1h'];
        $interval = $cfg['interval'];
        $totalSlots = $cfg['slots'];
        $labelFormat = $cfg['label'];

        $rooms = Room::orderBy('name')->get();
        $startTime = now()->subHours($cfg['hours']);
        $latestTemperatures = RoomTemperature::latestByNormalizedRoom();

        // Sort by latest temperature DESC (hottest first)
        $rooms = $rooms->sortByDesc(function ($room) use ($latestTemperatures) {
            $temp = optional(
                $latestTemperatures->get(RoomTemperature::normalizeRoomName($room->name))
            )->temperature;

            return $temp ?? -999;
        })->values();

        $totalRooms = $rooms->count();

        if ($limit > 0) {
            $rooms = $rooms->take($limit);
        }

        // Helper: bikin slot key (include tanggal supaya 24h tidak konflik antar hari)
        $slotKeyFor = function (Carbon $time) use ($interval) {
            if ($interval >= 60) {
                return $time->copy()->startOfHour()->format('Y-m-d H');
            }
            $minute = floor($time->minute / $interval) * $interval;

            return $time->copy()->setMinute($minute)->setSecond(0)->format('Y-m-d H:i');
        };

        // Generate slots
        $slots = collect();
        if ($interval >= 60) {
            $base = now()->copy()->startOfHour();
            for ($i = $totalSlots - 1; $i >= 0; $i--) {
                $slots->push($base->copy()->subHours($i));
            }
        } else {
            $currentMinute = floor(now()->minute / $interval) * $interval;
            $latestSlot = now()->copy()->startOfMinute()->setMinute($currentMinute)->setSecond(0);
            for ($i = $totalSlots - 1; $i >= 0; $i--) {
                $slots->push($latestSlot->copy()->subMinutes($i * $interval));
            }
        }

        $labels = $slots->map(fn ($t) => $t->format($labelFormat));

        $palette = [
            '#fb7185', '#fbbf24', '#4dd4ff', '#a78bfa',
            '#34d399', '#f472b6', '#60a5fa', '#fb923c',
            '#facc15', '#22d3ee', '#c084fc', '#f87171',
        ];

        $datasets = $rooms->values()->map(function ($room, $idx) use ($startTime, $slots, $palette, $latestTemperatures, $slotKeyFor, $roomDeviceIsOnline) {
            $normalized = RoomTemperature::normalizeRoomName($room->name);

            $rows = RoomTemperature::where('room', $normalized)
                ->where('created_at', '>=', $startTime)
                ->orderBy('created_at')
                ->get();

            $grouped = $rows->groupBy(fn ($t) => $slotKeyFor($t->created_at))
                ->map(fn ($g) => round($g->avg('temperature'), 1));

            $data = $slots->map(fn ($t) => $grouped->get($slotKeyFor($t)));

            $lastRecord = $latestTemperatures->get($normalized);
            $currentTemp = optional($lastRecord)->temperature;
            $lastKnownTemp = $currentTemp;

            // Cek apakah sensor suhu offline (data terakhir > 30 detik lalu)
            $isOffline = ! $roomDeviceIsOnline($room) || Cache::get("room_temp_status_{$normalized}") === 'offline';
            $offlineSince = null;
            if ($lastRecord && $lastRecord->created_at) {
                $secondsAgo = now()->diffInSeconds($lastRecord->created_at, true);
                $isOffline = $isOffline || $secondsAgo > 30;
                if ($isOffline) {
                    $offlineSince = $lastRecord->created_at->format('H:i');
                }
            } else {
                $isOffline = true;
            }

            // Saat offline: isi slot kosong dengan suhu terakhir agar garis tetap muncul (statis)
            if ($isOffline && $lastKnownTemp !== null) {
                $data = $data->map(fn ($v) => $v ?? $lastKnownTemp);
            }

            return [
                'room' => ucfirst($room->name),
                'room_id' => $room->id,
                'current_temp' => $currentTemp,
                'is_offline' => $isOffline,
                'offline_since' => $offlineSince,
                'data' => $data->values(),
                'color' => $palette[$idx % count($palette)],
            ];
        });

        return response()->json([
            'labels' => $labels->values(),
            'datasets' => $datasets->values(),
            'total_rooms' => $totalRooms,
            'shown' => $datasets->count(),
            'limit' => $limit,
            'range' => $range,
            'interval_minutes' => $interval,
        ]);
    });

    Route::middleware(['role:admin,operator'])->group(function () {
        Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::post('/rooms/add', [RoomController::class, 'store']);
        Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);

        Route::get('/rooms/{id}/ac', [AcUnitController::class, 'index']);
        Route::post('/rooms/{id}/ac', [AcUnitController::class, 'store']);
        Route::delete('/ac/{id}', [AcUnitController::class, 'destroy']);
        Route::put('/ac/{id}', [AcUnitController::class, 'update']);

        Route::post('/rooms/{id}/ac/bulk-power', [AcControlController::class, 'bulkPower']);

        // AC control endpoints with rate limiting (30 req/min per user)
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/ac/{id}/on', [AcControlController::class, 'powerOn']);
            Route::get('/ac/{id}/off', [AcControlController::class, 'powerOff']);
            Route::post('/ac/{id}/temp/{value}', [AcControlController::class, 'setTemp']);
            Route::post('/ac/{id}/mode/{mode}', [AcControlController::class, 'setMode']);
            Route::post('/ac/{id}/fan-speed/{speed}', [AcControlController::class, 'setFanSpeed']);
            Route::post('/ac/{id}/swing/{swing}', [AcControlController::class, 'setSwing']);
            Route::post('/ac/{id}/toggle', [AcControlController::class, 'togglePower']);
            Route::post('/ac/{id}/schedule', [TimerController::class, 'schedule']);
        });

    });

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/logs', [UserLogController::class, 'index']);
        Route::delete('/logs/delete-all', [UserLogController::class, 'destroyAll']);

        Route::get('/users-online', function () {
            $total = User::count();
            $online = User::where('last_activity', '>=', now()->subMinutes(2))
                ->count();
            $offline = $total - $online;

            return response()->json([
                'count' => $online,
                'online' => $online,
                'offline' => $offline,
                'total' => $total,
                'percentage' => $total > 0 ? (int) round(($online / $total) * 100) : 0,
                'offlinePercentage' => $total > 0 ? (int) round(($offline / $total) * 100) : 0,
            ]);
        });
    });

    Route::get('/cek-driver', function () {
        return config('cache.default');
    });

    Route::get('/test-cache', function () {
        Cache::put('test_key', 'OK', 60);

        return Cache::get('test_key');
    });

    Route::get('/suhu-raspi', function () {
        $temp = Cache::get('raspi_temperature');

        return response()->json([
            'suhu' => $temp !== null ? $temp.' °C' : null,
            'value' => $temp,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    });

    Route::get('/raspi-monitor', function () {
        return response()->view('server.monitoring')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->name('monitoring');

    Route::post(
        '/rooms/{id}/ac/fuzzy/apply',
        [AcUnitController::class, 'applyFuzzy']
    )->name('ac.fuzzy.apply');
});
