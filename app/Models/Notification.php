<?php

namespace App\Models;

use App\Events\NotificationCreated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Notification extends Model
{
    private const STATE_TTL_DAYS = 30;

    protected $fillable = [
        'user_id',
        'type',
        'severity',
        'title',
        'message',
        'link',
        'meta',
        'read_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reads()
    {
        return $this->hasMany(NotificationRead::class);
    }

    protected static function booted(): void
    {
        static::created(function (Notification $notification) {
            // Broadcasting opsional: jangan gagalkan request kalau Reverb mati.
            try {
                event(new NotificationCreated($notification));
            } catch (\Throwable $e) {
                Log::warning('Broadcast NotificationCreated gagal: '.$e->getMessage());
            }
        });
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeUnreadForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            // Personal unread
            $q->where('user_id', $userId)->whereNull('read_at');
            // Broadcast unread (no pivot row for this user)
            $q->orWhere(function ($qq) use ($userId) {
                $qq->whereNull('user_id')
                    ->whereDoesntHave('reads', fn ($r) => $r->where('user_id', $userId));
            });
        });
    }

    public function scopeForUserOrBroadcast(Builder $query, ?int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('user_id');
            if ($userId) {
                $q->orWhere('user_id', $userId);
            }
        });
    }

    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhere(function ($qq) use ($user) {
                    $qq->whereNull('user_id')
                        ->where('created_at', '>=', $user->created_at);
                });
        });
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function isUnreadForUser(int $userId): bool
    {
        if ($this->user_id !== null) {
            return $this->read_at === null;
        }

        // Broadcast: check pivot (reads must be eager-loaded for this user)
        return $this->reads->where('user_id', $userId)->isEmpty();
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    public static function notify(string $type, string $title, array $opts = []): self
    {
        return self::create([
            'user_id' => $opts['user_id'] ?? null,
            'type' => $type,
            'severity' => $opts['severity'] ?? 'info',
            'title' => $title,
            'message' => $opts['message'] ?? null,
            'link' => $opts['link'] ?? null,
            'meta' => $opts['meta'] ?? null,
        ]);
    }

    public static function deviceOffline(string $roomName, ?string $deviceId = null): ?self
    {
        $roomKey = self::roomKey($roomName);
        $stateKey = "notification_state:device:{$roomKey}";
        $prevState = Cache::get($stateKey);

        if ($prevState === 'offline') {
            return null;
        }

        Cache::put($stateKey, 'offline', now()->addDays(self::STATE_TTL_DAYS));

        $room = 'Ruangan '.ucwords($roomName);

        return self::notify('device_offline', "{$room} offline", [
            'severity' => 'error',
            'message' => "{$room} tidak terhubung. Cek koneksi WiFi atau power.",
            'meta' => ['room' => $roomName, 'device_id' => $deviceId],
        ]);
    }

    public static function deviceOnline(string $roomName, ?string $deviceId = null): ?self
    {
        $roomKey = self::roomKey($roomName);
        $stateKey = "notification_state:device:{$roomKey}";
        $prevState = Cache::get($stateKey);

        if ($prevState === 'online') {
            return null;
        }

        Cache::put($stateKey, 'online', now()->addDays(self::STATE_TTL_DAYS));

        if ($prevState !== 'offline') {
            return null;
        }

        $room = 'Ruangan '.ucwords($roomName);

        return self::notify('device_online', "{$room} online", [
            'severity' => 'info',
            'message' => "{$room} terhubung kembali.",
            'meta' => ['room' => $roomName, 'device_id' => $deviceId],
        ]);
    }

    public static function fuzzyAction(string $roomName, string $action, int $setpointBefore, int $setpointAfter): ?self
    {
        $roomKey = self::roomKey($roomName);
        $stateKey = "notification_state:fuzzy_action:{$roomKey}";
        $currentKey = "{$action}:{$setpointBefore}:{$setpointAfter}";
        $prevKey = Cache::get($stateKey);

        if ($prevKey === $currentKey) {
            return null;
        }

        Cache::put($stateKey, $currentKey, now()->addDays(self::STATE_TTL_DAYS));

        $title = "Fuzzy Logic: {$roomName}";
        $message = self::buildFuzzyMessage($roomName, $action, $setpointBefore, $setpointAfter);
        $severity = $action === 'DIAM' ? 'info' : 'warning';

        return self::notify('fuzzy_action', $title, [
            'severity' => $severity,
            'message' => $message,
            'meta' => [
                'room' => $roomName,
                'action' => $action,
                'setpoint_before' => $setpointBefore,
                'setpoint_after' => $setpointAfter,
            ],
        ]);
    }

    private static function buildFuzzyMessage(string $roomName, string $action, int $before, int $after): string
    {
        $room = ucwords($roomName);

        return match ($action) {
            'TURUNKAN' => "AC {$room}: Sistem mendeteksi panas, mendinginkan ({$before}°C → {$after}°C)",
            'NAIKKAN' => "AC {$room}: Sistem mendeteksi dingin, memanaskan ({$before}°C → {$after}°C)",
            default => "AC {$room}: Status stabil ({$before}°C)",
        };
    }

    public static function fuzzyWarning(string $roomName, string $reason = 'temperature_offline'): ?self
    {
        $roomKey = self::roomKey($roomName);
        $stateKey = "notification_state:fuzzy_warning:{$roomKey}:{$reason}";
        $lastWarning = Cache::get($stateKey);

        // Sudah pernah notif untuk reason ini → skip sampai recovery
        if ($lastWarning === 'warned') {
            return null;
        }

        // Clear opposite reason state so it doesn't get stuck
        // (e.g. when ESP came back but sensor went stale)
        $otherReasons = ['temperature_offline', 'device_offline'];
        foreach ($otherReasons as $other) {
            if ($other !== $reason) {
                Cache::forget("notification_state:fuzzy_warning:{$roomKey}:{$other}");
            }
        }

        // TTL panjang (7 hari) — tidak expire selama belum recovery
        Cache::put($stateKey, 'warned', now()->addDays(self::STATE_TTL_DAYS));

        $message = match ($reason) {
            'device_offline' => 'ESP ruangan '.ucwords($roomName).' offline — Fuzzy logic tidak berjalan. Periksa koneksi device.',
            default => 'Sensor suhu ruangan '.ucwords($roomName).' offline — Fuzzy logic tidak berjalan. Periksa koneksi sensor.',
        };

        return self::notify('fuzzy_warning', "Fuzzy Logic: {$roomName}", [
            'severity' => 'error',
            'message' => $message,
            'meta' => ['room' => $roomName, 'reason' => $reason],
        ]);
    }

    public static function fuzzyRecovery(string $roomName, bool $notify = true): ?self
    {
        // Cek SEMUA reason keys (temperature & device offline)
        $reasons = ['temperature_offline', 'device_offline'];
        $recoveredReasons = [];
        $roomKey = self::roomKey($roomName);

        foreach ($reasons as $reason) {
            $key = "notification_state:fuzzy_warning:{$roomKey}:{$reason}";
            if (Cache::has($key)) {
                $recoveredReasons[] = $reason;
                Cache::forget($key);
            }
        }

        // Tidak ada warning sebelumnya → tidak perlu notif recovery
        if (empty($recoveredReasons) || ! $notify) {
            return null;
        }

        $hasBoth = count($recoveredReasons) === 2;
        $message = $hasBoth
            ? 'ESP dan sensor suhu ruangan '.ucwords($roomName).' online — Fuzzy logic aktif kembali.'
            : (in_array('device_offline', $recoveredReasons, true)
                ? 'ESP ruangan '.ucwords($roomName).' online — Fuzzy logic aktif kembali.'
                : 'Sensor suhu ruangan '.ucwords($roomName).' online — Fuzzy logic aktif kembali.');

        return self::notify('fuzzy_recovery', "Fuzzy Logic: {$roomName}", [
            'severity' => 'info',
            'message' => $message,
            'meta' => ['room' => $roomName, 'reason' => 'recovered'],
        ]);
    }

    private static function roomKey(string $roomName): string
    {
        return strtolower(preg_replace('/\s+/', '_', trim($roomName)));
    }
}
