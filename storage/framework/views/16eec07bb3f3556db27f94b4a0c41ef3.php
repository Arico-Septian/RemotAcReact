<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Manajemen Ruangan — SmartAC</title>
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php echo app('Illuminate\Foundation\Vite')('resources/js/app.js'); ?>
    <?php echo $__env->make('components.sidebar-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <style>
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

        /* Keputusan text color follows action */
        .keputusan-yellow { color: #facc15 !important; } /* TURUNKAN */
        .keputusan-cool   { color: #5ed0ff !important; } /* NAIKKAN */
        .keputusan-warm   { color: #6ee7b7 !important; } /* DIAM (stabil) */
        .keputusan-hot    { color: #fb923c !important; }
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

        /* Responsive search and filters */
        @media (max-width: 768px) {
            .flex.items-center.gap-2 {
                gap: 6px;
            }

            .flex.items-center.gap-2 > form {
                flex: 1;
                min-width: 0;
                transition: flex 0.2s ease;
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

        /* ===== Auto-fit room grid (overrides Tailwind grid-cols on .floor-section) ===== */
        .floor-section > .grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(210px, 260px)) !important;
            gap: 12px !important;
            justify-content: start;
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

        /* Mobile S (≤ 360 px): keep "Add Room" label visible, match 36 px unified height */
        @media (max-width: 360px) {
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
        @media (max-width: 360px) {
            .main-header { gap: 6px; padding-left: 10px; padding-right: 10px; }
            .main-header .app-header-title h1 { font-size: 13px; line-height: 1.15; }
            .main-header .app-header-title p { font-size: 9.5px; line-height: 1.2; }
            .main-header > .flex.items-center.gap-3 { gap: 6px; min-width: 0; flex: 1; }
            .main-header > .flex.items-center.gap-2 { gap: 4px; flex-shrink: 0; }
            /* Shrink the Online pill to just the dot on tiny screens */
            .main-header > .flex.items-center.gap-2 #systemStatus span:not(.dot) { display: none; }
            .main-header > .flex.items-center.gap-2 #systemStatus { padding: 4px 6px; }
            .main-header > .flex.items-center.gap-2 .btn-icon { width: 32px; height: 32px; }
        }

        /* Phones (≤ 600 px): tighter card, stretch with 1fr so 2-col fills row */
        @media (max-width: 600px) {
            .floor-section > .grid {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)) !important;
                gap: 10px !important;
            }

            .room-card { padding: 11px; gap: 8px; }
            .room-card h2 { font-size: 13px; }
            .room-card .room-status-pill { padding: 2px 7px !important; font-size: 9px !important; }
            .room-card .temp-chip { padding: 6px 10px !important; font-size: 10px !important; }
            .room-card .label-tag { font-size: 8.5px !important; margin-top: 2px !important; }
        }

        /* Tiny phones (≤ 360 px): 1-col so card stays legible */
        @media (max-width: 360px) {
            .floor-section > .grid {
                grid-template-columns: 1fr !important;
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
                        <h1>Rooms &amp; AC Units</h1>
                        <p>Manage server rooms</p>
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

                        <div class="flex items-center gap-2">
                            <form method="GET" action="<?php echo e(route('rooms.index')); ?>" class="flex-1 min-w-0">
                                <label class="search-input">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input name="search" value="<?php echo e(request('search')); ?>" type="text"
                                        placeholder="Cari ruangan…" autocomplete="off">
                                    <?php if(request('search')): ?>
                                        <a href="<?php echo e(route('rooms.index')); ?>" class="clear" title="Clear"><i
                                                class="fa-solid fa-xmark text-[10px]"></i></a>
                                    <?php endif; ?>
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
                                <?php if(auth()->guard()->check()): ?>
                                    <?php if(in_array(Auth::user()->role, ['admin', 'operator'])): ?>
                                        <button onclick="openModal()" class="btn btn-primary btn-sm" type="button">
                                            <i class="fa-solid fa-plus text-[10px]"></i>
                                            <span>Add Room</span>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p id="roomCount" class="text-mono text-xs" style="color:var(--ink-3);"></p>

                        <?php if($rooms->count() > 0): ?>
                            <div class="space-y-2">
                                <?php $__currentLoopData = $roomsByFloor; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $floorName => $floorRooms): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <section class="floor-section">
                                        <div class="floor-section-header">
                                            <i class="fa-solid fa-layer-group text-[10px]"
                                                style="color:var(--lavender);"></i>
                                            <span class="floor-label"><?php echo e(ucfirst($floorName)); ?></span>
                                            <div class="floor-divider"></div>
                                            <span class="floor-count"><?php echo e($floorRooms->count()); ?> ruangan</span>
                                        </div>

                                        <div
                                            class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 mb-6">
                                            <?php $__currentLoopData = $floorRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $online = ($room->device_status ?? 'offline') === 'online';
                                                    $temp = $room->temperature ?? null;
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
                                                ?>
                                                <div class="room-card" data-room-id="<?php echo e($room->id); ?>"
                                                    data-room-name="<?php echo e($room->name); ?>"
                                                    data-device-id="<?php echo e($room->device_id); ?>"
                                                    data-status="<?php echo e($online ? 'online' : 'offline'); ?>">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <h2 class="font-semibold text-tight truncate"
                                                            style="color:var(--ink-0);font-size:16px;line-height:1.25;"><?php echo e(ucfirst($room->name)); ?></h2>
                                                        <span class="pill room-status-pill <?php echo e($online ? 'pill-online' : 'pill-offline'); ?>"
                                                            style="padding:3px 8px;font-size:10px;flex-shrink:0;">
                                                            <span class="dot"></span><span class="room-status-text"><?php echo e($online ? 'Online' : 'Offline'); ?></span>
                                                        </span>
                                                    </div>

                                                    
                                                    <div class="temp-chip <?php echo e($room->temperature_is_offline ? 'idle' : $tcls); ?>"
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

                                                    
                                                    <?php if($room->temperature !== null): ?>
                                                        <div class="mt-2"
                                                            style="background:var(--panel-1);border:1px solid var(--line-soft);border-radius:var(--r-md);padding:8px 10px;">
                                                            <div class="flex items-center justify-between"
                                                                style="font-size:12px;color:var(--ink-3);">
                                                                <span>ΔT</span>
                                                                <span
                                                                    class="text-mono"><?php echo e($room->delta_t ?? 0); ?></span>
                                                            </div>

                                                            <?php if(!empty($room->fuzzy)): ?>
                                                                <div class="flex items-center justify-between mt-1"
                                                                    style="font-size:11px;">
                                                                    <span style="color:var(--ink-3);flex-shrink:0;">Pendinginan</span>
                                                                    <span class="text-mono"
                                                                        style="font-weight:700;color:var(--mint);white-space:nowrap;margin-left:6px;">
                                                                        <?php echo e($room->fuzzy['status_pendinginan'] ?? '-'); ?>

                                                                    </span>
                                                                </div>

                                                                
                                                                <?php if(!empty($room->decision)): ?>
                                                                    <?php
                                                                        $action = strtoupper($room->decision['action'] ?? 'DIAM');
                                                                        $keputusanClass = match($action) {
                                                                            'TURUNKAN' => 'keputusan-yellow',
                                                                            'NAIKKAN'  => 'keputusan-cool',
                                                                            'DIAM'     => 'keputusan-warm',
                                                                            default    => 'keputusan-idle',
                                                                        };
                                                                        $spBefore = is_array($room->decision) ? ($room->decision['setpoint_before'] ?? '-') : '-';
                                                                        $spAfter  = is_array($room->decision) ? ($room->decision['setpoint_after']  ?? '-') : '-';
                                                                    ?>
                                                                    <div style="font-size:11px;color:var(--ink-3);margin-top:4px;">
                                                                        <div class="flex items-center justify-between">
                                                                            <span style="flex-shrink:0;">Keputusan</span>
                                                                            <span class="text-mono <?php echo e($keputusanClass); ?>"
                                                                                style="font-weight:700;white-space:nowrap;margin-left:6px;"><?php echo e($action); ?></span>
                                                                        </div>
                                                                        <div class="flex items-center justify-between" style="margin-top:2px;color:var(--ink-4);">
                                                                            <span style="flex-shrink:0;">Setpoint</span>
                                                                            <span class="text-mono" style="white-space:nowrap;margin-left:6px;"><?php echo e($spBefore); ?> &rarr; <?php echo e($spAfter); ?></span>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="grid grid-cols-2 gap-1.5">
                                                        <div
                                                            style="background:var(--panel-1);border:1px solid var(--line-soft);border-radius:var(--r-md);padding:6px 8px;text-align:center;">
                                                            <p class="text-mono text-base font-bold"
                                                                style="color:var(--mint);line-height:1;"
                                                                id="active-<?php echo e($room->id); ?>">
                                                                <?php echo e($activeAcs); ?></p>
                                                            <p class="label-tag mt-1" style="font-size:9.5px;">Active
                                                            </p>
                                                        </div>
                                                        <div
                                                            style="background:var(--panel-1);border:1px solid var(--line-soft);border-radius:var(--r-md);padding:6px 8px;text-align:center;">
                                                            <p class="text-mono text-base font-bold"
                                                                style="color:var(--ink-2);line-height:1;"
                                                                id="idle-<?php echo e($room->id); ?>">
                                                                <?php echo e($idleAcs); ?></p>
                                                            <p class="label-tag mt-1" style="font-size:9.5px;">Idle
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <p class="text-xs text-center"
                                                        style="color:var(--ink-4);margin-top:-2px;">
                                                        <?php echo e($room->acUnits->count()); ?> unit total</p>

                                                    <div class="flex flex-col gap-1.5 mt-auto">
                                                        <a href="/rooms/<?php echo e($room->id); ?>/ac"
                                                            class="btn btn-primary btn-sm">
                                                            <i class="fa-solid fa-sliders text-[10px]"></i>Control AC
                                                        </a>
                                                        <?php if(auth()->guard()->check()): ?>
                                                            <?php if(in_array(Auth::user()->role, ['admin', 'operator'])): ?>
                                                                <form action="/rooms/<?php echo e($room->id); ?>" method="POST"
                                                                    onsubmit="return confirmDelete(event)">
                                                                    <?php echo csrf_field(); ?>
                                                                    <?php echo method_field('DELETE'); ?>
                                                                    <button type="submit"
                                                                        class="btn btn-danger btn-sm btn-block">
                                                                        <i class="fa-solid fa-trash text-[10px]"></i>Hapus
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </section>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            <div id="roomFilterEmpty" class="empty-state" hidden>
                                <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                                <p class="empty-title">Tidak ditemukan</p>
                                <p class="empty-sub">Coba filter status lain</p>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-server"></i></div>
                                <p class="empty-title">Belum ada ruangan</p>
                                <p class="empty-sub">Tambahkan ruangan untuk memulai</p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php echo $__env->make('components.bottom-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php if(auth()->guard()->check()): ?>
        <?php if(in_array(Auth::user()->role, ['admin', 'operator'])): ?>
            <div id="modal" class="modal-backdrop">
                <div class="modal">
                    <div class="modal-header">
                        <div>
                            <p class="eyebrow"><i class="fa-solid fa-plus"></i> New</p>
                            <h2>Tambah Ruangan</h2>
                            <p class="sub">Daftarkan ruangan baru beserta ESP device-nya</p>
                        </div>
                        <button type="button" class="modal-close" onclick="closeModal()"><i
                                class="fa-solid fa-xmark"></i></button>
                    </div>
                    <form id="addRoomForm" method="POST" action="/rooms">
                        <?php echo csrf_field(); ?>
                        <div class="modal-body space-y-3">
                            <div class="field">
                                <label class="field-label">Nama Ruangan</label>
                                <input class="input text-mono" type="text" name="name" placeholder="server_1"
                                    pattern="[A-Za-z0-9_]+"
                                    title="Nama ruangan tidak boleh mengandung spasi"
                                    required>
                                <p class="field-help">Huruf, angka, dan underscore (tidak boleh ada spasi)</p>
                            </div>
                            <div class="field">
                                <label class="field-label">ESP Device ID</label>
                                <input class="input text-mono" type="text" name="device_id" placeholder="esp32_01"
                                    pattern="[A-Za-z0-9_-]+"
                                    title="ESP Device ID tidak boleh mengandung spasi"
                                    required>
                                <p class="field-help">Huruf, angka, underscore, dan strip (tidak boleh ada spasi)</p>
                            </div>
                            <div class="field">
                                <label class="field-label">Lantai / Zona <span
                                        style="color:var(--ink-4);font-weight:400;">(opsional)</span></label>
                                <input class="input text-mono" type="text" name="floor" placeholder="lantai_1"
                                    pattern="[A-Za-z0-9_]*"
                                    title="Lantai atau zona tidak boleh mengandung spasi">
                                <p class="field-help">Huruf, angka, dan underscore (tidak boleh ada spasi)</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-ghost" onclick="closeModal()">Batal</button>
                            <button type="submit" class="btn btn-primary">Buat Ruangan</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

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
            if (confirm('Hapus ruangan ini beserta semua AC unit di dalamnya?')) e.target.submit();
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
                input.setCustomValidity(`${label} tidak boleh mengandung spasi`);
                setFieldFeedback(input, `${label} tidak boleh mengandung spasi`, true);
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

            if (!validateNoSpaces(nameInput, 'Nama ruangan')) {
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
                blockDuplicateInput(nameInput, 'Nama ruangan sudah ada');
                return;
            }

            if (deviceIds.has(deviceInput.value)) {
                e.preventDefault();
                blockDuplicateInput(deviceInput, 'ESP Device ID sudah terdaftar');
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

        setInterval(() => {
            fetch('/temperature', {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (!Array.isArray(data)) return;
                    data.forEach(updateRoomTemperature);
                }).catch(() => {});
        }, 5000);

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

                    applyRoomFilter();
                })
                .catch(() => {});
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
                    .listen('.DeviceStatusUpdated', () => refreshRoomStatuses())
                    .listen('.RoomTemperatureUpdated', () => {
                        fetch('/temperature', { headers: { 'Accept': 'application/json' } })
                            .then(r => r.ok ? r.json() : null)
                            .then(data => {
                                if (!Array.isArray(data)) return;
                                data.forEach(updateRoomTemperature);
                            }).catch(() => {});
                    })
                    .listen('.AcStatusUpdated', () => refreshAcCounters());
            }

            <?php if(session('success')): ?>
                window.smToast("<?php echo e(session('success')); ?>", 'success');
            <?php endif; ?>
            <?php if(session('error')): ?>
                window.smToast("<?php echo e(session('error')); ?>", 'error');
            <?php endif; ?>
            <?php if($errors->any()): ?>
                window.smToast("<?php echo e($errors->first()); ?>", 'error');
            <?php endif; ?>
        });

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
        });
    </script>
    <?php echo $__env->make('components.sidebar-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>

</html>
<?php /**PATH C:\laragon\www\tugasakhirremotac\resources\views/rooms/index.blade.php ENDPATH**/ ?>