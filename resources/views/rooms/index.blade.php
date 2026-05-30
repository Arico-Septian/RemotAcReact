<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Room Management — SmartAC</title>
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite('resources/js/app.js')
    @include('components.sidebar-styles')
    <style>
        /* ===== Temperature chip — colored bg + matching text ===== */
        .room-card .temp-chip {
            display: flex !important;
            border-radius: var(--r-md) !important;
            padding: 7px 12px !important;
            font-size: 11px !important;
        }
        .room-card .temp-chip.cool {
            background: rgb(var(--cyan-rgb) / 0.15) !important;
            color: var(--cyan) !important;
            border: 1px solid rgb(var(--cyan-rgb) / 0.35) !important;
        }
        .room-card .temp-chip.warm {
            background: rgb(var(--mint-rgb) / 0.15) !important;
            color: var(--mint) !important;
            border: 1px solid rgb(var(--mint-rgb) / 0.35) !important;
        }
        .room-card .temp-chip.hot {
            background: rgba(248, 113, 113, 0.15) !important;
            color: var(--red) !important;
            border: 1px solid rgba(248, 113, 113, 0.4) !important;
        }
        .room-card .temp-chip.idle {
            background: var(--panel-2) !important;
            color: var(--ink-3) !important;
            border: 1px solid var(--line-soft) !important;
        }

        /* Keputusan text color follows action */
        .keputusan-yellow { color: var(--yellow) !important; } /* TURUNKAN */
        .keputusan-cool   { color: var(--cyan) !important; }
        .keputusan-warm   { color: var(--mint) !important; } /* DIAM (stabil) */
        .keputusan-hot    { color: var(--orange) !important; }
        .keputusan-idle   { color: var(--ink-3) !important; }

        .room-card {
            position: relative;
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
            border-radius: var(--r-xl);
            box-shadow: var(--inset-hi);
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            transition: var(--t-base);
            overflow: hidden;
        }

        .room-card::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            height: 2px;
            background: var(--card-accent, var(--ink-3));
            opacity: 0.7;
        }

        .room-card[data-status="online"] {
            --card-accent: var(--mint);
        }

        .room-card[data-status="offline"] {
            --card-accent: var(--coral);
        }

        .room-card:hover {
            background: var(--panel-2);
            border-color: var(--line);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .floor-section {
            margin-bottom: 4px;
        }

        .floor-section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .floor-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: var(--ink-3);
            white-space: nowrap;
        }

        .floor-divider {
            flex: 1;
            height: 1px;
            background: var(--line-soft);
        }

        .floor-count {
            font-size: 10px;
            color: var(--ink-4);
            white-space: nowrap;
        }

        /* Responsive search and filters */
        @media (max-width: 768px) {
            .flex.items-center.gap-2 {
                gap: 6px;
            }

            .flex.items-center.gap-2 > form {
                flex: 1;
                min-width: 0;
                transition: flex var(--t-base);
            }

            .flex.items-center.gap-2 > div {
                display: inline-flex;
                gap: 1px;
                flex-wrap: nowrap;
                flex-shrink: 0;
            }

            .segmented {
                display: inline-flex;
                gap: 1px;
            }

            .segmented .seg {
                font-size: 11px;
                padding: 5px 8px;
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

        /* Very small screens (< 480px) */
        @media (max-width: 480px) {
            .flex.items-center.gap-2 {
                gap: 6px;
            }

            .flex.items-center.gap-2 > form {
                flex: 1;
                min-width: 0;
            }

            .flex.items-center.gap-2 > form:focus-within {
                flex: 1;
            }

            .flex.items-center.gap-2 > div {
                display: inline-flex;
                gap: 4px;
                flex-wrap: nowrap;
                flex-shrink: 0;
            }

            .segmented {
                display: inline-flex;
                gap: 2px;
            }

            .segmented .seg {
                font-size: 10px;
                padding: 5px 6px;
                min-width: auto;
            }

            .search-input input {
                font-size: 12px;
                padding: 6px 8px 6px 28px;
            }

            .search-input input::placeholder {
                color: var(--ink-3);
                transition: color var(--t-base);
            }

            .search-input input:focus::placeholder {
                color: transparent;
            }

            .search-input i {
                font-size: 12px;
                transition: opacity var(--t-base);
            }

            .search-input:focus-within i {
                opacity: 0;
                pointer-events: none;
            }

            .flex.items-center.gap-2 > button.btn-primary {
                padding: 6px 12px;
                font-size: 11px;
                white-space: nowrap;
            }

            .flex.items-center.gap-2 > button.btn-primary span {
                display: inline;
            }

            .flex.items-center.gap-2 > button.btn-primary i {
                margin-right: 4px;
                font-size: 11px;
            }
        }

        /* Tablet & desktop (≥ 481 px): unify search / segmented / Add Room height to 40 px */
        @media (min-width: 481px) {
            .app-content-inner > .flex.items-center.gap-2:first-child .search-input {
                height: 40px;
            }
            .app-content-inner > .flex.items-center.gap-2:first-child .search-input input {
                height: 40px;
                box-sizing: border-box;
            }
            .app-content-inner > .flex.items-center.gap-2:first-child .segmented {
                height: 40px;
                box-sizing: border-box;
                display: inline-flex;
                align-items: center;
            }
            .app-content-inner > .flex.items-center.gap-2:first-child .segmented .seg {
                height: 100%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-sizing: border-box;
            }
            .app-content-inner > .flex.items-center.gap-2:first-child > .flex > button.btn-primary {
                height: 40px;
                box-sizing: border-box;
            }
        }

        /* Tablet (481–768 px): slightly more breathing room between segmented and Add Room */
        @media (min-width: 481px) and (max-width: 768px) {
            .app-content-inner > .flex.items-center.gap-2:first-child > .flex.gap-2 {
                gap: 10px !important;
            }
        }

        /* ===== Room grid: 2 cols (mobile) / 3 cols (tablet) / 5 cols (laptop) ===== */
        .floor-section > .grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 12px !important;
            justify-content: stretch;
        }

        /* Tablet (≥ 768px): 3 cols */
        @media (min-width: 768px) {
            .floor-section > .grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }
        }

        /* Laptop (≥ 1024px): 5 cols */
        @media (min-width: 1024px) {
            .floor-section > .grid {
                grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            }
        }

        /* Toolbar wraps to 2 rows on tiny phones so search field gets its full width */
        @media (max-width: 480px) {
            .app-content-inner > .flex.items-center.gap-2:first-child {
                flex-wrap: wrap;
                row-gap: 8px;
            }
            .app-content-inner > .flex.items-center.gap-2:first-child > form { flex: 1 1 100%; }
            .app-content-inner > .flex.items-center.gap-2:first-child > .flex {
                flex: 1 1 100%;
                justify-content: flex-start;
                gap: 6px;
            }

            /* Unify height across search / segmented / Add Room — 36 px */
            .app-content-inner > .flex.items-center.gap-2:first-child .search-input {
                height: 36px;
            }
            .app-content-inner > .flex.items-center.gap-2:first-child .search-input input {
                height: 36px;
                padding: 0 12px 0 34px;
                font-size: 12px;
            }
            .app-content-inner > .flex.items-center.gap-2:first-child .search-input i {
                left: 12px;
                top: 50%;
                transform: translateY(-50%);
            }
            .app-content-inner > .flex.items-center.gap-2:first-child > .flex > .segmented {
                flex: 1 1 auto;
                display: flex;
                height: 36px;
                padding: 2px;
                box-sizing: border-box;
            }
            .app-content-inner > .flex.items-center.gap-2:first-child > .flex > .segmented .seg {
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
            .app-content-inner > .flex.items-center.gap-2:first-child > .flex > button.btn-primary {
                flex-shrink: 0;
                height: 36px;
                min-height: 36px;
                padding: 0 12px;
                font-size: 11px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
        }

        /* Mobile (≤ 480px): keep "Add Room" label visible, match 36 px unified height */
        @media (max-width: 480px) {
            .app-content-inner > .flex.items-center.gap-2:first-child > .flex > button.btn-primary span { display: inline !important; font-size: 10px; }
            .app-content-inner > .flex.items-center.gap-2:first-child > .flex > button.btn-primary i { margin-right: 4px; font-size: 10px; }
            .app-content-inner > .flex.items-center.gap-2:first-child > .flex > button.btn-primary { padding: 0 10px; font-size: 10px; white-space: nowrap; }
            .app-content-inner > .flex.items-center.gap-2:first-child > .flex > .segmented .seg { font-size: 10px; padding: 0 4px; }
        }

        /* Header title wraps less awkwardly on small screens */
        @media (max-width: 480px) {
            .main-header .app-header-title h1 { font-size: 16px; line-height: 1.2; }
            .main-header .app-header-title p { font-size: 11px; }
        }
        @media (max-width: 480px) {
            .main-header { gap: 6px; padding-left: 10px; padding-right: 10px; }
            .main-header .app-header-title h1 { font-size: 13px; line-height: 1.2; }
            .main-header .app-header-title p { font-size: 10px; line-height: 1.2; }
            .main-header > .flex.items-center.gap-3 { gap: 6px; min-width: 0; flex: 1; }
            .main-header > .flex.items-center.gap-2 { gap: 4px; flex-shrink: 0; }
            /* Shrink the Online pill to just the dot on tiny screens */
            .main-header > .flex.items-center.gap-2 .btn-icon { width: 28px; height: 28px; }
        }

        /* Phones (≤ 480px): tighter card spacing — grid tetap 2 kolom */
        @media (max-width: 480px) {
            .floor-section > .grid {
                gap: 10px !important;
            }

            .room-card { padding: 11px; gap: 8px; }
            .room-card h2 { font-size: 13px; }
            .room-card .room-status-pill { padding: 2px 7px !important; font-size: 10px !important; }
            .room-card .temp-chip { padding: 6px 10px !important; font-size: 10px !important; }
            .room-card .label-tag { font-size: 8px !important; margin-top: 2px !important; }
        }

        /* Tiny phones (≤ 480px): grid tetap 2 kolom, lebih rapat */
        @media (max-width: 480px) {
            .floor-section > .grid {
                gap: 8px !important;
            }
            .room-card { padding: 9px; gap: 6px; }
        }

        /* Compact Add Room modal on tablets */
        @media (min-width: 481px) and (max-width: 1024px) {
            #modal.modal-backdrop {
                padding: 12px;
            }

            #modal .modal {
                max-width: 390px;
                border-radius: var(--r-xl);
            }

            #modal .modal-header {
                padding: 14px 18px 6px;
                gap: 7px;
            }

            #modal .eyebrow {
                font-size: 10px;
                margin-bottom: 4px;
            }

            #modal .modal-header h2 {
                font-size: 14px;
                line-height: 1.2;
            }

            #modal .modal-header .sub {
                font-size: 11px;
                line-height: 1.3;
                margin-top: 5px;
            }

            #modal .modal-close {
                width: 26px;
                height: 26px;
                font-size: 13px;
            }

            #modal .modal-body {
                padding: 5px 18px 7px;
            }

            #modal .modal-body.space-y-3 > :not([hidden]) ~ :not([hidden]) {
                margin-top: 7px !important;
            }

            #modal .field {
                gap: 4px;
            }

            #modal .field-label {
                font-size: 10px;
                letter-spacing: 0.05em;
            }

            #modal .input {
                min-height: 36px;
                padding: 0 12px;
                border-radius: var(--r-md);
                font-size: 12px;
            }

            #modal .field-help {
                font-size: 10px;
                line-height: 1.3;
            }

            #modal .modal-footer {
                padding: 7px 18px 14px;
                gap: 8px;
            }

            #modal .modal-footer .btn {
                min-height: 34px;
                padding: 0 14px;
                font-size: 12px;
            }
        }

        /* Compact Add Room modal on mobile */
        @media (max-width: 480px) {
            #modal.modal-backdrop {
                padding: 10px;
            }

            #modal .modal {
                max-width: min(330px, calc(100vw - 44px));
                border-radius: var(--r-xl);
            }

            #modal .modal-header {
                padding: 12px 14px 4px;
                gap: 6px;
            }

            #modal .eyebrow {
                font-size: 10px;
                margin-bottom: 3px;
            }

            #modal .modal-header h2 {
                font-size: 14px;
                line-height: 1.1;
            }

            #modal .modal-header .sub {
                font-size: 10px;
                line-height: 1.3;
                margin-top: 4px;
            }

            #modal .modal-close {
                width: 24px;
                height: 24px;
                font-size: 12px;
            }

            #modal .modal-body {
                padding: 4px 14px 6px;
            }

            #modal .modal-body.space-y-3 > :not([hidden]) ~ :not([hidden]) {
                margin-top: 6px !important;
            }

            #modal .field {
                gap: 3px;
            }

            #modal .field-label {
                font-size: 10px;
                letter-spacing: 0.05em;
            }

            #modal .input {
                min-height: 34px;
                padding: 0 10px;
                border-radius: var(--r-md);
                font-size: 11px;
            }

            #modal .field-help {
                display: none;
            }

            #modal .modal-footer {
                padding: 6px 14px 12px;
                gap: 7px;
            }

            #modal .modal-footer .btn {
                min-height: 34px;
                padding: 0 12px;
                font-size: 11px;
            }
        }

        @media (max-width: 480px) {
            #modal .modal {
                max-width: min(310px, calc(100vw - 44px));
            }
        }

        @media (max-width: 480px) {
            #modal.modal-backdrop {
                padding: 8px;
            }

            #modal .modal {
                max-width: min(286px, calc(100vw - 34px));
                border-radius: var(--r-xl);
            }

            #modal .modal-header {
                padding: 10px 12px 3px;
            }

            #modal .modal-body {
                padding: 3px 12px 5px;
            }

            #modal .input {
                min-height: 32px;
                font-size: 11px;
            }

            #modal .modal-footer {
                padding: 5px 12px 10px;
            }

            #modal .modal-footer .btn {
                min-height: 32px;
                padding: 0 10px;
                font-size: 11px;
            }
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
                        <h1>Rooms &amp; AC Units</h1>
                        <p>Manage server rooms</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @include('components.notification-bell')

                </div>
            </header>

            <div class="page-body">
                <div class="app-content">
                    <div class="app-content-inner space-y-4">

                        <div class="flex items-center gap-2">
                            <form method="GET" action="{{ route('rooms.index') }}" class="flex-1 min-w-0">
                                <label class="search-input">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input name="search" value="{{ request('search') }}" type="text"
                                        placeholder="Search rooms…" autocomplete="off">
                                    @if (request('search'))
                                        <a href="{{ route('rooms.index') }}" class="clear" title="Clear"><i
                                                class="fa-solid fa-xmark text-[10px]"></i></a>
                                    @endif
                                </label>
                            </form>

                            <div class="flex gap-2 flex-shrink-0 items-center">
                                <div class="segmented">
                                    <button class="seg active" data-room-filter="all" type="button">All</button>
                                    <button class="seg" data-room-filter="online" type="button">
                                        Online
                                    </button>
                                    <button class="seg" data-room-filter="offline" type="button">
                                        Offline
                                    </button>
                                </div>
                                @auth
                                    @if (in_array(Auth::user()->role, ['admin', 'operator']))
                                        <button onclick="openModal()" class="btn btn-primary btn-sm" type="button">
                                            <i class="fa-solid fa-plus text-[10px]"></i>
                                            <span>Add Room</span>
                                        </button>
                                    @endif
                                @endauth
                            </div>
                        </div>

                        <p id="roomCount" class="text-mono text-xs" style="color:var(--ink-3);"></p>

                        @if ($rooms->count() > 0)
                            <div class="space-y-2">
                                @foreach ($roomsByFloor as $floorName => $floorRooms)
                                    <section class="floor-section">
                                        <div class="floor-section-header">
                                            <i class="fa-solid fa-layer-group text-[10px]"
                                                style="color:var(--lavender);"></i>
                                            <span class="floor-label">{{ ucfirst($floorName) }}</span>
                                            <div class="floor-divider"></div>
                                            <span class="floor-count">{{ $floorRooms->count() }} rooms</span>
                                        </div>

                                        <div
                                            class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 mb-6">
                                            @foreach ($floorRooms as $room)
                                                @php
                                                    $online = ($room->device_status ?? 'offline') === 'online';
                                                    $temp = $room->temperature ?? $room->last_temperature ?? null;
                                                    $tcls =
                                                        $temp === null
                                                            ? 'idle'
                                                            : ($temp > 30
                                                                ? 'hot'
                                                                : ($temp > 25
                                                                    ? 'warm'
                                                                    : 'cool'));
                                                    $activeAcs = $room->acUnits
                                                        ->filter(fn($ac) => $ac->status && $ac->status->power == 'ON')
                                                        ->count();
                                                    $idleAcs = $room->acUnits
                                                        ->filter(fn($ac) => !$ac->status || $ac->status->power !== 'ON')
                                                        ->count();
                                                @endphp
                                                <div class="room-card" data-room-id="{{ $room->id }}"
                                                    data-room-name="{{ $room->name }}"
                                                    data-device-id="{{ $room->device_id }}"
                                                    data-status="{{ $online ? 'online' : 'offline' }}">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <h2 class="font-semibold text-tight truncate"
                                                            style="color:var(--ink-0);font-size:16px;line-height: 1.3;">{{ ucfirst($room->name) }}</h2>
                                                        <span class="pill room-status-pill {{ $online ? 'pill-online' : 'pill-offline' }}"
                                                            style="padding:3px 8px;font-size:10px;flex-shrink:0;">
                                                            <span class="dot"></span><span class="room-status-text">{{ $online ? 'Online' : 'Offline' }}</span>
                                                        </span>
                                                    </div>

                                                    {{-- BAR SUHU --}}
                                                    <div class="temp-chip {{ $room->temperature_is_offline ? 'idle' : $tcls }}"
                                                        style="justify-content:space-between;width:100%;">
                                                        <span style="display:inline-flex;align-items:center;gap:6px;font-weight:500;">
                                                            <i class="fa-solid fa-temperature-half text-[10px]"></i>Temp
                                                        </span>
                                                        <span style="display:inline-flex;align-items:center;gap:5px;">
                                                            @if($room->temperature_is_offline)
                                                                <i class="fa-solid fa-wifi-slash temp-offline-icon" style="font-size:11px;color:var(--coral);"></i>
                                                            @endif
                                                            <span id="temp-{{ $room->id }}" class="text-mono" data-offline="{{ $room->temperature_is_offline ? 'true' : 'false' }}">
                                                                {{ $temp ?? '–' }}°C
                                                            </span>
                                                        </span>
                                                    </div>

                                                    {{-- FUZZY OUTPUT (taruh DI BAWAH BAR SUHU) --}}
                                                    @if ($room->temperature !== null)
                                                        <div class="mt-2"
                                                            style="background:var(--panel-1);border:1px solid var(--line-soft);border-radius:var(--r-md);padding:8px 10px;">
                                                            <div class="flex items-center justify-between"
                                                                style="font-size:12px;color:var(--ink-3);">
                                                                <span>ΔT</span>
                                                                <span
                                                                    class="text-mono">{{ $room->delta_t ?? 0 }}</span>
                                                            </div>

                                                            @if (!empty($room->fuzzy))
                                                                <div class="flex items-center justify-between mt-1"
                                                                    style="font-size:11px;">
                                                                    <span style="color:var(--ink-3);flex-shrink:0;">Cooling</span>
                                                                    <span class="text-mono"
                                                                        style="font-weight:700;color:var(--mint);white-space:nowrap;margin-left:6px;">
                                                                        {{ $room->fuzzy['status_pendinginan'] ?? '-' }}
                                                                    </span>
                                                                </div>

                                                                {{-- KEPUTUSAN FUZZY --}}
                                                                @if (!empty($room->decision))
                                                                    @php
                                                                        $action = strtoupper($room->decision['action'] ?? 'DIAM');
                                                                        $keputusanClass = match($action) {
                                                                            'TURUNKAN' => 'keputusan-yellow',
                                                                            'NAIKKAN'  => 'keputusan-cool',
                                                                            'DIAM'     => 'keputusan-warm',
                                                                            default    => 'keputusan-idle',
                                                                        };
                                                                        $spBefore = is_array($room->decision) ? ($room->decision['setpoint_before'] ?? '-') : '-';
                                                                        $spAfter  = is_array($room->decision) ? ($room->decision['setpoint_after']  ?? '-') : '-';
                                                                    @endphp
                                                                    <div style="font-size:11px;color:var(--ink-3);margin-top:4px;">
                                                                        <div class="flex items-center justify-between">
                                                                            <span style="flex-shrink:0;">Decision</span>
                                                                            <span class="text-mono {{ $keputusanClass }}"
                                                                                style="font-weight:700;white-space:nowrap;margin-left:6px;">{{ $action }}</span>
                                                                        </div>
                                                                        <div class="flex items-center justify-between" style="margin-top:2px;color:var(--ink-4);">
                                                                            <span style="flex-shrink:0;">Setpoint</span>
                                                                            <span class="text-mono" style="white-space:nowrap;margin-left:6px;">{{ $spBefore }} &rarr; {{ $spAfter }}</span>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    @endif
                                                    <div class="grid grid-cols-2 gap-1.5">
                                                        <div
                                                            style="background:var(--panel-1);border:1px solid var(--line-soft);border-radius:var(--r-md);padding:6px 8px;text-align:center;">
                                                            <p class="text-mono text-base font-bold"
                                                                style="color:var(--mint);line-height:1;"
                                                                id="active-{{ $room->id }}">
                                                                {{ $activeAcs }}</p>
                                                            <p class="label-tag mt-1" style="font-size: 10px;">Active
                                                            </p>
                                                        </div>
                                                        <div
                                                            style="background:var(--panel-1);border:1px solid var(--line-soft);border-radius:var(--r-md);padding:6px 8px;text-align:center;">
                                                            <p class="text-mono text-base font-bold"
                                                                style="color:var(--ink-2);line-height:1;"
                                                                id="idle-{{ $room->id }}">
                                                                {{ $idleAcs }}</p>
                                                            <p class="label-tag mt-1" style="font-size: 10px;">Idle
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <p class="text-xs text-center"
                                                        style="color:var(--ink-4);margin-top:-2px;">
                                                        {{ $room->acUnits->count() }} unit total</p>

                                                    <div class="flex flex-col gap-1.5 mt-auto">
                                                        <a href="/rooms/{{ $room->id }}/ac"
                                                            class="btn btn-primary btn-sm">
                                                            <i class="fa-solid fa-sliders text-[10px]" aria-hidden="true"></i>Control AC
                                                        </a>
                                                        @auth
                                                            @if (in_array(Auth::user()->role, ['admin', 'operator']))
                                                                <form action="/rooms/{{ $room->id }}" method="POST"
                                                                    onsubmit="return confirmDelete(event)">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit"
                                                                        class="btn btn-danger btn-sm btn-block"
                                                                        aria-label="Delete room {{ $room->name }}">
                                                                        <i class="fa-solid fa-trash text-[10px]"></i>Delete
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        @endauth
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </section>
                                @endforeach
                            </div>
                            <div id="roomFilterEmpty" class="empty-state" hidden>
                                <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                                <p class="empty-title">Not found</p>
                                <p class="empty-sub">Try a different status filter</p>
                            </div>
                        @else
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-server"></i></div>
                                <p class="empty-title">No rooms</p>
                                <p class="empty-sub">Add a room to get started</p>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('components.bottom-nav')

    {{-- Modal: Add Room --}}
    @auth
        @if (in_array(Auth::user()->role, ['admin', 'operator']))
            <div id="modal" class="modal-backdrop">
                <div class="modal">
                    <div class="modal-header">
                        <div>
                            <p class="eyebrow"><i class="fa-solid fa-plus"></i> New</p>
                            <h2>Add Room</h2>
                            <p class="sub">Register a new room with its ESP device</p>
                        </div>
                    </div>
                    <form id="addRoomForm" method="POST" action="/rooms">
                        @csrf
                        <div class="modal-body space-y-3">
                            <div class="field">
                                <label class="field-label">Room Name</label>
                                <input class="input text-mono" type="text" name="name" placeholder="server_1"
                                    pattern="[A-Za-z0-9_]+"
                                    title="Room name must not contain spaces"
                                    required>
                                <p class="field-help">Letters, numbers, and underscores (no spaces)</p>
                            </div>
                            <div class="field">
                                <label class="field-label">ESP Device ID</label>
                                <input class="input text-mono" type="text" name="device_id" placeholder="esp32_01"
                                    pattern="[A-Za-z0-9_-]+"
                                    title="ESP Device ID must not contain spaces"
                                    required>
                                <p class="field-help">Letters, numbers, underscores, and dashes (no spaces)</p>
                            </div>
                            <div class="field">
                                <label class="field-label">Floor / Zone <span
                                        style="color:var(--ink-4);font-weight:400;">(optional)</span></label>
                                <input class="input text-mono" type="text" name="floor" placeholder="floor_1"
                                    pattern="[A-Za-z0-9_]*"
                                    title="Floor or zone must not contain spaces">
                                <p class="field-help">Letters, numbers, and underscores (no spaces)</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Room</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endauth

    <script>
        function openModal() {
            document.getElementById('modal')?.classList.add('is-open');
        }

        function closeModal() {
            document.getElementById('modal')?.classList.remove('is-open');
            document.querySelector('#modal form')?.reset();
        }
        document.getElementById('modal')?.addEventListener('click', e => {
            if (e.target === document.getElementById('modal')) closeModal();
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeModal();
        });

        function confirmDelete(e) {
            e.preventDefault();
            if (confirm('Delete this room and all its AC units?')) e.target.submit();
            return false;
        }

        const roomCards = Array.from(document.querySelectorAll('.room-card'));
        const floorSections = Array.from(document.querySelectorAll('.floor-section'));
        const roomFilterEmpty = document.getElementById('roomFilterEmpty');
        const roomCount = document.getElementById('roomCount');
        let activeRoomFilter = 'all';
        const normalizeFormValue = value => (value || '').trim().toLowerCase();

        function blockDuplicateInput(input, message) {
            input.setCustomValidity(message);
            setFieldFeedback(input, message, true);
            input.reportValidity();
            window.smToast?.(message, 'error');
            input.focus();
        }

        function clearInputValidity(input) {
            input.setCustomValidity('');
            setFieldFeedback(input);
        }

        function setFieldFeedback(input, message = null, isError = false) {
            const help = input.closest('.field')?.querySelector('.field-help, .field-hint');
            if (!help) return;

            help.dataset.defaultText ??= help.textContent;
            help.textContent = message || help.dataset.defaultText;
            help.style.color = isError ? 'var(--coral)' : '';
        }

        function validateNoSpaces(input, label) {
            if (/\s/.test(input.value)) {
                input.setCustomValidity(`${label} must not contain spaces`);
                setFieldFeedback(input, `${label} must not contain spaces`, true);
                return false;
            }

            clearInputValidity(input);
            return true;
        }

        document.querySelectorAll('#addRoomForm input').forEach(input => {
            input.addEventListener('input', () => validateNoSpaces(input, input.closest('.field')?.querySelector('.field-label')?.textContent?.trim() || 'Input'));
        });

        document.getElementById('addRoomForm')?.addEventListener('submit', e => {
            const form = e.currentTarget;
            const nameInput = form.querySelector('[name="name"]');
            const deviceInput = form.querySelector('[name="device_id"]');
            const floorInput = form.querySelector('[name="floor"]');

            nameInput.value = normalizeFormValue(nameInput.value);
            deviceInput.value = normalizeFormValue(deviceInput.value);
            floorInput.value = normalizeFormValue(floorInput.value);

            if (!validateNoSpaces(nameInput, 'Room name')) {
                e.preventDefault();
                nameInput.reportValidity();
                return;
            }

            if (!validateNoSpaces(deviceInput, 'ESP Device ID')) {
                e.preventDefault();
                deviceInput.reportValidity();
                return;
            }

            if (!validateNoSpaces(floorInput, 'Lantai atau zona')) {
                e.preventDefault();
                floorInput.reportValidity();
                return;
            }

            const roomNames = new Set(roomCards.map(card => normalizeFormValue(card.dataset.roomName)));
            const deviceIds = new Set(roomCards.map(card => normalizeFormValue(card.dataset.deviceId)).filter(Boolean));

            if (roomNames.has(nameInput.value)) {
                e.preventDefault();
                blockDuplicateInput(nameInput, 'Room name already exists');
                return;
            }

            if (deviceIds.has(deviceInput.value)) {
                e.preventDefault();
                blockDuplicateInput(deviceInput, 'ESP Device ID already registered');
            }
        });

        function applyRoomFilter() {
            let visible = 0;

            roomCards.forEach(card => {
                const show = activeRoomFilter === 'all' || card.dataset.status === activeRoomFilter;
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            floorSections.forEach(section => {
                const hasVisible = Array.from(section.querySelectorAll('.room-card'))
                    .some(card => card.style.display !== 'none');

                section.style.display = hasVisible ? '' : 'none';
            });

            if (roomCount) {
                roomCount.textContent =
                    visible === roomCards.length ?
                    `Showing ${roomCards.length} rooms` :
                    `${visible} of ${roomCards.length} rooms`;
            }

            if (roomFilterEmpty) {
                roomFilterEmpty.hidden = visible > 0;
            }
        }

        document.querySelectorAll('[data-room-filter]').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('[data-room-filter]').forEach(item => item.classList.remove(
                    'active'));
                this.classList.add('active');
                activeRoomFilter = this.dataset.roomFilter;
                applyRoomFilter();
            });
        });

        function classForTemperature(temp) {
            if (temp === null || Number.isNaN(temp)) return 'idle';
            if (temp > 30) return 'hot';
            if (temp > 25) return 'warm';
            return 'cool';
        }

        function updateRoomTemperature(room) {
            const el = document.getElementById(`temp-${room.id}`);
            if (!el) return;

            const liveTemp = parseFloat(room.temp);
            const lastTemp = parseFloat(room.last_temp ?? room.temperature);
            const displayTemp = Number.isNaN(liveTemp) ? lastTemp : liveTemp;
            const isOffline = room.is_offline === true;
            const chip = el.closest('.temp-chip');

            if (!Number.isNaN(displayTemp)) {
                el.textContent = `${displayTemp}°C`;
            }

            el.dataset.offline = isOffline ? 'true' : 'false';

            if (chip) {
                chip.classList.remove('cool', 'warm', 'hot', 'idle');
                chip.classList.add(isOffline ? 'idle' : classForTemperature(displayTemp));

                let icon = chip.querySelector('.temp-offline-icon');
                if (isOffline && !icon) {
                    icon = document.createElement('i');
                    icon.className = 'fa-solid fa-wifi-slash temp-offline-icon';
                    icon.style.fontSize = '11px';
                    icon.style.color = 'var(--coral)';
                    el.before(icon);
                } else if (!isOffline && icon) {
                    icon.remove();
                }
            }
        }

        let _tempFetchFailed = false;
        let _statusFetchFailed = false;

        function refreshTemps() {
            fetch('/temperature', {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    _tempFetchFailed = false;
                    if (!Array.isArray(data)) return;
                    data.forEach(updateRoomTemperature);
                }).catch(() => {
                    if (!_tempFetchFailed) {
                        _tempFetchFailed = true;
                        window.smToast?.('Failed to load room temperature data', 'error');
                    }
                });
        }

        setInterval(refreshTemps, 5000);

        function setRoomStatus(card, online) {
            card.dataset.status = online ? 'online' : 'offline';

            const pill = card.querySelector('.room-status-pill');
            const text = card.querySelector('.room-status-text');
            if (!pill || !text) return;

            pill.classList.toggle('pill-online', online);
            pill.classList.toggle('pill-offline', !online);
            text.textContent = online ? 'Online' : 'Offline';
        }

        function refreshRoomStatuses() {
            fetch('/device-status', {
                    headers: {
                        'Accept': 'application/json'
                    },
                    cache: 'no-store'
                })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    _statusFetchFailed = false;
                    if (!Array.isArray(data)) return;

                    data.forEach(device => {
                        const card = document.querySelector(`.room-card[data-room-id="${device.room_id}"]`);
                        if (!card) return;

                        setRoomStatus(card, device.is_online === true || device.status === 'online');
                    });

                    applyRoomFilter();
                })
                .catch(() => {
                    if (!_statusFetchFailed) {
                        _statusFetchFailed = true;
                        window.smToast?.('Failed to load device status', 'error');
                    }
                });
        }

        setInterval(refreshRoomStatuses, 5000);

        document.addEventListener('DOMContentLoaded', () => {
            applyRoomFilter();
            refreshRoomStatuses();

            // Real-time via Reverb: refresh segera saat device/suhu/AC berubah (tanpa reload)
            function refreshAcCounters() {
                fetch('/api/ac-status', { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
                    .then(r => r.ok ? r.json() : null)
                    .then(data => {
                        if (!Array.isArray(data)) return;
                        // Group by room_id, count power=ON vs OFF
                        const counts = {};
                        data.forEach(item => {
                            const roomId = item.ac_unit?.room?.id ?? item.acUnit?.room?.id;
                            if (!roomId) return;
                            if (!counts[roomId]) counts[roomId] = { active: 0, idle: 0 };
                            if ((item.power || 'OFF').toUpperCase() === 'ON') counts[roomId].active++;
                            else counts[roomId].idle++;
                        });
                        // Update DOM
                        Object.entries(counts).forEach(([roomId, c]) => {
                            const a = document.getElementById(`active-${roomId}`);
                            const i = document.getElementById(`idle-${roomId}`);
                            if (a) a.textContent = c.active;
                            if (i) i.textContent = c.idle;
                        });
                    })
                    .catch(() => {});
            }

            if (window.Echo) {
                window.Echo.channel('device-status')
                    .listen('.DeviceStatusUpdated', () => {
                        refreshRoomStatuses();
                        refreshTemps();
                    })
                    .listen('.RoomTemperatureUpdated', () => {
                        refreshTemps();
                    })
                    .listen('.AcStatusUpdated', () => refreshAcCounters());
            }

            @if (session('success'))
                window.smToast(@js(session('success')), 'success');
            @endif
            @if (session('error'))
                window.smToast(@js(session('error')), 'error');
            @endif
            @if ($errors->any())
                window.smToast(@js($errors->first()), 'error');
            @endif
        });

        document.addEventListener('DOMContentLoaded', () => {
});
    </script>
    @include('components.sidebar-scripts')
</body>

</html>
