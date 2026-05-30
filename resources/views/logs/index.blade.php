<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Activity Log — SmartAC</title>
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite('resources/js/app.js')
    @include('components.sidebar-styles')
    <style>
        .toolbar-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .toolbar-row .search-input { flex: 1; min-width: 240px; }


        .stat-card .stat-label-sm {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--ink-3);
        }

        .stat-card .stat-num-lg {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            font-feature-settings: 'tnum' 1, 'lnum' 1, 'cv11' 1;
            font-size: 36px;
            font-weight: 700;
            line-height: 1;
            letter-spacing: -0.02em;
            margin: 8px 0 6px;
        }

        .stat-card .stat-sub {
            font-size: 11px;
            color: var(--ink-3);
        }

        /* Lock badge ikon: cegah glyph FA dengan tinggi natural beda menarik badge jadi lebih besar */
        .stat-card .stat-icon {
            box-sizing: border-box;
            flex-shrink: 0;
            line-height: 1;
            overflow: hidden;
        }
        .stat-card .stat-icon > i {
            font-size: inherit;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1em;
            height: 1em;
        }

        /* Angka utama selalu putih untuk hierarki yang kuat */
        .stat-card .stat-num-lg { color: var(--ink-0); }

        /* Label kecil di atas mengambil warna accent per kartu */
        .stat-card.acc-cyan     .stat-label-sm { color: var(--cyan); }
        .stat-card.acc-mint     .stat-label-sm { color: var(--mint); }
        .stat-card.acc-lavender .stat-label-sm { color: var(--lavender); }
        .stat-card.acc-coral    .stat-label-sm { color: var(--coral); }

        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px 3px 10px;
            border-radius: 999px;
            background: rgba(77, 212, 255, 0.1);
            border: 1px solid rgba(77, 212, 255, 0.25);
            font-size: 11px;
            color: var(--cyan);
        }

        .filter-tag button {
            background: none;
            border: none;
            color: var(--cyan);
            cursor: pointer;
            padding: 0;
            line-height: 1;
            opacity: 0.7;
        }

        .filter-tag button:hover { opacity: 1; }

        /* Toolbar responsiveness for very small screens */
        @media (max-width: 768px) {
            .tbl-toolbar {
                gap: 6px;
                padding: 8px 10px;
            }

            .tbl-toolbar label.search-input {
                flex: 1;
                min-width: 0;
            }

            .tbl-toolbar > div {
                display: inline-flex;
                flex-wrap: nowrap;
                gap: 1px;
                align-items: center;
                flex-shrink: 0;
            }

            .segmented {
                display: inline-flex;
                gap: 1px;
            }

            .segmented .seg {
                font-size: 10.5px;
                padding: 5px 8px;
            }

            .btn.btn-danger {
                padding: 5px 8px;
                font-size: 10.5px;
                white-space: nowrap;
            }

            .search-input input {
                font-size: 11px;
                padding: 6px 10px 6px 36px;
            }

            .search-input i {
                font-size: 12px;
                left: 10px;
            }
        }

        /* Very small screens (< 480px) — wrap toolbar to 2 rows */
        @media (max-width: 480px) {
            .tbl-toolbar {
                padding: 8px;
                gap: 8px;
                flex-wrap: wrap;
                row-gap: 8px;
            }

            .tbl-toolbar label.search-input {
                flex: 1 1 100%;
                min-width: 0;
                width: 100%;
            }

            .tbl-toolbar > div {
                display: flex;
                gap: 6px;
                align-items: center;
                flex: 1 1 100%;
                justify-content: flex-start;
                flex-shrink: 0;
            }

            /* Unify height across search / segmented / btn — 36 px */
            .tbl-toolbar .search-input {
                height: 36px;
            }

            .tbl-toolbar .search-input input {
                height: 36px;
                padding: 0 12px 0 34px;
                font-size: 12px;
                box-sizing: border-box;
            }

            .tbl-toolbar .segmented {
                flex: 1 1 auto;
                display: flex;
                height: 36px;
                padding: 2px;
                box-sizing: border-box;
            }

            .tbl-toolbar .segmented .seg {
                flex: 1 1 0;
                min-width: 0;
                text-align: center;
                height: 100%;
                padding: 0 6px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
            }

            .tbl-toolbar .btn {
                flex-shrink: 0;
                height: 36px;
                min-height: 36px;
                padding: 0 12px;
                font-size: 11px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-sizing: border-box;
            }

            .tbl-toolbar .btn.btn-danger i {
                margin: 0 !important;
                font-size: 13px;
            }

            .search-input input::placeholder {
                color: var(--ink-3);
            }

            .search-input i {
                font-size: 12px;
                left: 12px;
            }
        }

        .tbl tbody tr { transition: background 0.12s ease; }
        .tbl tbody tr:hover { background: var(--panel-2); }

        .tbl.tbl-log thead {
            background: transparent;
        }

        .tbl.tbl-log th {
            font-size: 11.5px;
            letter-spacing: 0.1em;
            padding: 14px 18px;
            background: transparent;
            color: var(--ink-0);
        }

        .tbl.tbl-log td {
            padding: 14px 18px;
            vertical-align: middle;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            font-size: 14px;
        }

        .tbl.tbl-log tbody tr:first-child td { border-top: none; }

        .log-empty { color: var(--ink-5, var(--ink-4)); opacity: 0.5; }

        .log-user {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .log-user .name {
            color: var(--ink-0);
            font-weight: 600;
            font-size: 14.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .log-room {
            color: var(--ink-1);
            font-size: 14px;
        }

        .log-detail {
            color: var(--ink-2);
            font-size: 13.5px;
            max-width: 260px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .log-time {
            display: flex;
            flex-direction: column;
            line-height: 1.35;
            font-family: 'JetBrains Mono', monospace;
            white-space: nowrap;
        }

        .log-time .t { color: var(--ink-1); font-size: 14px; font-weight: 600; }
        .log-time .d { color: var(--ink-4); font-size: 11.5px; }

        /* Sortable table headers */
        .tbl-log th {
            cursor: pointer;
            user-select: none;
            position: relative;
            transition: background 0.12s ease;
        }

        .tbl-log th:hover {
            background: var(--panel-2);
        }

        .tbl-log th.sortable::after {
            content: '';
            display: inline-block;
            width: 12px;
            height: 12px;
            margin-left: 6px;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12" fill="%23999"><path d="M3 2L6 0l3 2M3 10L6 12l3-2"/></svg>') center no-repeat;
            background-size: contain;
            opacity: 0.4;
            vertical-align: -1px;
        }

        .tbl-log th.sort-asc::after {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12" fill="%234dd4ff"><path d="M6 1L3 4h6z"/></svg>');
            opacity: 1;
        }

        .tbl-log th.sort-desc::after {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12" fill="%234dd4ff"><path d="M6 11L3 8h6z"/></svg>');
            opacity: 1;
        }

        /* Enhanced pagination */
        .pager {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .pager a, .pager span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            color: var(--ink-2);
            background: transparent;
            border: 1px solid transparent;
            text-decoration: none;
            transition: all 0.12s ease;
        }

        .pager a {
            cursor: pointer;
        }

        .pager a:hover {
            background: var(--panel-2);
            color: var(--ink-0);
            border-color: var(--line);
        }

        .pager .active {
            background: var(--cyan-soft);
            color: var(--cyan);
            border-color: var(--cyan-soft-2);
            font-weight: 600;
        }

        .pager .disabled {
            opacity: 0.35;
            pointer-events: none;
            cursor: not-allowed;
        }

        .pager i {
            opacity: 0.7;
        }

        .pager a:hover i {
            opacity: 1;
        }

        /* Page sections spacing */
        .app-content-inner > * + * {
            margin-top: 32px;
        }

        /* Tablet & desktop (≥ 481 px): unify search / segmented / button height to 40 px */
        @media (min-width: 481px) {
            .tbl-toolbar .search-input { height: 40px; }
            .tbl-toolbar .search-input input { height: 40px; box-sizing: border-box; }
            .tbl-toolbar .segmented {
                height: 40px;
                box-sizing: border-box;
                display: inline-flex;
                align-items: center;
            }
            .tbl-toolbar .segmented .seg {
                height: 100%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-sizing: border-box;
            }
            .tbl-toolbar .btn {
                height: 40px;
                min-height: 40px;
                box-sizing: border-box;
                padding: 0 14px;
            }
        }

        /* Header keep on one row across breakpoints */
        .main-header { flex-wrap: nowrap; }
        .main-header > .flex.items-center.gap-3 { min-width: 0; flex: 1; }
        .main-header > .flex.items-center.gap-2 { flex-shrink: 0; }

        /* Stat cards — Mobile L (≤ 640px) */
        @media (max-width: 640px) {
            .grid.grid-cols-2.lg\:grid-cols-4 { gap: 12px; }
            .stat-card { padding: 14px 16px; }
            .stat-card .stat-label-sm { font-size: 9.5px; letter-spacing: 0.08em; }
            .stat-card .stat-num-lg { font-size: 28px; margin: 6px 0 4px; }
            .stat-card .stat-sub { font-size: 10px; line-height: 1.35; }
            .stat-card .stat-icon { width: 34px; height: 34px; border-radius: 10px; font-size: 14px; }
        }

        /* Stat cards — Mobile M (≤ 480px) */
        @media (max-width: 480px) {
            .grid.grid-cols-2.lg\:grid-cols-4 { gap: 10px; }
            .stat-card { padding: 12px 14px; }
            .stat-card .stat-label-sm { font-size: 8.5px; letter-spacing: 0.06em; white-space: nowrap; }
            .stat-card .stat-num-lg { font-size: 24px; margin: 4px 0 2px; }
            .stat-card .stat-sub { font-size: 9.5px; line-height: 1.3; }
            .stat-card .stat-icon { width: 30px; height: 30px; border-radius: 9px; font-size: 12px; }
            .stat-card .accent-bar { top: 12px; bottom: 12px; }
        }

        /* Mobile S (≤ 360 px): shrink header + extra-tight stat cards */
        @media (max-width: 360px) {
            .main-header { gap: 6px; padding-left: 10px; padding-right: 10px; }
            .main-header > .flex.items-center.gap-3 { gap: 6px; }
            .main-header > .flex.items-center.gap-2 { gap: 4px; }
            .main-header .app-header-title h1 { font-size: 13px; line-height: 1.2; }
            .main-header .app-header-title p { font-size: 9.5px; line-height: 1.2; }
            .main-header #systemStatus span:not(.dot) { display: none; }
            .main-header #systemStatus { padding: 4px 6px; }
            .main-header .btn-icon { width: 32px; height: 32px; }

            /* Stat cards: keep 2-col, compact each card so labels/subs don't wrap awkwardly */
            .grid.grid-cols-2.lg\:grid-cols-4 { gap: 8px !important; }
            .stat-card { padding: 10px 12px !important; }
            .stat-card .stat-label-sm { font-size: 9px !important; letter-spacing: 0.05em !important; white-space: nowrap !important; }
            .stat-card .stat-num-lg { font-size: 21px !important; margin: 3px 0 2px !important; line-height: 1.1 !important; }
            .stat-card .stat-sub { font-size: 9px !important; line-height: 1.3 !important; }
            .stat-card .stat-icon { width: 26px !important; height: 26px !important; border-radius: 8px !important; font-size: 11px !important; }
            .stat-card .accent-bar { top: 10px !important; bottom: 10px !important; }
        }
    </style>
</head>

<body>
    <div class="custom-bg"></div>
    <div id="overlay"></div>

    <div class="layout">
        @include('components.sidebar')

        <div class="main-content">
            <header class="main-header">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="lg:hidden btn-icon" title="Menu">
                        <i class="fa-solid fa-bars-staggered"></i>
                    </button>
                    <div class="app-header-title">
                        <h1>Activity Log</h1>
                        <p>System &amp; user activity</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @include('components.notification-bell')
                    <span id="systemStatus" class="pill pill-online">
                        <span class="dot"></span>
                        <span>Online</span>
                    </span>
                </div>
            </header>

            <div class="page-body">
                <div class="app-content">
                    <div class="app-content-inner space-y-4">

                        @php
                            function activityBadge($activity)
                            {
                                if (str_starts_with($activity, 'set_temp_')) {
                                    $val = str_replace('set_temp_', '', $activity);
                                    return ["TEMP {$val}°C", 'act-amber'];
                                }
                                if (str_starts_with($activity, 'mode_')) {
                                    $val = strtoupper(str_replace('mode_', '', $activity));
                                    return ["MODE {$val}", 'act-cyan'];
                                }
                                if (str_starts_with($activity, 'fan_speed_')) {
                                    $val = strtoupper(str_replace('fan_speed_', '', $activity));
                                    return ["FAN {$val}", 'act-cyan'];
                                }
                                if (str_starts_with($activity, 'swing_')) {
                                    $val = strtoupper(str_replace('swing_', '', $activity));
                                    return ["SWING {$val}", 'act-lavender'];
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

                            $activityOptions = [
                                'auth'      => 'Auth (login/logout)',
                                'ac'        => 'AC Control',
                                'room'      => 'Room',
                                'user'      => 'User',
                                'power_on'  => 'Power ON',
                                'power_off' => 'Power OFF',
                                'temp'      => 'Set Temperature',
                                'mode'      => 'Mode Change',
                                'fan'       => 'Fan Speed',
                                'swing'     => 'Swing',
                            ];

                            $rangeOptions = [
                                ''      => 'All time',
                                'today' => 'Today',
                                '7d'    => '7 Days',
                                '30d'   => '30 Days',
                            ];
                            $currentRange = request('range', '');
                            $rangeLabel = $rangeOptions[$currentRange] ?? 'Custom';

                            $activeFilters = array_filter(
                                request()->only(['user_id', 'room', 'activity', 'date_from', 'date_to', 'search', 'range']),
                            );

                            $quickCats = [
                                ''     => 'All',
                                'auth' => 'Auth',
                                'ac'   => 'AC',
                                'room' => 'Room',
                                'user' => 'User',
                            ];
                            $currentCat = in_array(request('activity'), ['auth', 'ac', 'room', 'user']) ? request('activity') : '';
                        @endphp

                        {{-- Stats — 4 kartu sesuai mockup --}}
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                            <div class="stat-card acc-cyan">
                                <span class="accent-bar"></span>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="stat-label-sm">Total Activity</p>
                                        <p class="stat-num-lg">{{ $stats['total'] }}</p>
                                        <p class="stat-sub">Page {{ $logs->currentPage() }} / {{ $logs->lastPage() }}</p>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                                </div>
                            </div>
                            <div class="stat-card acc-mint">
                                <span class="accent-bar"></span>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="stat-label-sm">Add Room</p>
                                        <p class="stat-num-lg">{{ $stats['add_room'] }}</p>
                                        <p class="stat-sub">+{{ $stats['add_room24'] }} dalam 24 jam</p>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-square-plus"></i></div>
                                </div>
                            </div>
                            <div class="stat-card acc-coral">
                                <span class="accent-bar"></span>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="stat-label-sm">Delete Room</p>
                                        <p class="stat-num-lg">{{ $stats['delete_room'] ?? 0 }}</p>
                                        <p class="stat-sub">+{{ $stats['delete_room24'] ?? 0 }} dalam 24 jam</p>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-trash"></i></div>
                                </div>
                            </div>
                            <div class="stat-card acc-lavender">
                                <span class="accent-bar"></span>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="stat-label-sm">AC Control</p>
                                        <p class="stat-num-lg">{{ $stats['ac'] }}</p>
                                        <p class="stat-sub">on/off · mode · temp</p>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-snowflake"></i></div>
                                </div>
                            </div>
                        </div>

                        {{-- Toolbar + Table wrapper (no space-y between them) --}}
                        <div class="tbl-wrap">
                        {{-- Toolbar: search + quick category + date range --}}
                        <form method="GET" action="/logs" id="filterForm">
                            <div class="tbl-toolbar">
                                <label class="search-input" style="flex:1;max-width:none;">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input name="search" value="{{ request('search') }}" type="text"
                                        placeholder="Search user / room / activity…" autocomplete="off">
                                    @if (request('search'))
                                        <button type="button" class="clear" title="Clear"
                                            onclick="removeFilter('search')"><i
                                                class="fa-solid fa-xmark text-[10px]"></i></button>
                                    @endif
                                </label>

                                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                                    <div class="segmented">
                                        @foreach ($quickCats as $val => $label)
                                            <button type="button"
                                                class="seg {{ $currentCat === $val ? 'active' : '' }}"
                                                data-quick="{{ $val }}">{{ $label }}</button>
                                        @endforeach
                                    </div>

                                    @if (Auth::user()->role == 'admin')
                                        <button type="button" onclick="deleteAllLogs()"
                                            class="btn btn-danger btn-sm" title="Delete All Logs">
                                            <i class="fa-solid fa-trash text-[10px]"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>

                        </form>

                        @php
                            $isEmpty = fn ($v) => $v === null || $v === '' || $v === '-' || $v === '—';
                        @endphp

                        {{-- Log table --}}
                            {{-- Mobile cards --}}
                            <div class="md:hidden" id="logsMobile">
                                @forelse ($logs as $log)
                                    @php
                                        [$label, $class] = activityBadge($log->activity);
                                        $roomAcText = collect([
                                            $isEmpty($log->room) ? null : $log->room,
                                            $isEmpty($log->ac) ? null : $log->ac,
                                        ])->filter()->implode(' · ');
                                        $avatarColors = ['cyan', 'mint', 'lavender', 'coral'];
                                        $avatarColor = $avatarColors[(($log->user_id ?? 0) - 1) % 4 < 0 ? 0 : (($log->user_id ?? 0) - 1) % 4];
                                    @endphp
                                    <div style="padding:14px 16px;border-bottom:1px solid rgba(255, 255, 255, 0.15);">
                                        <div class="flex items-center gap-3">
                                            @if ($log->user?->avatar_url)
                                                <img src="{{ $log->user->avatar_url }}" alt="{{ $log->user->name }}"
                                                     style="width:38px;height:38px;border-radius:9px;object-fit:cover;flex-shrink:0;">
                                            @else
                                                <div style="width:38px;height:38px;border-radius:9px;background:var(--{{ $avatarColor }});color:#0c1726;font-size:15px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                    {{ strtoupper(substr($log->user->name ?? '?', 0, 1)) }}
                                                </div>
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <span class="truncate" style="font-size:15px;font-weight:600;color:var(--ink-0);">{{ $log->user->name ?? '—' }}</span>
                                                        <span class="act-badge {{ $class }}" style="flex-shrink:0;">{{ $label }}</span>
                                                    </div>
                                                    <span class="text-mono" style="font-size:12.5px;color:var(--ink-2);white-space:nowrap;flex-shrink:0;font-weight:600;">{{ $log->created_at->format('H:i') }}</span>
                                                </div>
                                                <div class="flex items-center justify-between gap-2" style="margin-top:5px;">
                                                    <span class="truncate" style="font-size:13px;color:var(--ink-3);">{{ $roomAcText ?: '—' }}</span>
                                                    <span class="text-mono" style="font-size:11.5px;color:var(--ink-4);white-space:nowrap;flex-shrink:0;">{{ $log->created_at->format('d M Y') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                                        <p class="empty-title">No activities found</p>
                                        <p class="empty-sub">{{ count($activeFilters) ? 'Try adjusting your filters or ' : '' }}<a href="/logs" style="color:var(--cyan);text-decoration:underline;cursor:pointer;">reset all filters</a></p>
                                    </div>
                                @endforelse
                            </div>

                            {{-- Active filter chips --}}
                            @if (count($activeFilters))
                                <div style="display:flex;flex-wrap:wrap;gap:8px;padding:10px 0;align-items:center;border-bottom:1px solid var(--line-soft);">
                                    <span style="font-size:12px;color:var(--ink-3);font-weight:500;">Filters:</span>
                                    @if (request('search'))
                                        <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;background:rgba(77,212,255,0.1);border:1px solid rgba(77,212,255,0.25);border-radius:999px;font-size:12px;color:var(--cyan);">
                                            <i class="fa-solid fa-magnifying-glass text-[9px]"></i>
                                            "{{ request('search') }}"
                                            <button onclick="removeFilter('search')" style="background:none;border:none;color:var(--cyan);cursor:pointer;padding:0;font-size:10px;"><i class="fa-solid fa-xmark"></i></button>
                                        </span>
                                    @endif
                                    @if (request('activity'))
                                        @php $actLabel = $activityOptions[request('activity')] ?? request('activity'); @endphp
                                        <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;background:rgba(77,212,255,0.1);border:1px solid rgba(77,212,255,0.25);border-radius:999px;font-size:12px;color:var(--cyan);">
                                            <i class="fa-solid fa-filter text-[9px]"></i>
                                            {{ $actLabel }}
                                            <button onclick="removeFilter('activity')" style="background:none;border:none;color:var(--cyan);cursor:pointer;padding:0;font-size:10px;"><i class="fa-solid fa-xmark"></i></button>
                                        </span>
                                    @endif
                                    @if (request('user_id'))
                                        @php $userName = $users->firstWhere('id', request('user_id'))?->name ?? request('user_id'); @endphp
                                        <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;background:rgba(77,212,255,0.1);border:1px solid rgba(77,212,255,0.25);border-radius:999px;font-size:12px;color:var(--cyan);">
                                            <i class="fa-solid fa-user text-[9px]"></i>
                                            {{ $userName }}
                                            <button onclick="removeFilter('user_id')" style="background:none;border:none;color:var(--cyan);cursor:pointer;padding:0;font-size:10px;"><i class="fa-solid fa-xmark"></i></button>
                                        </span>
                                    @endif
                                    @if (request('room'))
                                        <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;background:rgba(77,212,255,0.1);border:1px solid rgba(77,212,255,0.25);border-radius:999px;font-size:12px;color:var(--cyan);">
                                            <i class="fa-solid fa-server text-[9px]"></i>
                                            {{ request('room') }}
                                            <button onclick="removeFilter('room')" style="background:none;border:none;color:var(--cyan);cursor:pointer;padding:0;font-size:10px;"><i class="fa-solid fa-xmark"></i></button>
                                        </span>
                                    @endif
                                </div>
                            @endif

                            {{-- Desktop table --}}
                            <div class="hidden md:block" style="overflow-x:auto;">
                                <table class="tbl tbl-log">
                                    <thead>
                                        <tr>
                                            <th style="width:20%;" class="sortable" data-sort="user_name" onclick="handleSort('user_name')">USER</th>
                                            <th style="width:20%;" class="sortable" data-sort="room" onclick="handleSort('room')">ROOM</th>
                                            <th style="width:20%;">DETAIL</th>
                                            <th style="width:20%;" class="sortable" data-sort="activity" onclick="handleSort('activity')">ACTIVITY</th>
                                            <th style="width:20%;" class="whitespace-nowrap sortable" data-sort="created_at" onclick="handleSort('created_at')">TIME</th>
                                        </tr>
                                    </thead>
                                    <tbody id="logsTbody">
                                        @forelse ($logs as $log)
                                            <tr>
                                                <td>
                                                    <div class="log-user">
                                                        @if ($log->user?->avatar_url)
                                                            <img src="{{ $log->user->avatar_url }}" alt="{{ $log->user->name }}"
                                                                 class="avatar"
                                                                 style="width:34px;height:34px;border-radius:9px;flex-shrink:0;object-fit:cover;">
                                                        @else
                                                            <span class="avatar"
                                                                style="width:34px;height:34px;font-size:13px;border-radius:9px;flex-shrink:0;">
                                                                {{ strtoupper(substr($log->user->name ?? '?', 0, 1)) }}
                                                            </span>
                                                        @endif
                                                        <span class="name">{{ $log->user->name ?? '—' }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($isEmpty($log->room))
                                                        <span class="log-empty">—</span>
                                                    @else
                                                        <span class="log-room">{{ $log->room }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($isEmpty($log->ac))
                                                        <span class="log-empty">—</span>
                                                    @else
                                                        <span class="log-detail" title="{{ $log->ac }}">{{ $log->ac }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php [$label, $class] = activityBadge($log->activity); @endphp
                                                    <span class="act-badge {{ $class }}">{{ $label }}</span>
                                                </td>
                                                <td>
                                                    <div class="log-time">
                                                        <span class="t">{{ $log->created_at->format('H:i') }}</span>
                                                        <span class="d">{{ $log->created_at->format('d M Y') }}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5">
                                                    <div class="empty-state">
                                                        <div class="empty-icon"><i
                                                                class="fa-solid fa-magnifying-glass"></i></div>
                                                        <p class="empty-title">No activities found</p>
                                                        <p class="empty-sub">{{ count($activeFilters) ? 'Try adjusting your filters or ' : '' }}<a href="/logs" style="color:var(--cyan);text-decoration:underline;cursor:pointer;">reset all filters</a></p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="tbl-footer">
                                <p>
                                    Showing <span class="text-mono"
                                        style="color:var(--ink-1);">{{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }}</span>
                                    of <span class="text-mono"
                                        style="color:var(--ink-1);">{{ $logs->total() }}</span> activities
                                </p>
                                <div class="pager">
                                    @php
                                        $current = $logs->currentPage();
                                        $last = $logs->lastPage();
                                        $pages = [];
                                        if ($last <= 7) {
                                            $pages = range(1, $last);
                                        } else {
                                            $pages[] = 1;
                                            if ($current > 3) $pages[] = '...';
                                            for ($i = max(2, $current - 1); $i <= min($last - 1, $current + 1); $i++) $pages[] = $i;
                                            if ($current < $last - 2) $pages[] = '...';
                                            $pages[] = $last;
                                        }
                                    @endphp

                                    @if ($logs->onFirstPage())
                                        <span class="disabled"><i class="fa-solid fa-chevron-left text-[9px]"></i></span>
                                    @else
                                        <a href="{{ $logs->previousPageUrl() }}"><i class="fa-solid fa-chevron-left text-[9px]"></i></a>
                                    @endif

                                    @foreach ($pages as $p)
                                        @if ($p === '...')
                                            <span class="disabled">…</span>
                                        @elseif ($p == $current)
                                            <span class="active text-mono">{{ $p }}</span>
                                        @else
                                            <a class="text-mono" href="{{ $logs->url($p) }}">{{ $p }}</a>
                                        @endif
                                    @endforeach

                                    @if ($logs->hasMorePages())
                                        <a href="{{ $logs->nextPageUrl() }}"><i class="fa-solid fa-chevron-right text-[9px]"></i></a>
                                    @else
                                        <span class="disabled"><i class="fa-solid fa-chevron-right text-[9px]"></i></span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('components.bottom-nav')
    @include('components.sidebar-scripts')

    <script>
        // Quick category buttons -> set activity = auth/ac/room/user
        document.querySelectorAll('[data-quick]').forEach(btn => {
            btn.addEventListener('click', () => {
                const val = btn.getAttribute('data-quick');
                const url = new URL(window.location.href);
                url.searchParams.delete('page');
                if (val) {
                    url.searchParams.set('activity', val);
                } else {
                    url.searchParams.delete('activity');
                }
                window.location.href = url.toString();
            });
        });

        function removeFilter(key) {
            const url = new URL(window.location.href);
            url.searchParams.delete(key);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        function deleteAllLogs() {
            if (!confirm('Delete ALL logs? This action cannot be undone.')) return;

            fetch('/logs/delete-all', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(r => {
                    if (!r.ok) throw new Error('Delete failed');
                    return r.json();
                })
                .then(() => {
                    window.smToast ? window.smToast('All logs deleted successfully', 'success') : alert(
                        'All logs deleted successfully');
                    setTimeout(() => location.reload(), 800);
                })
                .catch(() => {
                    window.smToast ? window.smToast('Failed to delete logs', 'error') : alert('Failed to delete logs');
                });
        }

        function setSystemStatus(online) {
            const el = document.getElementById('systemStatus');
            if (!el) return;
            el.className = 'pill ' + (online ? 'pill-online' : 'pill-offline');
            el.innerHTML = `<span class="dot"></span><span>${online ? 'Online' : 'Offline'}</span>`;
        }
        window.addEventListener('online', () => setSystemStatus(true));
        window.addEventListener('offline', () => setSystemStatus(false));
        document.addEventListener('DOMContentLoaded', () => {
            setSystemStatus(navigator.onLine);
            initializeSortIndicators();

            // Real-time: prepend log baru ke tabel tanpa reload
            function escapeHtml(s) {
                return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
            }
            function activityBadgeJs(activity) {
                const a = String(activity || '');
                if (a.startsWith('set_temp_')) return [`TEMP ${a.replace('set_temp_', '')}°C`, 'act-amber'];
                if (a.startsWith('mode_'))     return [`MODE ${a.replace('mode_', '').toUpperCase()}`, 'act-cyan'];
                if (a.startsWith('fan_speed_'))return [`FAN ${a.replace('fan_speed_', '').toUpperCase()}`, 'act-cyan'];
                if (a.startsWith('swing_'))    return [`SWING ${a.replace('swing_', '').toUpperCase()}`, 'act-lavender'];
                const map = {
                    login: ['LOGIN', 'act-mint'], logout: ['LOGOUT', 'act-slate'],
                    on: ['POWER ON', 'act-mint'], off: ['POWER OFF', 'act-coral'],
                    bulk_on: ['ALL ON', 'act-mint'], bulk_off: ['ALL OFF', 'act-coral'],
                    set_timer: ['SET TIMER', 'act-amber'], timer_on: ['TIMER ON', 'act-mint'],
                    timer_off: ['TIMER OFF', 'act-amber'], control_ac: ['CONTROL AC', 'act-lavender'],
                    add_room: ['ADD ROOM', 'act-cyan'], delete_room: ['DELETE ROOM', 'act-coral'],
                    add_ac: ['ADD AC', 'act-cyan'], delete_ac: ['DELETE AC', 'act-coral'],
                    add_user: ['ADD USER', 'act-lavender'], delete_user: ['DELETE USER', 'act-coral'],
                    update_role: ['UPDATE ROLE', 'act-lavender'],
                    change_password: ['CHG PASSWORD', 'act-amber'],
                };
                return map[a] || [a.toUpperCase(), 'act-lavender'];
            }

            function prependLogRow(payload) {
                // Skip kalau user di halaman lain (paginasi)
                const url = new URL(window.location.href);
                const onFirstPage = !url.searchParams.get('page') || url.searchParams.get('page') === '1';
                if (!onFirstPage) return;

                const name = escapeHtml(payload.user_name || '—');
                const initial = escapeHtml(payload.user_initial || (payload.user_name || '?').charAt(0).toUpperCase());
                const isEmpty = (v) => v == null || v === '' || v === '-' || v === '—';
                const [badgeLabel, badgeClass] = activityBadgeJs(payload.activity);
                const safeAvatar = payload.user_avatar ? escapeHtml(payload.user_avatar) : null;

                const now = new Date();
                const hh = String(now.getHours()).padStart(2, '0');
                const mm = String(now.getMinutes()).padStart(2, '0');
                const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                const dateStr = `${String(now.getDate()).padStart(2,'0')} ${months[now.getMonth()]} ${now.getFullYear()}`;

                // === DESKTOP TABLE ===
                const tbody = document.getElementById('logsTbody');
                if (tbody) {
                    const empty = tbody.querySelector('.empty-state');
                    if (empty) empty.closest('tr')?.remove();

                    const roomHtml = isEmpty(payload.room)
                        ? '<span class="log-empty">—</span>'
                        : `<span class="log-room">${escapeHtml(payload.room)}</span>`;
                    const acHtml = isEmpty(payload.ac)
                        ? '<span class="log-empty">—</span>'
                        : `<span class="log-detail" title="${escapeHtml(payload.ac)}">${escapeHtml(payload.ac)}</span>`;
                    const avatarHtml = safeAvatar
                        ? `<img src="${safeAvatar}" alt="${name}" class="avatar" style="width:34px;height:34px;border-radius:9px;flex-shrink:0;object-fit:cover;">`
                        : `<span class="avatar" style="width:34px;height:34px;font-size:13px;border-radius:9px;flex-shrink:0;">${initial}</span>`;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>
                            <div class="log-user">
                                ${avatarHtml}
                                <span class="name">${name}</span>
                            </div>
                        </td>
                        <td>${roomHtml}</td>
                        <td>${acHtml}</td>
                        <td><span class="act-badge ${badgeClass}">${escapeHtml(badgeLabel)}</span></td>
                        <td>
                            <div class="log-time">
                                <span class="t">${hh}:${mm}</span>
                                <span class="d">${dateStr}</span>
                            </div>
                        </td>`;
                    tbody.insertBefore(tr, tbody.firstChild);

                    const maxRows = 50;
                    while (tbody.children.length > maxRows) tbody.removeChild(tbody.lastChild);
                }

                // === MOBILE CARDS ===
                const mobile = document.getElementById('logsMobile');
                if (mobile) {
                    const empty = mobile.querySelector('.empty-state');
                    if (empty) empty.remove();

                    const avatarColors = ['cyan', 'mint', 'lavender', 'coral'];
                    const uid = Number(payload.user_id) || 0;
                    const avatarColor = avatarColors[((uid - 1) % 4 + 4) % 4];

                    const mobileAvatar = safeAvatar
                        ? `<img src="${safeAvatar}" alt="${name}" style="width:38px;height:38px;border-radius:9px;object-fit:cover;flex-shrink:0;">`
                        : `<div style="width:38px;height:38px;border-radius:9px;background:var(--${avatarColor});color:#0c1726;font-size:15px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">${initial}</div>`;

                    const roomAcText = [
                        !isEmpty(payload.room) ? escapeHtml(payload.room) : '',
                        !isEmpty(payload.ac) ? escapeHtml(payload.ac) : '',
                    ].filter(Boolean).join(' · ') || '—';

                    const card = document.createElement('div');
                    card.style.cssText = 'padding:14px 16px;border-bottom:1px solid rgba(255, 255, 255, 0.15);';
                    card.innerHTML = `
                        <div class="flex items-center gap-3">
                            ${mobileAvatar}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="truncate" style="font-size:15px;font-weight:600;color:var(--ink-0);">${name}</span>
                                        <span class="act-badge ${badgeClass}" style="flex-shrink:0;">${escapeHtml(badgeLabel)}</span>
                                    </div>
                                    <span class="text-mono" style="font-size:12.5px;color:var(--ink-2);white-space:nowrap;flex-shrink:0;font-weight:600;">${hh}:${mm}</span>
                                </div>
                                <div class="flex items-center justify-between gap-2" style="margin-top:5px;">
                                    <span class="truncate" style="font-size:13px;color:var(--ink-3);">${roomAcText}</span>
                                    <span class="text-mono" style="font-size:11.5px;color:var(--ink-4);white-space:nowrap;flex-shrink:0;">${dateStr}</span>
                                </div>
                            </div>
                        </div>`;
                    mobile.insertBefore(card, mobile.firstChild);

                    const maxCards = 50;
                    while (mobile.children.length > maxCards) mobile.removeChild(mobile.lastChild);
                }
            }

            function clearAllLogs() {
                const tbody = document.getElementById('logsTbody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                                    <p class="empty-title">No activities found</p>
                                    <p class="empty-sub"><a href="/logs" style="color:var(--cyan);text-decoration:underline;cursor:pointer;">reset all filters</a></p>
                                </div>
                            </td>
                        </tr>`;
                }
                const mobile = document.getElementById('logsMobile');
                if (mobile) {
                    mobile.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                            <p class="empty-title">No activities found</p>
                            <p class="empty-sub"><a href="/logs" style="color:var(--cyan);text-decoration:underline;cursor:pointer;">reset all filters</a></p>
                        </div>`;
                }
            }

            if (window.Echo) {
                window.Echo.channel('device-status')
                    .listen('.UserLogCreated', (e) => prependLogRow(e))
                    .listen('.UserLogsCleared', () => clearAllLogs());
            }
        });

        // Column sorting
        function handleSort(column) {
            const url = new URL(window.location.href);
            const currentSort = url.searchParams.get('sort');
            const currentOrder = url.searchParams.get('order') || 'asc';

            if (currentSort === column) {
                url.searchParams.set('order', currentOrder === 'asc' ? 'desc' : 'asc');
            } else {
                url.searchParams.set('sort', column);
                url.searchParams.set('order', 'asc');
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        function initializeSortIndicators() {
            const params = new URLSearchParams(window.location.search);
            const sortColumn = params.get('sort');
            const sortOrder = params.get('order') || 'asc';

            if (sortColumn) {
                const th = document.querySelector(`th[data-sort="${sortColumn}"]`);
                if (th) {
                    th.classList.remove('sortable');
                    th.classList.add(sortOrder === 'asc' ? 'sort-asc' : 'sort-desc');
                }
            }
        }
    </script>
</body>

</html>


