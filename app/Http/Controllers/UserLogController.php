<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserLogController extends Controller
{
    public function index(Request $request)
    {
        $authActs = ['login', 'logout', 'change_password'];
        $acActs   = ['on', 'off', 'bulk_on', 'bulk_off', 'timer_on', 'timer_off', 'set_timer', 'control_ac'];
        $acLikes  = ['set_temp_%', 'mode_%', 'fan_speed_%', 'swing_%'];
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
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
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

        $logs  = $query->paginate(25)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);
        $rooms = UserLog::whereNotNull('room')->distinct()->orderBy('room')->pluck('room');

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

        return view('logs.index', compact('logs', 'users', 'rooms', 'stats'));
    }

public function destroyAll(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            abort(403);
        }

        $totalDeleted = UserLog::count();

        // Delete all rows but keep autoincrement and FK behavior consistent
        UserLog::query()->delete();

        // Write a fresh audit entry AFTER the wipe so the action itself is traceable
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

        event(new \App\Events\UserLogsCleared());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Semua log berhasil dihapus']);
        }

        return back()->with('success', 'Semua log berhasil dihapus');
    }
}
