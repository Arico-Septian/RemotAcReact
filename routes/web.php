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
use App\Models\Room;
use App\Models\RoomTemperature;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return \Inertia\Inertia::render('Login');
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
                    $isOnline = $status === 'online' && now()->diffInSeconds($lastSeenAt, true) <= Room::ONLINE_THRESHOLD_SECONDS;
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
            && now()->diffInSeconds($lastSeenAt, true) <= Room::ONLINE_THRESHOLD_SECONDS;
    };

    $temperatureEndpoint = function () use ($roomDeviceIsOnline) {
        $latestTemperatures = RoomTemperature::latestByNormalizedRoom();

        return Room::orderBy('name')
            ->get()
            ->map(function (Room $room) use ($latestTemperatures, $roomDeviceIsOnline) {
                $roomKey = RoomTemperature::normalizeRoomName($room->name);
                $record = $latestTemperatures->get($roomKey);
                $lastTemperature = $record?->temperature;
                $temperature = $lastTemperature;
                $isOffline = ! $roomDeviceIsOnline($room) || Cache::get("room_temp_status_{$roomKey}") === 'offline';

                // Stale check: kalau record terakhir > 120s, anggap sensor mati → null
                if ($record && $record->created_at) {
                    $isOffline = $isOffline || now()->diffInSeconds($record->created_at, true) > 120;
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

    Route::get('/temperature/history/{id}', function (Request $request, $id) {
        $room = Room::findOrFail($id);
        $normalized = RoomTemperature::normalizeRoomName($room->name);
        $range = (string) $request->query('range', 'today');
        $range = $range === '24h' ? 'today' : $range;
        // Samakan dengan dashboard: 1 Hour = per menit (60 titik), 1 Day = 24 jam rolling per jam.
        $rangeConfig = [
            '1h' => ['hours' => 1,  'interval' => 1,  'slots' => 60, 'label' => 'H:i'],
            'today' => ['hours' => 24, 'interval' => 60, 'slots' => 24, 'label' => 'H:00'],
        ];
        $cfg = $rangeConfig[$range] ?? $rangeConfig['today'];
        $interval = $cfg['interval'];

        if ($cfg['today'] ?? false) {
            $startOfDay = now()->copy()->startOfDay();
            $latestSlot = now()->copy()->startOfHour();
            $slotCount = $startOfDay->diffInHours($latestSlot) + 1;
            $slots = collect(range(0, $slotCount - 1))
                ->map(fn (int $slotIndex) => $startOfDay->copy()->addHours($slotIndex));
        } elseif ($interval >= 60) {
            $latestSlot = now()->copy()->startOfHour();
            $slots = collect(range($cfg['slots'] - 1, 0))
                ->map(fn (int $slotIndex) => $latestSlot->copy()->subMinutes($slotIndex * $interval));
        } else {
            $currentMinute = floor(now()->minute / $interval) * $interval;
            $latestSlot = now()->copy()->startOfMinute()->setMinute($currentMinute)->setSecond(0);
            $slots = collect(range($cfg['slots'] - 1, 0))
                ->map(fn (int $slotIndex) => $latestSlot->copy()->subMinutes($slotIndex * $interval));
        }

        $startTime = $slots->first();
        $slotKeyFor = function (Carbon $time) use ($interval): string {
            if ($interval >= 60) {
                return $time->copy()->startOfHour()->format('Y-m-d H');
            }

            $minute = floor($time->minute / $interval) * $interval;

            return $time->copy()->setMinute($minute)->setSecond(0)->format('Y-m-d H:i');
        };

        $rows = RoomTemperature::where('room', $normalized)
            ->where('created_at', '>=', $startTime)
            ->orderBy('created_at')
            ->get();

        $grouped = $rows
            ->groupBy(fn (RoomTemperature $temperature) => $slotKeyFor($temperature->created_at))
            ->map(fn ($g) => round($g->avg('temperature'), 1));

        $history = $slots->map(fn (Carbon $slot): array => [
            'time' => $slot->format($cfg['label']),
            'temp' => $grouped->get($slotKeyFor($slot)),
        ]);

        if ($history->every(fn (array $point) => $point['temp'] === null)) {
            return response()->json([]);
        }

        return response()->json($history->values());
    });

    Route::get('/temperature/trend', function (Request $request) use ($roomDeviceIsOnline) {
        // Dua pilihan: 1 Day (24 jam, per jam) & 1 Hour (1 jam, per menit).
        $range = $request->query('range', '1d');
        $rangeConfig = [
            // 1 Day = 24 jam, per-1-jam (24 titik). 1 Hour = 60 menit, per-1-menit (60 titik).
            '1d' => ['hours' => 24, 'interval' => 60, 'slots' => 24, 'label' => 'H:00'],
            '1h' => ['hours' => 1,  'interval' => 1,  'slots' => 60, 'label' => 'H:i'],
        ];
        $cfg = $rangeConfig[$range] ?? $rangeConfig['1d'];

        $payload = Cache::remember(
            "temperature_trend_avg:{$range}",
            10,
            function () use ($cfg, $range, $roomDeviceIsOnline) {
                $interval = $cfg['interval'];
                $labelFormat = $cfg['label'];

                $rooms = Room::orderBy('name')->get();
                $startTime = ($cfg['today'] ?? false)
                    ? now()->copy()->startOfDay()
                    : now()->subHours($cfg['hours']);
                $latestTemperatures = RoomTemperature::latestByNormalizedRoom();

                // Urutkan: 1. Online vs Offline, 2. Suhu Tertinggi
                $rooms = $rooms->sort(function ($a, $b) use ($roomDeviceIsOnline, $latestTemperatures) {
                    $aOnline = $roomDeviceIsOnline($a);
                    $bOnline = $roomDeviceIsOnline($b);

                    if ($aOnline && ! $bOnline) {
                        return -1;
                    }
                    if (! $aOnline && $bOnline) {
                        return 1;
                    }

                    $aTemp = optional($latestTemperatures->get(RoomTemperature::normalizeRoomName($a->name)))->temperature ?? -999;
                    $bTemp = optional($latestTemperatures->get(RoomTemperature::normalizeRoomName($b->name)))->temperature ?? -999;

                    return $bTemp <=> $aTemp;
                })->values();

                $totalRooms = $rooms->count();

                // Helper: bikin slot key (include tanggal supaya slot per jam tidak konflik antar hari)
                $slotKeyFor = function (Carbon $time) use ($interval) {
                    if ($interval >= 60) {
                        return $time->copy()->startOfHour()->format('Y-m-d H');
                    }
                    $minute = floor($time->minute / $interval) * $interval;

                    return $time->copy()->setMinute($minute)->setSecond(0)->format('Y-m-d H:i');
                };

                // SQL expression untuk slot key — match dengan $slotKeyFor di PHP
                // Group by langsung di DB, jadi PHP cuma terima ~24 row hasil agregasi (bukan ribuan row mentah)
                $slotExpr = $interval >= 60
                    ? "DATE_FORMAT(created_at, '%Y-%m-%d %H')"
                    : "CONCAT(DATE_FORMAT(created_at, '%Y-%m-%d %H:'), LPAD(FLOOR(MINUTE(created_at) / {$interval}) * {$interval}, 2, '0'))";

                // Generate slots
                $slots = collect();
                if ($cfg['today'] ?? false) {
                    $base = now()->copy()->startOfDay();
                    $latestSlot = now()->copy()->startOfHour();
                    $totalSlots = $base->diffInHours($latestSlot) + 1;
                    for ($i = 0; $i < $totalSlots; $i++) {
                        $slots->push($base->copy()->addHours($i));
                    }
                } elseif ($interval >= 60) {
                    $base = now()->copy()->startOfHour();
                    $totalSlots = $cfg['slots'];
                    for ($i = $totalSlots - 1; $i >= 0; $i--) {
                        $slots->push($base->copy()->subHours($i));
                    }
                } else {
                    $totalSlots = $cfg['slots'];
                    $currentMinute = floor(now()->minute / $interval) * $interval;
                    $latestSlot = now()->copy()->startOfMinute()->setMinute($currentMinute)->setSecond(0);
                    for ($i = $totalSlots - 1; $i >= 0; $i--) {
                        $slots->push($latestSlot->copy()->subMinutes($i * $interval));
                    }
                }

                $labels = $slots->map(fn ($t) => $t->format($labelFormat));

                // === SATU GARIS: rata-rata suhu semua ruangan per slot waktu ===
                $slotKeys = $slots->map($slotKeyFor)->all();

                $perRoomSeries = [];   // per ruangan: [slotIdx => suhu|null]
                $currentTemps = [];    // suhu terkini ruangan yang online
                $onlineCount = 0;

                foreach ($rooms as $room) {
                    $normalized = RoomTemperature::normalizeRoomName($room->name);

                    $grouped = RoomTemperature::where('room', $normalized)
                        ->where('created_at', '>=', $startTime)
                        ->selectRaw("{$slotExpr} as slot_key, AVG(temperature) as avg_temp")
                        ->groupBy('slot_key')
                        ->pluck('avg_temp', 'slot_key')
                        ->map(fn ($v) => round((float) $v, 1));

                    $perRoomSeries[] = array_map(fn ($k) => $grouped->get($k), $slotKeys);

                    // status online + suhu terkini ruangan (untuk rata-rata "sekarang")
                    $lastRecord = $latestTemperatures->get($normalized);
                    $isOffline = ! $roomDeviceIsOnline($room) || Cache::get("room_temp_status_{$normalized}") === 'offline';
                    if ($lastRecord && $lastRecord->created_at) {
                        $isOffline = $isOffline || now()->diffInSeconds($lastRecord->created_at, true) > 120;
                    } else {
                        $isOffline = true;
                    }
                    if (! $isOffline && $lastRecord) {
                        $currentTemps[] = (float) $lastRecord->temperature;
                        $onlineCount++;
                    }
                }

                // Rata-ratakan tiap slot lintas ruangan (slot tanpa data mana pun = null).
                // Rata-rata dihitung dari rata-rata per-ruangan, jadi tiap ruangan berbobot sama.
                $avgData = [];
                foreach (array_keys($slotKeys) as $i) {
                    $vals = [];
                    foreach ($perRoomSeries as $series) {
                        if ($series[$i] !== null) {
                            $vals[] = $series[$i];
                        }
                    }
                    $avgData[] = $vals === [] ? null : round(array_sum($vals) / count($vals), 1);
                }

                $avgCurrent = $currentTemps === [] ? null : round(array_sum($currentTemps) / count($currentTemps), 1);

                $datasets = [[
                    'room' => 'Rata-rata Ruangan',
                    'room_id' => 0,
                    'current_temp' => $avgCurrent,
                    'last_temp' => $avgCurrent,
                    'is_offline' => $onlineCount === 0,
                    'offline_since' => null,
                    'data' => $avgData,
                    'color' => '#22d3ee',
                ]];

                return [
                    'labels' => $labels->values()->all(),
                    'datasets' => $datasets,
                    'total_rooms' => $totalRooms,
                    'rooms_online' => $onlineCount,
                    'rooms_offline' => $totalRooms - $onlineCount,
                    'shown' => 1,
                    'range' => $range,
                    'interval_minutes' => $interval,
                ];
            }
        );

        return response()->json($payload);
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
            Route::post('/ac/{id}/on', [AcControlController::class, 'powerOn']);
            Route::post('/ac/{id}/off', [AcControlController::class, 'powerOff']);
            Route::post('/ac/{id}/temp/{value}', [AcControlController::class, 'setTemp']);
            Route::post('/ac/{id}/mode/{mode}', [AcControlController::class, 'setMode']);
            Route::post('/ac/{id}/fan-speed/{speed}', [AcControlController::class, 'setFanSpeed']);
            Route::post('/ac/{id}/swing/{swing}', [AcControlController::class, 'setSwing']);
            Route::post('/ac/{id}/toggle', [AcControlController::class, 'togglePower']);
            Route::post('/ac/{id}/schedule', [TimerController::class, 'schedule']);
            Route::post('/ac/{id}/control', [AcControlController::class, 'control']);
        });

        Route::post('/rooms/{id}/ac/fuzzy/apply', [AcUnitController::class, 'applyFuzzy'])->name('ac.fuzzy.apply');
    });

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/check-exists', function (Request $request) {
            $name = strtolower(trim($request->query('name')));
            $exists = User::whereRaw('LOWER(name) = ?', [$name])->exists();

            return response()->json(['exists' => $exists]);
        });
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

    $cpuTempJson = function (string $source) {
        $temp = Cache::get("{$source}_temperature");
        $at = Cache::get("{$source}_temperature_at");

        // Umur data (detik) sejak pengirim terakhir kirim suhu.
        $ageSeconds = $at !== null ? max(0, now()->timestamp - (int) $at) : null;

        // Online jika ada data & umurnya <= 180 detik (3x interval kirim 60s).
        $isOnline = $temp !== null && $ageSeconds !== null && $ageSeconds <= 180;

        return response()->json([
            'suhu' => $temp !== null ? $temp.' °C' : null,
            'value' => $temp,
            'online' => $isOnline,
            'age' => $ageSeconds,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    };

    // /suhu-raspi (legacy, dari raspi/temperature) & /suhu-server (dari server/temperature)
    Route::get('/suhu-raspi', fn () => $cpuTempJson('raspi'));
    Route::get('/suhu-server', fn () => $cpuTempJson('server'));

    Route::get('/raspi-monitor', function () {
        return \Inertia\Inertia::render('ServerTemperature');
    })->name('monitoring');

});
