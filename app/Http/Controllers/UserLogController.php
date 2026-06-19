<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class UserLogController extends Controller
{
    /**
     * Map a raw activity string to a display badge [label, class].
     *
     * @return array{0: string, 1: string}
     */
    private function activityBadge(string $activity): array
    {
        if (str_starts_with($activity, 'set_temp_')) {
            return ['TEMP '.str_replace('set_temp_', '', $activity).'°C', 'act-amber'];
        }
        if (str_starts_with($activity, 'mode_')) {
            return ['MODE '.strtoupper(str_replace('mode_', '', $activity)), 'act-cyan'];
        }
        if (str_starts_with($activity, 'fan_speed_')) {
            return ['FAN '.strtoupper(str_replace('fan_speed_', '', $activity)), 'act-cyan'];
        }
        if (str_starts_with($activity, 'swing_')) {
            return ['SWING '.strtoupper(str_replace('swing_', '', $activity)), 'act-lavender'];
        }
        if (str_starts_with($activity, 'set_timer')) {
            $detail = substr($activity, 9);
            $on = preg_match('/ON\s+(\d{2}:\d{2})/i', $detail, $mOn) ? $mOn[1] : null;
            $off = preg_match('/OFF\s+(\d{2}:\d{2})/i', $detail, $mOff) ? $mOff[1] : null;
            if ($on && $off) {
                return ["Timer ON {$on} · OFF {$off}", 'act-amber'];
            }
            if ($on) {
                return ["Timer ON {$on}", 'act-amber'];
            }
            if ($off) {
                return ["Timer OFF {$off}", 'act-amber'];
            }

            return ['Set Timer', 'act-amber'];
        }

        return match ($activity) {
            'login' => ['LOGIN', 'act-mint'],
            'logout' => ['LOGOUT', 'act-slate'],
            'on' => ['POWER ON', 'act-mint'],
            'off' => ['POWER OFF', 'act-coral'],
            'bulk_on' => ['ALL ON', 'act-mint'],
            'bulk_off' => ['ALL OFF', 'act-coral'],
            'set_timer' => ['SET TIMER', 'act-amber'],
            'timer_on' => ['TIMER ON', 'act-mint'],
            'timer_off' => ['TIMER OFF', 'act-amber'],
            'control_ac' => ['CONTROL AC', 'act-lavender'],
            'add_room' => ['ADD ROOM', 'act-cyan'],
            'delete_room' => ['DELETE ROOM', 'act-coral'],
            'add_ac' => ['ADD AC', 'act-cyan'],
            'delete_ac' => ['DELETE AC', 'act-coral'],
            'add_user' => ['ADD USER', 'act-lavender'],
            'delete_user' => ['DELETE USER', 'act-coral'],
            'update_role' => ['UPDATE ROLE', 'act-lavender'],
            'change_password' => ['CHG PASSWORD', 'act-amber'],
            default => [strtoupper($activity), 'act-lavender'],
        };
    }
    public function index(Request $request)
    {
        $authActs = ['login', 'logout', 'change_password'];
        $acActs   = ['on', 'off', 'bulk_on', 'bulk_off', 'timer_on', 'timer_off', 'set_timer_delete', 'control_ac'];
        $acLikes  = ['set_temp_%', 'mode_%', 'fan_speed_%', 'swing_%', 'set_timer:%'];
        $userActs = ['add_user', 'delete_user', 'update_role'];
        $roomActs = ['add_room', 'delete_room', 'add_ac', 'delete_ac'];
        $destructiveActs = ['delete_user', 'delete_room', 'delete_ac'];

        $applyAcFilter = function ($q) use ($acActs, $acLikes) {
            $q->where(function ($qq) use ($acActs, $acLikes) {
                $qq->whereIn('activity', $acActs);
                foreach ($acLikes as $like) {
                    $qq->orWhere('activity', 'like', $like);
                }
            });
        };

        $query = UserLog::with('user:id,name,avatar')->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('room')) {
            $query->where('room', $request->room);
        }

        if ($request->filled('activity')) {
            match ($request->activity) {
                'auth'      => $query->whereIn('activity', $authActs),
                'ac'        => $applyAcFilter($query),
                'user'      => $query->whereIn('activity', $userActs),
                'room'      => $query->whereIn('activity', $roomActs),
                'power_on'  => $query->whereIn('activity', ['on', 'bulk_on', 'timer_on']),
                'power_off' => $query->whereIn('activity', ['off', 'bulk_off', 'timer_off']),
                'temp'      => $query->where('activity', 'like', 'set_temp_%'),
                'mode'      => $query->where('activity', 'like', 'mode_%'),
                'fan'       => $query->where('activity', 'like', 'fan_speed_%'),
                'swing'     => $query->where('activity', 'like', 'swing_%'),
                'user_mgmt' => $query->whereIn('activity', $userActs),
                'room_mgmt' => $query->whereIn('activity', $roomActs),
                default     => null,
            };
        }

        // Date preset (range=today|7d|30d) overrides date_from/date_to
        $range = $request->input('range');
        if ($range === 'today') {
            $query->whereDate('created_at', now()->toDateString());
        } elseif ($range === '7d') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($range === '30d') {
            $query->where('created_at', '>=', now()->subDays(30));
        } else {
            if ($request->filled('date_from') && strtotime($request->date_from) !== false) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to') && strtotime($request->date_to) !== false) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('room', 'like', "%{$s}%")
                  ->orWhere('ac', 'like', "%{$s}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%"));
            });
        }

        // Sorting
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');

        if (!in_array($sort, ['user_name', 'room', 'activity', 'created_at'])) {
            $sort = 'created_at';
        }
        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        if ($sort === 'user_name') {
            $query->leftJoin('users', 'user_logs.user_id', '=', 'users.id')
                  ->orderBy('users.name', $order)
                  ->select('user_logs.*');
        } else {
            $query->orderBy($sort, $order);
        }

        $logs = $query->paginate(25)->withQueryString();

        // Stats — selalu dihitung dari seluruh data (tidak terpengaruh filter), kecuali date range
        $statsScope = UserLog::query();
        if ($range === 'today') {
            $statsScope->whereDate('created_at', now()->toDateString());
        } elseif ($range === '7d') {
            $statsScope->where('created_at', '>=', now()->subDays(7));
        } elseif ($range === '30d') {
            $statsScope->where('created_at', '>=', now()->subDays(30));
        }

        $stats = [
            'total'         => (clone $statsScope)->count(),
            'add_room'      => (clone $statsScope)->where('activity', 'add_room')->count(),
            'add_room24'    => (clone $statsScope)->where('activity', 'add_room')
                                 ->where('created_at', '>=', now()->subDay())->count(),
            'delete_room'   => (clone $statsScope)->where('activity', 'delete_room')->count(),
            'delete_room24' => (clone $statsScope)->where('activity', 'delete_room')
                                 ->where('created_at', '>=', now()->subDay())->count(),
            'ac'            => (clone $statsScope)->where(function ($qq) use ($acActs, $acLikes) {
                                 $qq->whereIn('activity', $acActs);
                                 foreach ($acLikes as $like) {
                                     $qq->orWhere('activity', 'like', $like);
                                 }
                             })->count(),
            'destructive'   => (clone $statsScope)->whereIn('activity', $destructiveActs)->count(),
        ];

        $isEmpty = fn ($v) => $v === null || $v === '' || $v === '-' || $v === '—';

        $items = $logs->getCollection()->map(function (UserLog $log) use ($isEmpty) {
            [$label, $class] = $this->activityBadge((string) $log->activity);

            return [
                'id' => $log->id,
                'user_name' => $log->user?->name ?? '—',
                'user_avatar' => $log->user?->avatar_url,
                'room' => $isEmpty($log->room) ? null : $log->room,
                'ac' => $isEmpty($log->ac) ? null : $log->ac,
                'badge_label' => $label,
                'badge_class' => $class,
                'time' => $log->created_at?->format('H:i'),
                'date' => $log->created_at?->format('d M Y'),
            ];
        })->values();

        return Inertia::render('ActivityLog', [
            'logs' => $items,
            'stats' => $stats,
            'filters' => [
                'search' => $request->query('search', ''),
                'activity' => $request->query('activity', ''),
                'range' => $request->query('range', ''),
            ],
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'from' => $logs->firstItem() ?? 0,
                'to' => $logs->lastItem() ?? 0,
                'total' => $logs->total(),
                'prev_url' => $logs->previousPageUrl(),
                'next_url' => $logs->nextPageUrl(),
            ],
        ]);
    }

public function destroyAll(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            abort(403);
        }

        $totalDeleted = UserLog::count();

        UserLog::query()->delete();

        UserLog::create([
            'user_id' => $user->id,
            'room' => '-',
            'ac' => '-',
            'activity' => 'clear_logs',
        ]);

        \Illuminate\Support\Facades\Log::warning('User wiped activity log', [
            'admin_id' => $user->id,
            'admin_name' => $user->name,
            'deleted_count' => $totalDeleted,
        ]);

        try {
            event(new \App\Events\UserLogsCleared());
        } catch (\Throwable $e) {
            Log::warning('Broadcast UserLogsCleared gagal: '.$e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Semua log berhasil dihapus']);
        }

        return back()->with('success', 'Semua log berhasil dihapus');
    }
}
