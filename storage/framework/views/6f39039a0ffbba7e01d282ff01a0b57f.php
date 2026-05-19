<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Rooms – SmartAC</title>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="/js/chart.umd.js"></script>
    <?php echo app('Illuminate\Foundation\Vite')('resources/js/app.js'); ?>
    <?php echo $__env->make('components.sidebar-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <style>
        .room-card {
            position: relative;
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
            border-radius: var(--r-xl);
            padding: 14px;
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

        .room-card:hover {
            background: var(--panel-2);
            border-color: var(--line);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
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
            gap: 6px;
        }

        .room-card .ac-mini>div {
            text-align: center;
            padding: 8px 6px;
            border-radius: var(--r-md);
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
        }

        .room-card .ac-mini .num {
            font-family: 'JetBrains Mono', monospace;
            font-size: 16px;
            font-weight: 700;
            line-height: 1;
        }

        .room-card .ac-mini .lbl {
            font-size: 9.5px;
            color: var(--ink-3);
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-top: 4px;
            font-weight: 600;
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
            letter-spacing: 0.04em;
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
            font-size: 10.5px !important;
        }
        .room-card .temp-chip.cool {
            background: rgba(94, 208, 255, 0.15) !important;
            color: #5ed0ff !important;
            border: 1px solid rgba(94, 208, 255, 0.35) !important;
        }
        .room-card .temp-chip.warm {
            background: rgba(110, 231, 183, 0.15) !important;
            color: #6ee7b7 !important;
            border: 1px solid rgba(110, 231, 183, 0.35) !important;
        }
        .room-card .temp-chip.hot {
            background: rgba(248, 113, 113, 0.15) !important;
            color: #f87171 !important;
            border: 1px solid rgba(248, 113, 113, 0.4) !important;
        }
        .room-card .temp-chip.idle {
            background: var(--panel-2) !important;
            color: var(--ink-3) !important;
            border: 1px solid var(--line-soft) !important;
        }

        /* Toolbar responsiveness for small screens */
        @media (max-width: 768px) {
            .flex.flex-row.items-center {
                gap: 6px;
            }

            .flex.flex-row.items-center > label {
                flex: 1;
                min-width: 0;
                transition: flex 0.2s ease;
            }

            .flex.flex-row.items-center > .segmented {
                display: inline-flex;
                gap: 1px;
                flex-shrink: 0;
            }

            .segmented .seg {
                font-size: 10.5px;
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
            .flex.flex-row.items-center {
                gap: 6px;
            }

            .flex.flex-row.items-center > label {
                flex: 1;
                min-width: 0;
            }

            .flex.flex-row.items-center > label:focus-within {
                flex: 1;
            }

            .flex.flex-row.items-center > .segmented {
                display: inline-flex;
                gap: 2px;
                flex-shrink: 0;
            }

            .segmented .seg {
                font-size: 10px;
                padding: 5px 6px;
                min-width: auto;
                min-height: auto;
            }

            .search-input input {
                font-size: 12px;
                padding: 6px 8px 6px 28px;
            }

            .search-input input::placeholder {
                color: var(--ink-3);
                transition: color 0.2s ease;
            }

            .search-input input:focus::placeholder {
                color: transparent;
            }

            .search-input i {
                font-size: 12px;
                transition: opacity 0.2s ease;
            }

            .search-input:focus-within i {
                opacity: 0;
                pointer-events: none;
            }
        }

        /* Tablet & desktop (≥ 481 px): unify search & segmented height to 40 px */
        @media (min-width: 481px) {
            .flex.flex-row.items-center.gap-2 > .search-input {
                height: 40px;
            }
            .flex.flex-row.items-center.gap-2 > .search-input input {
                height: 40px;
                box-sizing: border-box;
            }
            .flex.flex-row.items-center.gap-2 > .segmented {
                height: 40px;
                box-sizing: border-box;
                display: inline-flex;
                align-items: center;
            }
            .flex.flex-row.items-center.gap-2 > .segmented .seg {
                height: 100%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-sizing: border-box;
            }
        }

        /* Auto-fit grid — works across all breakpoints, never stretches a lone card */
        .floor-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(190px, 240px)) !important;
            gap: 12px !important;
            justify-content: start;
        }

        /* Phones (≤600px): tighter card, 2-col where space allows */
        @media (max-width: 600px) {
            .floor-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) !important;
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
                padding: 2px 7px !important;
                font-size: 9px !important;
            }

            .room-card .temp-chip {
                padding: 6px 10px !important;
                font-size: 10px !important;
            }

            .ac-mini { gap: 5px; }
            .ac-mini > div { padding: 6px 5px; }
            .ac-mini .num { font-size: 14px; }
            .ac-mini .lbl { font-size: 8.5px; margin-top: 2px; }

            .room-card .btn.btn-primary.btn-sm {
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
        .main-header { flex-wrap: nowrap; }
        .main-header > .flex.items-center.gap-3 { min-width: 0; flex: 1; }
        .main-header > .flex.items-center.gap-2 { flex-shrink: 0; }

        /* Mobile M / L (≤ 480 px): subtitle moderately smaller so it fits 1 line */
        @media (max-width: 480px) {
            .main-header .app-header-title h1 { font-size: 16px; line-height: 1.2; }
            .main-header .app-header-title p {
                font-size: 11px;
                line-height: 1.25;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        /* Tiny phones (≤360px): force 1-col so card stays legible */
        @media (max-width: 360px) {
            .floor-grid {
                grid-template-columns: 1fr !important;
            }

            /* Header: aggressive shrink so everything fits at 320 px */
            .main-header { gap: 6px; padding-left: 10px; padding-right: 10px; }
            .main-header > .flex.items-center.gap-3 { gap: 6px; }
            .main-header > .flex.items-center.gap-2 { gap: 4px; }
            .main-header .app-header-title h1 { font-size: 13px; line-height: 1.2; }
            .main-header .app-header-title p { font-size: 9.5px; }
            .main-header #systemStatus span:not(.dot) { display: none; }
            .main-header #systemStatus {
                width: 28px;
                height: 28px;
                padding: 0 !important;
                gap: 0 !important;
                border-radius: 50%;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-shrink: 0;
                line-height: 0;
                box-sizing: border-box;
            }
            .main-header #systemStatus .dot {
                width: 8px !important;
                height: 8px !important;
                min-width: 8px;
                min-height: 8px;
                flex-shrink: 0;
                border-radius: 50%;
                aspect-ratio: 1 / 1;
                margin: 0 !important;
                position: relative;
            }
            /* Pulse halo: keep dead-center using transform instead of inset */
            .main-header #systemStatus .dot::after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 100%;
                height: 100%;
                transform: translate(-50%, -50%);
                transform-origin: center center;
                border-radius: 50%;
                background: var(--mint);
                opacity: 0.4;
                animation: pulse-dot 1.6s ease-out infinite;
                inset: auto;
            }
            .main-header .btn-icon { width: 32px; height: 32px; }
        }

        /* Touch targets optimization */
        @media (max-width: 640px) {
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

            .ac-mini > div {
                padding: 4px 4px;
            }

            .ac-mini .num {
                font-size: 12px;
            }

            .floor-section-header {
                margin-bottom: 8px;
            }
        }
    </style>
