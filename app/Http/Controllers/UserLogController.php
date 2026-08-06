<?php

namespace App\Http\Controllers;

use App\Events\UserLogsCleared;
use App\Models\User;
use App\Models\UserLog;
use Barryvdh\DomPDF\Facade\Pdf;
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
            'update_settings' => ['SETTINGS', 'act-cyan'],
            default => [strtoupper($activity), 'act-lavender'],
        };
    }

    /**
     * Activity groupings shared by the list view and the PDF export.
     *
     * @return array<string, array<int, string>>
     */
    private function activityGroups(): array
    {
        return [
            'auth' => ['login', 'logout', 'change_password'],
            'ac' => ['on', 'off', 'bulk_on', 'bulk_off', 'timer_on', 'timer_off', 'set_timer_delete', 'control_ac'],
            'acLikes' => ['set_temp_%', 'mode_%', 'fan_speed_%', 'swing_%', 'set_timer:%'],
            'user' => ['add_user', 'delete_user', 'update_role'],
            'room' => ['add_room', 'delete_room', 'add_ac', 'delete_ac'],
            'destructive' => ['delete_user', 'delete_room', 'delete_ac'],
        ];
    }

    /**
     * Build the filtered log query. Shared by index() and exportPdf() so the
     * PDF always contains exactly the rows the admin is looking at — if these
     * ever drift apart, the export silently stops matching the screen.
     */
    private function filteredQuery(Request $request)
    {
        $g = $this->activityGroups();
        $authActs = $g['auth'];
        $acActs = $g['ac'];
        $acLikes = $g['acLikes'];
        $userActs = $g['user'];
        $roomActs = $g['room'];

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
                'auth' => $query->whereIn('activity', $authActs),
                'ac' => $applyAcFilter($query),
                'user' => $query->whereIn('activity', $userActs),
                'room' => $query->whereIn('activity', $roomActs),
                'power_on' => $query->whereIn('activity', ['on', 'bulk_on', 'timer_on']),
                'power_off' => $query->whereIn('activity', ['off', 'bulk_off', 'timer_off']),
                'temp' => $query->where('activity', 'like', 'set_temp_%'),
                'mode' => $query->where('activity', 'like', 'mode_%'),
                'fan' => $query->where('activity', 'like', 'fan_speed_%'),
                'swing' => $query->where('activity', 'like', 'swing_%'),
                'user_mgmt' => $query->whereIn('activity', $userActs),
                'room_mgmt' => $query->whereIn('activity', $roomActs),
                default => null,
            };
        }

        // Date preset (range=24h|today|7d|30d) overrides date_from/date_to.
        // '24h' is a rolling 24-hour window, distinct from 'today' which is the
        // current calendar day — at 01:00 'today' would return only one hour.
        $range = $request->input('range');
        if ($range === '24h') {
            $query->where('created_at', '>=', now()->subDay());
        } elseif ($range === 'today') {
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

        if (! in_array($sort, ['user_name', 'room', 'activity', 'created_at'])) {
            $sort = 'created_at';
        }
        if (! in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        if ($sort === 'user_name') {
            $query->leftJoin('users', 'user_logs.user_id', '=', 'users.id')
                ->orderBy('users.name', $order)
                ->select('user_logs.*');
        } else {
            $query->orderBy($sort, $order);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $g = $this->activityGroups();
        $acActs = $g['ac'];
        $acLikes = $g['acLikes'];
        $destructiveActs = $g['destructive'];
        $range = $request->input('range');

        $logs = $this->filteredQuery($request)->paginate(25)->withQueryString();

        // Stats — selalu dihitung dari seluruh data (tidak terpengaruh filter), kecuali date range
        $statsScope = UserLog::query();
        if ($range === '24h') {
            $statsScope->where('created_at', '>=', now()->subDay());
        } elseif ($range === 'today') {
            $statsScope->whereDate('created_at', now()->toDateString());
        } elseif ($range === '7d') {
            $statsScope->where('created_at', '>=', now()->subDays(7));
        } elseif ($range === '30d') {
            $statsScope->where('created_at', '>=', now()->subDays(30));
        }

        $stats = [
            'total' => (clone $statsScope)->count(),
            'add_room' => (clone $statsScope)->where('activity', 'add_room')->count(),
            'add_room24' => (clone $statsScope)->where('activity', 'add_room')
                ->where('created_at', '>=', now()->subDay())->count(),
            'delete_room' => (clone $statsScope)->where('activity', 'delete_room')->count(),
            'delete_room24' => (clone $statsScope)->where('activity', 'delete_room')
                ->where('created_at', '>=', now()->subDay())->count(),
            'ac' => (clone $statsScope)->where(function ($qq) use ($acActs, $acLikes) {
                $qq->whereIn('activity', $acActs);
                foreach ($acLikes as $like) {
                    $qq->orWhere('activity', 'like', $like);
                }
            })->count(),
            'destructive' => (clone $statsScope)->whereIn('activity', $destructiveActs)->count(),
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

    /**
     * Splitting the table per page (see the report view) turned dompdf's
     * quadratic reflow into linear scaling. Measured with the real template
     * on desktop: 500 rows 3.7 s / 96 MB, 1000 rows 7.7 s / 150 MB, 1500 rows
     * 12.9 s / 204 MB — versus 5.3 s / 142 MB for 500 rows before the split.
     *
     * 1000 is the cap because a Raspberry Pi 3 runs several times slower and
     * shares 1 GB with nginx, php-fpm, the MQTT subscriber and Reverb: 150 MB
     * sits well inside the 256 MB request limit below, while 1500 rows would
     * push past 200 MB and risk an OOM that takes down more than the export.
     */
    private const PDF_MAX_ROWS = 1000;

    /**
     * Rows per PDF page. Sized to fit A4 landscape including the first page's
     * report header, so no chunk overflows and leaves a near-empty page.
     */
    private const PDF_ROWS_PER_PAGE = 28;

    /**
     * Download the currently filtered activity log as a PDF report.
     *
     * Reuses filteredQuery() so the PDF matches the on-screen list exactly.
     * When PDF_MAX_ROWS trims the result the PDF says so on its face rather
     * than silently handing over an incomplete report.
     */
    public function exportPdf(Request $request)
    {
        // Scoped to this request only. dompdf is synchronous and memory-hungry;
        // without this the Pi's default limits can abort mid-render and return
        // a broken download instead of a clear failure.
        ini_set('memory_limit', '256M');
        set_time_limit(120);

        $query = $this->filteredQuery($request);
        $total = (clone $query)->count();
        $logs = $query->limit(self::PDF_MAX_ROWS)->get();

        $rows = $logs->map(function (UserLog $log) {
            [$label] = $this->activityBadge((string) $log->activity);
            $isEmpty = fn ($v) => $v === null || $v === '' || $v === '-' || $v === '—';

            return [
                'datetime' => $log->created_at?->format('d/m/Y H:i') ?? '—',
                'user' => $log->user?->name ?? '—',
                'room' => $isEmpty($log->room) ? '—' : $log->room,
                'ac' => $isEmpty($log->ac) ? '—' : $log->ac,
                'activity' => $label,
            ];
        });

        // Ringkasan filter supaya pembaca PDF tahu data ini hasil saringan apa.
        $rangeLabels = [
            '24h' => '24 jam terakhir',
            'today' => 'Hari ini',
            '7d' => '7 hari terakhir',
            '30d' => '30 hari terakhir',
        ];
        $activityLabels = [
            'auth' => 'Autentikasi', 'ac' => 'Kontrol AC', 'user' => 'Manajemen User',
            'room' => 'Manajemen Ruangan', 'power_on' => 'Power ON', 'power_off' => 'Power OFF',
            'temp' => 'Ubah Suhu', 'mode' => 'Ubah Mode', 'fan' => 'Ubah Fan', 'swing' => 'Ubah Swing',
            'user_mgmt' => 'Manajemen User', 'room_mgmt' => 'Manajemen Ruangan',
        ];

        $filters = [];
        if ($request->filled('range')) {
            $filters['Periode'] = $rangeLabels[$request->range] ?? $request->range;
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            $filters['Periode'] = trim(($request->date_from ?: '…').' s/d '.($request->date_to ?: '…'));
        }
        if ($request->filled('activity')) {
            $filters['Jenis Aktivitas'] = $activityLabels[$request->activity] ?? $request->activity;
        }
        if ($request->filled('room')) {
            $filters['Ruangan'] = $request->room;
        }
        if ($request->filled('search')) {
            $filters['Pencarian'] = $request->search;
        }
        if ($request->filled('user_id')) {
            $filters['User'] = User::find($request->user_id)?->name ?? $request->user_id;
        }

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => '-',
            'ac' => '-',
            'activity' => 'export_logs',
        ]);

        $pdf = Pdf::loadView('reports.activity-log', [
            'rows' => $rows,
            'total' => $total,
            'shown' => $rows->count(),
            'truncated' => $total > $rows->count(),
            'filters' => $filters,
            'perPage' => self::PDF_ROWS_PER_PAGE,
            'generatedAt' => now('Asia/Jakarta')->format('d F Y, H:i').' WIB',
            'generatedBy' => Auth::user()?->name ?? '—',
        ])->setPaper('a4', 'landscape');

        return $pdf->download('activity-log-'.now('Asia/Jakarta')->format('Ymd-His').'.pdf');
    }

    public function destroyAll(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user || $user->role !== 'admin') {
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

        Log::warning('User wiped activity log', [
            'admin_id' => $user->id,
            'admin_name' => $user->name,
            'deleted_count' => $totalDeleted,
        ]);

        try {
            event(new UserLogsCleared);
        } catch (\Throwable $e) {
            Log::warning('Broadcast UserLogsCleared gagal: '.$e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Semua log berhasil dihapus']);
        }

        return back()->with('success', 'Semua log berhasil dihapus');
    }
}
