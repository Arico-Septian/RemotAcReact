<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Rooms – SmartAC</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="/js/chart.umd.js"></script>
    @vite('resources/js/app.js')
    @include('components.sidebar-styles')
    <style>
        .room-card {
            position: relative;
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
            border-radius: var(--r-xl);
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: var(--t-base);
            box-shadow: var(--inset-hi);
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

        .room-card:hover {}

        /* Tombol Detail & Grafik — soft blue (senada tema) */
        .room-card .btn.btn-primary,
        .room-card .room-card-chart-btn {
            background: rgba(59, 111, 212, 0.15);
            border-color: rgba(59, 111, 212, 0.30);
            color: #ffffff;
            box-shadow: none;
            transition: var(--t-base);
            justify-content: center;
            padding: 7px 12px;
            font-size: 12px;
            border-radius: 8px !important;
        }

        .room-card .btn.btn-primary:hover,
        .room-card .room-card-chart-btn:hover {
            background: rgba(59, 111, 212, 0.26);
            border-color: rgba(59, 111, 212, 0.45);
            color: #ffffff;
            box-shadow: none;
            transform: none;
        }

        .room-card[data-status="online"] {
            --card-accent: var(--mint);
        }

        .room-card[data-status="offline"] {
            --card-accent: var(--coral);
        }

        .room-card .ac-mini {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .room-card .ac-mini>div {
            text-align: center;
            padding: 8px 6px;
            border-radius: var(--r-md);
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
        }

        .room-card .ac-mini .num {
            font-family: var(--font-mono);
            font-size: 16px;
            font-weight: 700;
            line-height: 1;
        }

        .room-card .ac-mini .lbl {
            font-size: 10px;
            color: var(--ink-3);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-top: 4px;
            font-weight: 700;
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

        /* Status Online/Offline → teks saja, tanpa kotak */
        .room-card .room-status-pill {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            justify-content: center !important;
            text-align: center;
        }

        /* Online = putih, Offline = abu-abu */
        .room-card .room-status-pill.pill-online {
            color: #ffffff !important;
        }

        .room-card .room-status-pill.pill-offline {
            color: #94a3b8 !important;
        }

        .room-card .temp-chip.idle {
            background: var(--panel-2) !important;
            color: var(--ink-3) !important;
            border: 1px solid var(--line-soft) !important;
        }

        /* Suhu: sensor nyala = teks putih, mati = teks abu */
        .room-card .temp-chip.cool,
        .room-card .temp-chip.warm,
        .room-card .temp-chip.hot {
            color: #ffffff !important;
        }

        .room-card .temp-chip.idle {
            color: #94a3b8 !important;
        }

        /* Toolbar responsiveness for small screens */
        @media (max-width: 768px) {
            .flex.flex-row.items-center {
                gap: 6px;
            }

            .flex.flex-row.items-center>label {
                flex: 1;
                min-width: 0;
                transition: flex var(--t-base);
            }

            .flex.flex-row.items-center>.segmented {
                display: inline-flex;
                gap: 1px;
                flex-shrink: 0;
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
                transform: translateY(1px);
            }
        }

        /* Very small screens (< 480px) */
        @media (max-width: 480px) {
            .flex.flex-row.items-center {
                gap: 6px;
            }

            .flex.flex-row.items-center>label {
                flex: 1;
                min-width: 0;
            }

            .flex.flex-row.items-center>label:focus-within {
                flex: 1;
            }

            .flex.flex-row.items-center>.segmented {
                display: inline-flex;
                gap: 2px;
                flex-shrink: 0;
                height: 36px;
            }

            .segmented .seg {
                font-size: 10px;
                padding: 0 8px;
                min-width: auto;
                height: 100%;
                display: inline-flex;
                align-items: center;
            }

            .search-input {
                height: 36px;
            }

            .search-input input {
                height: 36px;
                padding: 0 12px 0 34px;
                font-size: 12px;
                box-sizing: border-box;
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
                left: 12px;
                transition: opacity var(--t-base);
            }

            .search-input:focus-within i {
                opacity: 0;
                pointer-events: none;
            }
        }

        /* Tablet & desktop (≥ 481 px): unify search & segmented height to 40 px */
        @media (min-width: 481px) {
            .flex.flex-row.items-center.gap-2>.search-input {
                height: 40px;
            }

            .flex.flex-row.items-center.gap-2>.search-input input {
                height: 40px;
                box-sizing: border-box;
            }

            .flex.flex-row.items-center.gap-2>.segmented {
                height: 40px;
                box-sizing: border-box;
                display: inline-flex;
                align-items: center;
            }

            .flex.flex-row.items-center.gap-2>.segmented .seg {
                height: 100%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-sizing: border-box;
            }
        }

        /* Room grid: 2 cols (mobile) / 3 cols (tablet) / 5 cols (laptop) — same as Manajemen Ruangan */
        .floor-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 12px !important;
            justify-content: stretch;
        }

        /* Tablet (≥ 768px): 3 cols */
        @media (min-width: 768px) {
            .floor-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }
        }

        /* Laptop (≥ 1024px): 5 cols */
        @media (min-width: 1024px) {
            .floor-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            }
        }

        /* Phones (≤480px): tighter card spacing — grid tetap 2 kolom */
        @media (max-width: 480px) {
            .floor-grid {
                gap: 10px !important;
                margin-bottom: 12px !important;
            }

            .room-card {
                padding: 11px;
                gap: 8px;
            }

            .room-card h3 {
                font-size: 13px;
            }

            .room-card .room-status-pill {
                padding: 0 !important;
                font-size: 12px !important;
            }

            .room-card .temp-chip {
                padding: 6px 10px !important;
                font-size: 10px !important;
            }

            .ac-mini {
                gap: 8px;
            }

            .ac-mini>div {
                padding: 8px 6px;
            }

            .ac-mini .num {
                font-size: 16px;
            }

            .ac-mini .lbl {
                font-size: 8px;
                margin-top: 2px;
            }

            .room-card .btn.btn-primary.btn-sm,
            .room-card .room-card-chart-btn {
                font-size: 11px;
                padding: 7px 10px;
                min-height: 34px;
            }

            .room-card .btn-icon {
                width: 34px;
                height: 34px;
            }
        }

        /* Header: keep on one row always */
        .main-header {
            flex-wrap: nowrap;
        }

        .main-header>.flex.items-center.gap-3 {
            min-width: 0;
            flex: 1;
        }

        .main-header>.flex.items-center.gap-2 {
            flex-shrink: 0;
        }

        /* Mobile M / L (≤ 480 px): subtitle moderately smaller so it fits 1 line */
        @media (max-width: 480px) {
            .main-header .app-header-title h1 {
                font-size: 16px;
                line-height: 1.2;
            }

            .main-header .app-header-title p {
                font-size: 11px;
                line-height: 1.3;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        /* Tiny phones (≤480px): grid tetap 2 kolom, lebih rapat */
        @media (max-width: 480px) {
            .floor-grid {
                gap: 8px !important;
            }

            /* Header: aggressive shrink so everything fits at 320 px */
            .main-header {
                gap: 6px;
                padding-left: 10px;
                padding-right: 10px;
            }

            .main-header>.flex.items-center.gap-3 {
                gap: 6px;
            }

            .main-header>.flex.items-center.gap-2 {
                gap: 4px;
            }

            .main-header .app-header-title h1 {
                font-size: 13px;
                line-height: 1.2;
            }

            .main-header .app-header-title p {
                font-size: 10px;
            }

            /* Pulse halo: keep dead-center using transform instead of inset */
            .main-header .btn-icon {
                width: 32px;
                height: 32px;
            }
        }

        /* Touch targets optimization */
        @media (max-width: 768px) {
            .btn.btn-primary.btn-sm {
                min-height: 40px;
                padding: 8px 12px;
            }

            .btn-icon {
                width: 40px;
                height: 40px;
            }
        }

        /* Landscape mode */
        @media (max-height: 600px) and (orientation: landscape) {
            .room-card {
                padding: 10px;
                gap: 6px;
            }

            .room-card h3 {
                font-size: 12px;
            }

            .ac-mini>div {
                padding: 4px 4px;
            }

            .ac-mini .num {
                font-size: 12px;
            }

            .floor-section-header {
                margin-bottom: 8px;
            }
        }

        .history-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        #historyModal {
            padding: 18px;
        }

        #historyModal .modal {
            width: min(640px, 100%);
            max-height: calc(100dvh - 36px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #0a0a0c;
            background-image: none;
            border: 1px solid rgba(255, 255, 255, 0.07);
        }

        #historyModal .modal-header {
            align-items: flex-start;
            gap: 14px;
        }

        #historyModal .history-title-group {
            min-width: 0;
            flex: 1;
        }

        #historyModal .history-title-group .eyebrow {
            line-height: 1.3;
            color: #5a93ec;
        }

        #historyTitle {
            overflow-wrap: anywhere;
            line-height: 1.2;
        }

        #historyModal .modal-body {
            flex: 1;
            min-height: 0;
            padding-bottom: 22px;
        }

        #historyChartWrap {
            height: clamp(220px, 45vh, 300px);
            min-height: 0;
        }

        #historyChartScroller {
            height: 100%;
        }

        #historyChart {
            width: 100% !important;
            height: 100% !important;
        }

        #historyChartWrap.show-full-range {
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 2px;
            scrollbar-width: thin;
            scrollbar-color: rgb(var(--ink-2-rgb) / 0.42) transparent;
        }

        .history-range-select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: var(--panel-1);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23a7b0c0' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 9px center;
            background-size: 11px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            color: var(--ink-1);
            border-radius: 8px;
            padding: 6px 28px 6px 12px;
            font-size: 11px;
            font-family: var(--font-sans);
            line-height: 1.2;
            text-align: left;
            cursor: pointer;
            outline: none;
            transition: var(--t-base);
        }

        .history-range-select option {
            background-color: #0a0a0c;
            color: #ffffff;
        }

        .history-range-select:hover,
        .history-range-select:focus {
            background-color: var(--panel-1);
            border-color: rgba(255, 255, 255, 0.6);
            color: var(--ink-1);
            box-shadow: none;
            outline: none;
        }

        @media (max-width: 768px) {
            #historyModal {
                padding: 14px;
            }

            #historyModal .modal {
                max-height: calc(100dvh - 28px);
            }

            #historyModal .modal-header {
                padding: 18px 18px 10px;
            }

            #historyModal .modal-header h2 {
                font-size: 16px;
            }

            #historyModal .modal-header .sub {
                font-size: 11px;
            }

            #historyMeta {
                overflow-wrap: anywhere;
            }

            #historyModal .modal-body {
                padding: 6px 18px 18px;
            }

            #historyChartWrap {
                height: clamp(210px, 42vh, 260px);
            }

        }

        @media (max-width: 480px) {
            #historyModal {
                padding: 10px;
                align-items: center;
            }

            #historyModal .modal {
                max-height: calc(100dvh - 20px);
                border-radius: var(--r-2xl);
            }

            #historyModal .modal-header {
                padding: 16px 16px 8px;
                gap: 10px;
            }

            #historyModal .history-title-group .eyebrow {
                font-size: 10px;
                letter-spacing: 0.05em;
            }

            #historyModal .modal-header h2 {
                font-size: 14px;
            }

            #historyModal .modal-header .sub {
                font-size: 11px;
                line-height: 1.4;
            }

            #historyModal .modal-body {
                padding: 4px 14px 16px;
            }

            #historyChartWrap {
                height: min(230px, 43vh);
            }

            .history-actions {
                gap: 6px;
            }


            .history-range-select {
                min-height: 36px;
                padding-left: 11px;
                padding-right: 26px;
                font-size: 11px;
                background-position: right 9px center;
            }

            #historyModal .modal-close {
                width: 36px;
                height: 36px;
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
                        <h1>Server Rooms</h1>
                        <p>{{ $rooms->count() }} rooms · AC monitoring</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @include('components.notification-bell')
                </div>
            </header>
            <div class="page-body">
                <div class="app-content">
                    <div class="app-content-inner space-y-4">
                        {{-- Toolbar --}}
                        <div class="flex flex-row items-center gap-2">
                            <label class="search-input flex-1 min-w-0">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input id="searchInput" type="text" placeholder="Search room name…"
                                    autocomplete="off">
                            </label>
                            <div class="segmented flex-shrink-0">
                                <button class="seg active" data-filter="all">All</button>
                                <button class="seg" data-filter="online">
                                    Online
                                </button>
                                <button class="seg" data-filter="offline">
                                    Offline
                                </button>
                            </div>
                        </div>
                        <p id="roomCount" class="text-mono text-xs" style="color:var(--ink-3);"></p>
                        @if ($rooms->count() > 0)
                            <div id="allSections">
                                @foreach ($roomsByFloor as $floorName => $floorRooms)
                                    <div class="floor-section" data-section-floor="{{ $floorName }}">
                                        <div class="floor-section-header">
                                            <i class="fa-solid fa-layer-group text-[10px]"
                                                style="color:var(--lavender);"></i>
                                            <span class="floor-label">{{ ucfirst($floorName) }}</span>
                                            <div class="floor-divider"></div>
                                            <span class="floor-count">{{ $floorRooms->count() }} rooms</span>
                                        </div>
                                        <div
                                            class="floor-grid grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3 mb-6">
                                            @foreach ($floorRooms as $room)
                                                @php
                                                    $activeCount = $room->acUnits
                                                        ->filter(fn($ac) => optional($ac->status)->power === 'ON')
                                                        ->count();
                                                    $inactiveCount = $room->acUnits
                                                        ->filter(fn($ac) => optional($ac->status)->power !== 'ON')
                                                        ->count();
                                                    $temp = $room->temperature ?? ($room->last_temperature ?? null);
                                                    $status = $room->device_status ?? 'offline';
                                                    $tempClass =
                                                        $temp === null
                                                            ? 'idle'
                                                            : ($temp > 30
                                                                ? 'hot'
                                                                : ($temp > 25
                                                                    ? 'warm'
                                                                    : 'cool'));
                                                @endphp
                                                <div class="room-card" data-room-id="{{ $room->id }}"
                                                    data-name="{{ strtolower($room->name) }}"
                                                    data-status="{{ $status }}" data-floor="{{ $floorName }}">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <h3 class="font-semibold text-tight"
                                                            style="color:var(--ink-0);line-height: 1.3;font-size:16px;">
                                                            {{ ucfirst($room->name) }}
                                                        </h3>
                                                        <span
                                                            class="pill room-status-pill {{ $status === 'online' ? 'pill-online' : 'pill-offline' }}"
                                                            style="font-size:12px;">
                                                            <span
                                                                class="room-status-text">{{ $status === 'online' ? 'Online' : 'Offline' }}</span>
                                                        </span>
                                                    </div>
                                                    <div class="temp-chip {{ $room->temperature_is_offline ? 'idle' : $tempClass }}"
                                                        style="justify-content:space-between;width:100%;">
                                                        <span
                                                            style="display:inline-flex;align-items:center;gap:6px;font-weight:500;">
                                                            <i class="fa-solid fa-temperature-half text-[10px]"></i>Temp
                                                        </span>
                                                        <span style="display:inline-flex;align-items:center;gap:5px;">
                                                            @if ($room->temperature_is_offline)
                                                                <i class="fa-solid fa-wifi-slash temp-offline-icon"
                                                                    style="font-size:11px;color:var(--coral);"></i>
                                                            @endif
                                                            <span id="temp-{{ $room->id }}" class="text-mono"
                                                                data-offline="{{ $room->temperature_is_offline ? 'true' : 'false' }}">
                                                                {{ $temp ?? '–' }}°C
                                                            </span>
                                                        </span>
                                                    </div>
                                                    <div class="ac-mini">
                                                        <div>
                                                            <p class="num"
                                                                style="color:#ffffff;font-family:var(--font-mono);font-size:16px;font-weight:700;line-height:1;margin:0;"
                                                                id="ov-active-{{ $room->id }}">
                                                                {{ $activeCount }}</p>
                                                            <p class="lbl"
                                                                style="font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--ink-3);margin-top:4px;">
                                                                Active</p>
                                                        </div>
                                                        <div>
                                                            <p class="num"
                                                                style="color:#ffffff;font-family:var(--font-mono);font-size:16px;font-weight:700;line-height:1;margin:0;"
                                                                id="ov-idle-{{ $room->id }}">
                                                                {{ $inactiveCount }}</p>
                                                            <p class="lbl"
                                                                style="font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--ink-3);margin-top:4px;">
                                                                Idle</p>
                                                        </div>
                                                    </div>
                                                    <p class="text-xs text-center"
                                                        style="color:var(--ink-4);margin-top:-2px;">
                                                        {{ $room->acUnits->count() }} unit total</p>
                                                    <div class="grid grid-cols-2 gap-2 mt-auto">
                                                        <a href="/rooms/{{ $room->id }}/status"
                                                            class="btn btn-primary btn-sm"
                                                            style="justify-content:center;">
                                                            Detail
                                                        </a>
                                                        <button type="button"
                                                            onclick="openHistory({{ $room->id }}, @js(ucfirst($room->name)))"
                                                            class="btn btn-sm room-card-chart-btn"
                                                            title="24-hour temperature history">
                                                            Grafik
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div id="emptyState" class="empty-state" hidden>
                                <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                                <p class="empty-title">Not found</p>
                                <p class="empty-sub">Try a different keyword or filter</p>
                            </div>
                        @else
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-server"></i></div>
                                <p class="empty-title">No rooms</p>
                                <p class="empty-sub">Contact an administrator to add rooms</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- HISTORY MODAL --}}
    <div id="historyModal" class="modal-backdrop">
        <div class="modal modal-lg">
            <div class="modal-header">
                <div class="history-title-group">
                    <p class="eyebrow" style="color:#5a93ec;"><i class="fa-solid fa-chart-line"></i> Temperature
                        History</p>
                    <h2 id="historyTitle">Room</h2>
                    <p id="historyMeta" class="sub">Today · hourly average</p>
                </div>
                <div class="history-actions">
                    <select id="historyRange" class="history-range-select" title="Select history range">
                        <option value="1h">1h</option>
                        <option value="3h">3h</option>
                        <option value="6h">6h</option>
                        <option value="today">Today</option>
                    </select>
                    <button type="button" class="modal-close" onclick="closeHistory()"><i
                            class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
            <div class="modal-body">
                <div id="historyLoading" class="empty-state" style="padding:36px 0;">
                    <div class="empty-icon"><i class="fa-solid fa-spinner fa-spin"></i></div>
                    <p class="empty-sub">Memuat data…</p>
                </div>
                <div id="historyEmpty" class="empty-state" style="padding:36px 0;" hidden>
                    <div class="empty-icon"><i class="fa-solid fa-temperature-empty"></i></div>
                    <p class="empty-sub">No temperature data in the last 24 hours</p>
                </div>
                <div id="historyChartWrap" hidden>
                    <div id="historyChartScroller">
                        <canvas id="historyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('components.bottom-nav')
    <script>
        /* ===== SEARCH, STATUS & FLOOR FILTER ===== */
        const cards = Array.from(document.querySelectorAll('.room-card'));
        const sections = Array.from(document.querySelectorAll('.floor-section'));
        const emptyState = document.getElementById('emptyState');
        const countEl = document.getElementById('roomCount');
        let activeStatus = 'all';
        let activeFloor = 'all';

        function applyFilter() {
            const q = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();
            let visible = 0;

            cards.forEach(card => {
                const matchSearch = !q || card.dataset.name.includes(q);
                const matchStatus = activeStatus === 'all' || card.dataset.status === activeStatus;
                const matchFloor = activeFloor === 'all' || card.dataset.floor === activeFloor;
                const show = matchSearch && matchStatus && matchFloor;
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            // Show/hide section headers based on whether they have visible cards
            sections.forEach(sec => {
                const sFloor = sec.dataset.sectionFloor;
                const hasVisible = cards.some(c => c.dataset.floor === sFloor && c.style.display !== 'none');
                sec.style.display = hasVisible ? '' : 'none';
            });

            countEl.textContent = visible === cards.length ?
                `Showing ${cards.length} room${cards.length !== 1 ? 's' : ''}` :
                `${visible} of ${cards.length} room${cards.length !== 1 ? 's' : ''}`;

            if (emptyState) emptyState.hidden = visible > 0;
        }

        document.getElementById('searchInput')?.addEventListener('input', applyFilter);

        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                activeStatus = this.dataset.filter;
                applyFilter();
            });
        });

        document.addEventListener('DOMContentLoaded', applyFilter);

        function setRoomStatus(card, online) {
            card.dataset.status = online ? 'online' : 'offline';

            const pill = card.querySelector('.room-status-pill');
            const text = card.querySelector('.room-status-text');
            if (!pill || !text) return;

            pill.classList.toggle('pill-online', online);
            pill.classList.toggle('pill-offline', !online);
            text.textContent = online ? 'Online' : 'Offline';
        }

        let _statusFetchFailed = false;
        let _tempFetchFailed = false;

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

                    applyFilter();
                })
                .catch(() => {
                    if (!_statusFetchFailed) {
                        _statusFetchFailed = true;
                        window.smToast?.('Failed to load device status', 'error');
                    }
                });
        }

        setInterval(refreshRoomStatuses, 5000);
        document.addEventListener('DOMContentLoaded', refreshRoomStatuses);

        /* ===== HISTORY MODAL ===== */
        let historyChartInstance = null;
        let historyCurrentRoomName = 'Room';
        let historyRoomId = null;
        const historyRangeText = {
            '1h': 'Last 1 hour',
            '3h': 'Last 3 hours',
            '6h': 'Last 6 hours',
            'today': 'Today'
        };
        const historyRangeConfig = {
            '1h': {
                intervalMinutes: 5,
                slots: 12
            },
            '3h': {
                intervalMinutes: 10,
                slots: 18
            },
            '6h': {
                intervalMinutes: 15,
                slots: 24
            },
            'today': {
                intervalMinutes: 60,
                today: true
            }
        };

        function padHour(value) {
            return String(value).padStart(2, '0');
        }

        function getHistoryRange() {
            const saved = localStorage.getItem('historyRange') || 'today';
            const normalized = saved === '24h' ? 'today' : saved;

            return historyRangeConfig[normalized] ? normalized : 'today';
        }

        function setHistoryRange(value) {
            const normalized = value === '24h' ? 'today' : value;
            const range = historyRangeConfig[normalized] ? normalized : 'today';
            localStorage.setItem('historyRange', range);

            const select = document.getElementById('historyRange');
            if (select) select.value = range;

            return range;
        }

        function currentHistoryRangeText() {
            return historyRangeText[getHistoryRange()] || historyRangeText.today;
        }

        function historyStatusText(status) {
            if (!status) return null;

            return status.is_offline === true ? 'Offline' : 'Online';
        }

        function historyStatusSuffix(status) {
            const text = historyStatusText(status);

            return text ? ` · ${text}` : '';
        }

        function fetchHistoryStatus(roomId) {
            return fetch('/temperature', {
                    headers: {
                        'Accept': 'application/json'
                    },
                    cache: 'no-store'
                })
                .then(r => r.ok ? r.json() : null)
                .then(data => Array.isArray(data) ? data.find(room => Number(room.id) === Number(roomId)) : null)
                .catch(() => null);
        }

        function historySlotCount(range = getHistoryRange()) {
            const config = historyRangeConfig[range] || historyRangeConfig.today;

            if (!config.today) return config.slots;

            return alignDateToHistorySlot(new Date(), config.intervalMinutes).getHours() + 1;
        }

        function alignDateToHistorySlot(date, intervalMinutes) {
            const aligned = new Date(date);
            aligned.setSeconds(0, 0);

            if (intervalMinutes >= 60) {
                aligned.setMinutes(0, 0, 0);
                return aligned;
            }

            aligned.setMinutes(Math.floor(aligned.getMinutes() / intervalMinutes) * intervalMinutes, 0, 0);

            return aligned;
        }

        function makeHistoryTimeLabel(date, intervalMinutes) {
            if (intervalMinutes >= 60) return `${padHour(date.getHours())}:00`;

            return `${padHour(date.getHours())}:${padHour(date.getMinutes())}`;
        }

        function historyChartOptionsForViewport() {
            const width = window.innerWidth || document.documentElement.clientWidth || 1024;
            const range = getHistoryRange();
            const slotCount = historySlotCount(range);

            if (width <= 420) {
                return {
                    pointRadius: 2.8,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                    tickSize: slotCount >= 18 ? 8 : 8.5,
                    xMaxTicks: slotCount,
                    xAutoSkip: false,
                    yMaxTicks: 9,
                    minWidth: Math.max(420, slotCount * 30),
                    padding: {
                        top: 8,
                        right: 4,
                        bottom: 0,
                        left: 0
                    }
                };
            }

            if (width <= 768) {
                return {
                    pointRadius: 3.2,
                    pointHoverRadius: 5.5,
                    borderWidth: 2,
                    tickSize: slotCount >= 18 ? 8.5 : 9,
                    xMaxTicks: slotCount,
                    xAutoSkip: false,
                    yMaxTicks: 9,
                    minWidth: Math.max(520, slotCount * 32),
                    padding: {
                        top: 10,
                        right: 8,
                        bottom: 0,
                        left: 0
                    }
                };
            }

            return {
                pointRadius: 4,
                pointHoverRadius: 6,
                borderWidth: 2,
                tickSize: slotCount >= 18 ? 9 : 10,
                xMaxTicks: slotCount,
                xAutoSkip: false,
                yMaxTicks: 9,
                minWidth: slotCount >= 18 ? slotCount * 34 : 0,
                padding: {
                    top: 12,
                    right: 10,
                    bottom: 0,
                    left: 0
                }
            };
        }

        function renderHistoryChart(data, status = null) {
            const chartSizing = historyChartOptionsForViewport();

            document.getElementById('historyLoading').hidden = true;
            document.getElementById('historyEmpty').hidden = true;
            const chartWrap = document.getElementById('historyChartWrap');
            chartWrap.hidden = false;
            chartWrap.classList.add('show-full-range');

            const chartScroller = document.getElementById('historyChartScroller');
            if (chartScroller) {
                chartScroller.style.minWidth = chartSizing.minWidth > 0 ? `${chartSizing.minWidth}px` : '';
            }

            if (historyChartInstance) {
                historyChartInstance.destroy();
                historyChartInstance = null;
            }

            const labels = data.map(d => d.time);
            const temps = data.map(d => d.temp);
            const latestPoint = [...data]
                .reverse()
                .find(point => point.temp !== null && point.temp !== undefined && !Number.isNaN(Number(point.temp)));
            const metaEl = document.getElementById('historyMeta');

            if (metaEl) {
                metaEl.textContent = latestPoint ?
                    `${currentHistoryRangeText()} · last ${Number(latestPoint.temp).toFixed(1)}°C at ${latestPoint.time}${historyStatusSuffix(status)}` :
                    `${currentHistoryRangeText()} · hourly average${historyStatusSuffix(status)}`;
            }

            const pointColor = t => t > 30 ? '#fb7185' : t > 25 ? '#fbbf24' : '#2563eb';
            const ctx = document.getElementById('historyChart').getContext('2d');

            historyChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Temp (°C)',
                        data: temps,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(38,99,235,0.10)',
                        pointBackgroundColor: temps.map(pointColor),
                        pointRadius: chartSizing.pointRadius,
                        pointHoverRadius: chartSizing.pointHoverRadius,
                        tension: 0.4,
                        fill: true,
                        spanGaps: true,
                        borderWidth: chartSizing.borderWidth
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    layout: {
                        padding: chartSizing.padding
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(7,16,31,0.96)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: 'rgba(77,212,255,0.40)',
                            borderWidth: 1,
                            padding: 10,
                            cornerRadius: 10,
                            displayColors: false,
                            callbacks: {
                                title: items => `Hour: ${items?.[0]?.label ?? '-'}`,
                                label: c => {
                                    const value = c.parsed.y;
                                    if (value === null || Number.isNaN(value)) return 'Temp: No data';

                                    return `Temp: ${Number(value).toFixed(1)}°C`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#64748b',
                                autoSkip: chartSizing.xAutoSkip,
                                maxTicksLimit: chartSizing.xMaxTicks,
                                maxRotation: 0,
                                minRotation: 0,
                                font: {
                                    size: chartSizing.tickSize
                                }
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            min: 20,
                            max: 36,
                            ticks: {
                                color: '#64748b',
                                maxTicksLimit: chartSizing.yMaxTicks,
                                stepSize: 2,
                                font: {
                                    size: chartSizing.tickSize
                                },
                                callback: v => v + '°C'
                            },
                            grid: {
                                color: 'rgba(255,255,255,0.04)'
                            }
                        }
                    }
                }
            });
        }

        function applyHistoryChartResponsiveSizing() {
            if (!historyChartInstance) return;

            const chartSizing = historyChartOptionsForViewport();

            historyChartInstance.options.layout.padding = chartSizing.padding;
            historyChartInstance.options.scales.x.ticks.font.size = chartSizing.tickSize;
            historyChartInstance.options.scales.x.ticks.maxTicksLimit = chartSizing.xMaxTicks;
            historyChartInstance.options.scales.x.ticks.autoSkip = chartSizing.xAutoSkip;
            historyChartInstance.options.scales.y.ticks.font.size = chartSizing.tickSize;
            historyChartInstance.options.scales.y.ticks.maxTicksLimit = chartSizing.yMaxTicks;
            document.getElementById('historyChartWrap')?.classList.add('show-full-range');

            const chartScroller = document.getElementById('historyChartScroller');
            if (chartScroller) {
                chartScroller.style.minWidth = chartSizing.minWidth > 0 ? `${chartSizing.minWidth}px` : '';
            }

            historyChartInstance.data.datasets.forEach(dataset => {
                dataset.pointRadius = chartSizing.pointRadius;
                dataset.pointHoverRadius = chartSizing.pointHoverRadius;
                dataset.borderWidth = chartSizing.borderWidth;
            });

            historyChartInstance.update('none');
        }

        function openHistory(roomId, roomName) {
            historyRoomId = roomId;
            historyCurrentRoomName = roomName;
            setHistoryRange(getHistoryRange());
            document.getElementById('historyTitle').textContent = roomName;
            document.getElementById('historyModal').classList.add('is-open');
            document.getElementById('historyLoading').hidden = false;
            document.getElementById('historyEmpty').hidden = true;
            document.getElementById('historyChartWrap').hidden = true;
            document.getElementById('historyChartWrap').classList.remove('show-full-range');
            document.getElementById('historyMeta').textContent = `${currentHistoryRangeText()} · hourly average`;

            if (historyChartInstance) {
                historyChartInstance.destroy();
                historyChartInstance = null;
            }

            fetch(`/temperature/history/${roomId}?range=${encodeURIComponent(getHistoryRange())}`)
                .then(r => r.ok ? r.json() : [])
                .then(data => {
                    return fetchHistoryStatus(roomId).then(status => ({
                        data,
                        status
                    }));
                })
                .then(({
                    data,
                    status
                }) => {
                    document.getElementById('historyLoading').hidden = true;
                    if (!data || data.length === 0) {
                        document.getElementById('historyEmpty').hidden = false;
                        return;
                    }
                    renderHistoryChart(data, status);
                })
                .catch(() => {
                    document.getElementById('historyLoading').hidden = true;
                    document.getElementById('historyEmpty').hidden = false;
                });
        }

        function closeHistory() {
            document.getElementById('historyModal').classList.remove('is-open');
            if (historyChartInstance) {
                historyChartInstance.destroy();
                historyChartInstance = null;
            }
        }
        document.getElementById('historyModal')?.addEventListener('click', e => {
            if (e.target === e.currentTarget) closeHistory();
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeHistory();
        });

        document.getElementById('historyRange')?.addEventListener('change', e => {
            setHistoryRange(e.target.value);

            if (historyRoomId !== null && document.getElementById('historyModal')?.classList.contains('is-open')) {
                openHistory(historyRoomId, historyCurrentRoomName);
            }
        });

        let historyResizeTimer = null;
        window.addEventListener('resize', () => {
            clearTimeout(historyResizeTimer);
            historyResizeTimer = setTimeout(applyHistoryChartResponsiveSizing, 120);
        });

        /* ===== LIVE TEMP ===== */
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

        function refreshTemps() {
            fetch('/temperature').then(r => r.ok ? r.json() : null).then(data => {
                _tempFetchFailed = false;
                if (!data) return;
                data.forEach(updateRoomTemperature);
            }).catch(() => {
                if (!_tempFetchFailed) {
                    _tempFetchFailed = true;
                    window.smToast?.('Failed to load room temperature data', 'error');
                }
            });
        }
        setInterval(refreshTemps, 5000);
        document.addEventListener('DOMContentLoaded', refreshTemps);

        document.addEventListener('DOMContentLoaded', () => {
            // Real-time: counter Active/Idle per kartu tanpa reload
            function refreshAcCountersOverview() {
                fetch('/api/ac-status', {
                        headers: {
                            'Accept': 'application/json'
                        },
                        cache: 'no-store'
                    })
                    .then(r => r.ok ? r.json() : null)
                    .then(data => {
                        if (!Array.isArray(data)) return;
                        const counts = {};
                        data.forEach(item => {
                            const roomId = item.ac_unit?.room?.id ?? item.acUnit?.room?.id;
                            if (!roomId) return;
                            if (!counts[roomId]) counts[roomId] = {
                                active: 0,
                                idle: 0
                            };
                            if ((item.power || 'OFF').toUpperCase() === 'ON') counts[roomId].active++;
                            else counts[roomId].idle++;
                        });
                        Object.entries(counts).forEach(([roomId, c]) => {
                            const a = document.getElementById(`ov-active-${roomId}`);
                            const i = document.getElementById(`ov-idle-${roomId}`);
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
                    .listen('.AcStatusUpdated', () => refreshAcCountersOverview());
            }
        });
    </script>
    @include('components.sidebar-scripts')
</body>

</html>