</head>

<body>
    <div class="custom-bg"></div>
    <div id="overlay"></div>

    <div class="layout">
        <?php echo $__env->make('components.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="main-content">
            <header class="main-header">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="lg:hidden btn-icon" title="Menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="app-header-title">
                        <h1>Server Rooms</h1>
                        <p><?php echo e($rooms->count()); ?> ruangan · AC monitoring</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <?php echo $__env->make('components.notification-bell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <span id="systemStatus" class="pill pill-online">
                        <span class="dot"></span>
                        <span>Online</span>
                    </span>
                </div>
            </header>

            <div class="page-body">
                <div class="app-content">
                    <div class="app-content-inner space-y-4">

                        
                        <div class="flex flex-row items-center gap-2">
                            <label class="search-input flex-1 min-w-0">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input id="searchInput" type="text" placeholder="Cari nama ruangan…"
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

                        <?php if($rooms->count() > 0): ?>
                            <div id="allSections">
                                <?php $__currentLoopData = $roomsByFloor; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $floorName => $floorRooms): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="floor-section" data-section-floor="<?php echo e($floorName); ?>">
                                        <?php if($roomsByFloor->count() > 1): ?>
                                            <div class="floor-section-header">
                                                <i class="fa-solid fa-layer-group text-[10px]"
                                                    style="color:var(--lavender);"></i>
                                                <span class="floor-label"><?php echo e(ucfirst($floorName)); ?></span>
                                                <div class="floor-divider"></div>
                                                <span class="floor-count"><?php echo e($floorRooms->count()); ?> ruangan</span>
                                            </div>
                                        <?php endif; ?>
                                        <div
                                            class="floor-grid grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3 mb-6">
                                            <?php $__currentLoopData = $floorRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $activeCount = $room->acUnits
                                                        ->filter(fn($ac) => optional($ac->status)->power === 'ON')
                                                        ->count();
                                                    $inactiveCount = $room->acUnits
                                                        ->filter(fn($ac) => optional($ac->status)->power !== 'ON')
                                                        ->count();
                                                    $temp = $room->temperature;
                                                    $status = $room->device_status ?? 'offline';
                                                    $tempClass =
                                                        $temp === null
                                                            ? 'idle'
                                                            : ($temp > 30
                                                                ? 'hot'
                                                                : ($temp > 25
                                                                    ? 'warm'
                                                                    : 'cool'));
                                                ?>
                                                <div class="room-card" data-room-id="<?php echo e($room->id); ?>"
                                                    data-name="<?php echo e(strtolower($room->name)); ?>"
                                                    data-status="<?php echo e($status); ?>" data-floor="<?php echo e($floorName); ?>">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <h3 class="font-semibold text-tight"
                                                            style="color:var(--ink-0);line-height:1.25;font-size:16px;">
                                                            <?php echo e(ucfirst($room->name)); ?>

                                                        </h3>
                                                        <span
                                                            class="pill room-status-pill <?php echo e($status === 'online' ? 'pill-online' : 'pill-offline'); ?>"
                                                            style="padding:3px 8px;font-size:10px;">
                                                            <span class="dot"></span><span
                                                                class="room-status-text"><?php echo e($status === 'online' ? 'Online' : 'Offline'); ?></span>
                                                        </span>
                                                    </div>

                                                    <div class="temp-chip <?php echo e($room->temperature_is_offline ? 'idle' : $tempClass); ?>"
                                                        style="justify-content:space-between;width:100%;">
                                                        <span style="display:inline-flex;align-items:center;gap:6px;font-weight:500;">
                                                            <i class="fa-solid fa-temperature-half text-[10px]"></i>Suhu
                                                        </span>
                                                        <span style="display:inline-flex;align-items:center;gap:5px;">
                                                            <?php if($room->temperature_is_offline): ?>
                                                                <i class="fa-solid fa-wifi-slash temp-offline-icon" style="font-size:11px;color:var(--coral);"></i>
                                                            <?php endif; ?>
                                                            <span id="temp-<?php echo e($room->id); ?>" class="text-mono" data-offline="<?php echo e($room->temperature_is_offline ? 'true' : 'false'); ?>">
                                                                <?php echo e($temp ?? '–'); ?>°C
                                                            </span>
                                                        </span>
                                                    </div>

                                                    <div class="ac-mini">
                                                        <div>
                                                            <p class="num" style="color:var(--mint);"
                                                                id="ov-active-<?php echo e($room->id); ?>">
                                                                <?php echo e($activeCount); ?></p>
                                                            <p class="lbl">Active</p>
                                                        </div>
                                                        <div>
                                                            <p class="num" style="color:var(--ink-2);"
                                                                id="ov-idle-<?php echo e($room->id); ?>">
                                                                <?php echo e($inactiveCount); ?></p>
                                                            <p class="lbl">Idle</p>
                                                        </div>
                                                    </div>

                                                    <div class="flex gap-1.5 mt-auto pt-1">
                                                        <a href="/rooms/<?php echo e($room->id); ?>/status"
                                                            class="btn btn-primary btn-sm flex-1">
                                                            Detail
                                                        </a>
                                                        <button type="button"
                                                            onclick="openHistory(<?php echo e($room->id); ?>, '<?php echo e(ucfirst($room->name)); ?>')"
                                                            class="btn-icon lavender" title="Histori suhu 24 jam">
                                                            <i class="fa-solid fa-chart-line text-[10px]"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>

                            <div id="emptyState" class="empty-state" hidden>
                                <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                                <p class="empty-title">Tidak ditemukan</p>
                                <p class="empty-sub">Coba kata kunci atau filter lain</p>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-server"></i></div>
                                <p class="empty-title">Belum ada ruangan</p>
                                <p class="empty-sub">Hubungi administrator untuk menambahkan ruangan</p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div id="historyModal" class="modal-backdrop">
        <div class="modal modal-lg">
            <div class="modal-header">
                <div>
                    <p class="eyebrow" style="color:var(--lavender);"><i class="fa-solid fa-chart-line"></i> Histori
                        Suhu</p>
                    <h2 id="historyTitle">Ruangan</h2>
                    <p class="sub">24 jam terakhir · rata-rata per jam</p>
                </div>
                <button type="button" class="modal-close" onclick="closeHistory()"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div id="historyLoading" class="empty-state" style="padding:36px 0;">
                    <div class="empty-icon"><i class="fa-solid fa-spinner fa-spin"></i></div>
                    <p class="empty-sub">Memuat data…</p>
                </div>
                <div id="historyEmpty" class="empty-state" style="padding:36px 0;" hidden>
                    <div class="empty-icon"><i class="fa-solid fa-temperature-empty"></i></div>
                    <p class="empty-sub">Tidak ada data suhu dalam 24 jam terakhir</p>
                </div>
                <div id="historyChartWrap" hidden style="height:280px;">
                    <canvas id="historyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <?php echo $__env->make('components.bottom-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

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

        function refreshRoomStatuses() {
            fetch('/device-status', {
                    headers: {
                        'Accept': 'application/json'
                    },
                    cache: 'no-store'
                })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (!Array.isArray(data)) return;

                    data.forEach(device => {
                        const card = document.querySelector(`.room-card[data-room-id="${device.room_id}"]`);
                        if (!card) return;

                        setRoomStatus(card, device.is_online === true || device.status === 'online');
                    });

                    applyFilter();
                })
                .catch(() => {});
        }

        setInterval(refreshRoomStatuses, 5000);
        document.addEventListener('DOMContentLoaded', refreshRoomStatuses);

        /* ===== HISTORY MODAL ===== */
        let historyChartInstance = null;

        function openHistory(roomId, roomName) {
            document.getElementById('historyTitle').textContent = roomName;
            document.getElementById('historyModal').classList.add('is-open');
            document.getElementById('historyLoading').hidden = false;
            document.getElementById('historyEmpty').hidden = true;
            document.getElementById('historyChartWrap').hidden = true;

            if (historyChartInstance) {
                historyChartInstance.destroy();
                historyChartInstance = null;
            }

            fetch(`/temperature/history/${roomId}`)
                .then(r => r.ok ? r.json() : [])
                .then(data => {
                    document.getElementById('historyLoading').hidden = true;
                    if (!data || data.length === 0) {
                        document.getElementById('historyEmpty').hidden = false;
                        return;
                    }
                    document.getElementById('historyChartWrap').hidden = false;
                    const labels = data.map(d => d.time);
                    const temps = data.map(d => d.temp);
                    const pointColor = t => t > 30 ? '#fb7185' : t > 25 ? '#fbbf24' : '#4dd4ff';
                    const ctx = document.getElementById('historyChart').getContext('2d');
                    historyChartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Suhu (°C)',
                                data: temps,
                                borderColor: '#4dd4ff',
                                backgroundColor: 'rgba(77,212,255,0.10)',
                                pointBackgroundColor: temps.map(pointColor),
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(7,16,31,0.96)',
                                    titleColor: '#f5f7fb',
                                    bodyColor: '#cbd5e1',
                                    borderColor: 'rgba(77,212,255,0.40)',
                                    borderWidth: 1,
                                    padding: 10,
                                    cornerRadius: 10,
                                    displayColors: false,
                                    callbacks: {
                                        label: c => ` ${c.parsed.y}°C`
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        color: '#64748b',
                                        font: {
                                            size: 10
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(255,255,255,0.04)'
                                    }
                                },
                                y: {
                                    suggestedMin: 18,
                                    suggestedMax: 35,
                                    ticks: {
                                        color: '#64748b',
                                        font: {
                                            size: 10
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
                if (!data) return;
                data.forEach(updateRoomTemperature);
            }).catch(() => {});
        }
        setInterval(refreshTemps, 5000);

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

            // Real-time: counter Active/Idle per kartu tanpa reload
            function refreshAcCountersOverview() {
                fetch('/api/ac-status', { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
                    .then(r => r.ok ? r.json() : null)
                    .then(data => {
                        if (!Array.isArray(data)) return;
                        const counts = {};
                        data.forEach(item => {
                            const roomId = item.ac_unit?.room?.id ?? item.acUnit?.room?.id;
                            if (!roomId) return;
                            if (!counts[roomId]) counts[roomId] = { active: 0, idle: 0 };
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
    <?php echo $__env->make('components.sidebar-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>

</html>
<?php /**PATH C:\laragon\www\tugasakhirremotac\resources\views/rooms/overview.blade.php ENDPATH**/ ?>