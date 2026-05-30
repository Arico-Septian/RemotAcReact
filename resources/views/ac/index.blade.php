<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $room->name }} — AC Control</title>
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite('resources/js/app.js')
    @include('components.sidebar-styles')
    <style>
        .ac-panel {
            transition: opacity .2s, transform .2s;
        }

        .ac-panel.hidden {
            display: none;
        }

        .selector-bar {
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
            border-radius: var(--r-xl);
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: var(--inset-hi);
        }

        .selector {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            background: var(--panel-2);
            border: 1px solid var(--line);
            border-radius: var(--r-md);
            cursor: pointer;
            color: var(--ink-0);
            font-size: 13px;
            font-weight: 600;
            transition: var(--t-base);
            user-select: none;
        }

        .selector:hover {
            background: var(--panel-3);
        }

        .selector i {
            color: var(--ink-3);
            font-size: 10px;
        }

        #dropdownAC {
            position: absolute;
            top: 44px;
            left: 0;
            min-width: 220px;
            background: var(--bg-2);
            border: 1px solid var(--line);
            border-radius: var(--r-md);
            box-shadow: var(--shadow-lg);
            opacity: 0;
            visibility: hidden;
            transform: translateY(6px);
            transition: var(--t-base);
            z-index: 40;
            overflow: hidden;
        }

        #dropdownAC.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        #dropdownAC>div {
            padding: 10px 14px;
            font-size: 13px;
            color: var(--ink-1);
            cursor: pointer;
            transition: var(--t-fast);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #dropdownAC>div:hover {
            background: var(--cyan-soft);
            color: var(--cyan);
        }

        #dropdownAC>div .num {
            color: var(--ink-3);
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
        }

        .stat-mini {
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
            border-radius: var(--r-md);
            padding: 9px 8px;
            text-align: center;
        }

        .stat-mini .lbl {
            font-size: 10px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--ink-3);
            font-weight: 700;
        }

        .stat-mini .val {
            font-size: 13px;
            font-weight: 700;
            margin-top: 4px;
            font-family: 'JetBrains Mono', monospace;
            color: var(--ink-0);
        }

        /* === Temperature Ring === */
        .temp-ring {
            width: 240px;
            height: 240px;
            border-radius: 50%;
            padding: 3px;
            background: conic-gradient(from 215deg,
                    transparent 0deg,
                    rgba(77, 212, 255, 0.85) 60deg,
                    rgba(180, 163, 255, 0.85) 130deg,
                    rgba(180, 163, 255, 0.25) 175deg,
                    transparent 200deg);
            position: relative;
            box-shadow: 0 20px 60px rgba(77, 212, 255, 0.10);
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }

        /* #10 Temperature color indicator */
        .temp-ring.temp-cool {
            background: conic-gradient(from 215deg,
                    transparent 0deg,
                    rgba(77, 212, 255, 0.85) 60deg,
                    rgba(180, 163, 255, 0.85) 130deg,
                    rgba(180, 163, 255, 0.25) 175deg,
                    transparent 200deg);
            box-shadow: 0 20px 60px rgba(77, 212, 255, 0.10);
        }

        .temp-ring.temp-warm {
            background: conic-gradient(from 215deg,
                    transparent 0deg,
                    rgba(250, 204, 21, 0.85) 60deg,
                    rgba(251, 146, 60, 0.85) 130deg,
                    rgba(251, 146, 60, 0.25) 175deg,
                    transparent 200deg);
            box-shadow: 0 20px 60px rgba(250, 204, 21, 0.10);
        }

        .temp-ring.temp-hot {
            background: conic-gradient(from 215deg,
                    transparent 0deg,
                    rgba(248, 113, 113, 0.85) 60deg,
                    rgba(244, 63, 94, 0.85) 130deg,
                    rgba(244, 63, 94, 0.25) 175deg,
                    transparent 200deg);
            box-shadow: 0 20px 60px rgba(248, 113, 113, 0.10);
        }

        /* #1 Responsive temperature ring */
        @media (max-width: 768px) {
            .temp-ring {
                width: 160px;
                height: 160px;
                box-shadow: 0 12px 40px rgba(77, 212, 255, 0.08);
            }

            .ring-temp {
                font-size: 48px;
            }

            .ring-temp .unit {
                font-size: 14px;
                margin-top: 4px;
            }

            .ring-label {
                font-size: 10px;
            }

            .ring-summary {
                font-size: 10px;
            }
        }

        .temp-ring-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background:
                radial-gradient(circle at 50% 45%, rgba(18, 32, 66, 0.95), rgba(7, 16, 31, 0.98) 70%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
        }

        .ring-label {
            font-size: 10px;
            letter-spacing: 0.20em;
            text-transform: uppercase;
            color: var(--ink-3);
            font-weight: 700;
            margin: 0;
        }

        .ring-temp {
            font-family: 'Inter', sans-serif;
            font-size: 64px;
            font-weight: 700;
            color: var(--ink-0);
            letter-spacing: -0.04em;
            line-height: 1;
            display: inline-flex;
            align-items: flex-start;
        }

        .ring-temp .unit {
            font-size: 18px;
            color: var(--ink-2);
            margin-left: 2px;
            margin-top: 6px;
            font-weight: 600;
        }

        .ring-summary {
            font-size: 12px;
            color: var(--ink-3);
            margin: 2px 0 0;
            letter-spacing: 0.02em;
        }

        /* === Control Row (− power +) === */
        .ctrl-row {
            display: inline-flex;
            align-items: center;
            gap: 22px;
        }

        .ctrl-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            font-size: 18px;
            font-weight: 500;
            color: var(--ink-1);
            background: var(--panel-1);
            border: 1px solid var(--line);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: var(--t-base);
        }

        .ctrl-btn:hover:not(:disabled) {
            background: var(--panel-3);
            border-color: var(--line-strong);
            color: var(--ink-0);
        }

        .ctrl-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .power-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: var(--panel-2);
            border: 1px solid var(--line);
            color: var(--ink-3);
            font-size: 20px;
            transition: var(--t-base);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.02);
        }

        .power-btn:hover {
            transform: scale(1.04);
        }

        .power-btn.on {
            background: radial-gradient(circle at center, var(--mint), var(--mint-d));
            color: var(--bg-0);
            border-color: transparent;
            box-shadow:
                0 0 0 4px rgba(110, 231, 183, 0.18),
                0 0 30px rgba(110, 231, 183, 0.45);
        }

        /* === Min/Max chips === */
        .ring-chips {
            display: inline-flex;
            gap: 8px;
        }

        .ring-chip {
            font-size: 11px;
            color: var(--ink-3);
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
            padding: 5px 12px;
            border-radius: 999px;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 0.02em;
        }

        /* Slim power form wrapper to keep stepper inline */
        .power-form-inline {
            display: inline-flex;
        }

        /* === Mode buttons (2x2) — vertical stacked, larger === */
        .mode-btn-v {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 18px 10px;
            background: var(--panel-1);
            border: 1px solid var(--line);
            border-radius: var(--r-lg);
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-2);
            cursor: pointer;
            font-family: inherit;
            transition: var(--t-base);
            width: 100%;
            min-height: 86px;
        }

        .mode-btn-v .icon-wrap {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: var(--panel-2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: var(--ink-3);
            transition: var(--t-base);
        }

        .mode-btn-v:hover {
            background: var(--panel-2);
            border-color: var(--line-strong);
            color: var(--ink-0);
        }

        .mode-btn-v:hover .icon-wrap {
            background: var(--panel-3);
            color: var(--ink-1);
        }

        .mode-btn-v.active {
            background: linear-gradient(180deg, rgba(77, 212, 255, 0.14), rgba(77, 212, 255, 0.04));
            border-color: var(--cyan);
            color: var(--cyan);
            box-shadow: 0 0 0 1px var(--cyan-soft) inset, 0 8px 22px rgba(77, 212, 255, 0.14);
        }

        .mode-btn-v.active .icon-wrap {
            background: rgba(77, 212, 255, 0.16);
            color: var(--cyan);
        }

        .mode-btn-v[data-mode="heat"].active {
            color: var(--coral);
            border-color: var(--coral);
            box-shadow: 0 0 0 1px rgba(248, 113, 113, 0.20) inset, 0 8px 22px rgba(248, 113, 113, 0.14);
            background: linear-gradient(180deg, rgba(248, 113, 113, 0.14), rgba(248, 113, 113, 0.04));
        }

        .mode-btn-v[data-mode="heat"].active .icon-wrap {
            background: rgba(248, 113, 113, 0.18);
            color: var(--coral);
        }

        .mode-btn-v[data-mode="dry"].active {
            color: var(--lavender);
            border-color: var(--lavender);
            box-shadow: 0 0 0 1px rgba(180, 163, 255, 0.20) inset, 0 8px 22px rgba(180, 163, 255, 0.14);
            background: linear-gradient(180deg, rgba(180, 163, 255, 0.14), rgba(180, 163, 255, 0.04));
        }

        .mode-btn-v[data-mode="dry"].active .icon-wrap {
            background: rgba(180, 163, 255, 0.18);
            color: var(--lavender);
        }

        .mode-btn-v[data-mode="fan"].active {
            color: var(--mint);
            border-color: var(--mint);
            box-shadow: 0 0 0 1px rgba(110, 231, 183, 0.20) inset, 0 8px 22px rgba(110, 231, 183, 0.14);
            background: linear-gradient(180deg, rgba(110, 231, 183, 0.14), rgba(110, 231, 183, 0.04));
        }

        .mode-btn-v[data-mode="fan"].active .icon-wrap {
            background: rgba(110, 231, 183, 0.18);
            color: var(--mint);
        }

        /* === Horizontal buttons (icon + label inline) for Fan/Swing === */
        .mode-btn-h {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 11px 10px;
            background: var(--panel-1);
            border: 1px solid var(--line);
            border-radius: var(--r-md);
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-2);
            cursor: pointer;
            font-family: inherit;
            transition: var(--t-base);
            width: 100%;
            white-space: nowrap;
        }

        .mode-btn-h i {
            font-size: 12px;
            color: var(--ink-3);
            transition: var(--t-base);
        }

        .mode-btn-h:hover {
            background: var(--panel-2);
            border-color: var(--line-strong);
            color: var(--ink-0);
        }

        .mode-btn-h:hover i {
            color: var(--ink-1);
        }

        .mode-btn-h.active {
            background: linear-gradient(180deg, rgba(77, 212, 255, 0.14), rgba(77, 212, 255, 0.04));
            border-color: var(--cyan);
            color: var(--cyan);
            box-shadow: 0 0 0 1px var(--cyan-soft) inset, 0 6px 18px rgba(77, 212, 255, 0.14);
        }

        .mode-btn-h.active i {
            color: var(--cyan);
        }

        /* === Timer panel === */
        .timer-state {
            display: flex;
            gap: 12px;
            margin-top: 4px;
        }

        .timer-card {
            flex: 1;
            min-width: 0;
            padding: 12px 14px;
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
            border-radius: var(--r-lg);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .timer-card .t-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            background: var(--panel-2);
            color: var(--ink-3);
            flex-shrink: 0;
        }

        .timer-card.is-on .t-icon {
            background: rgba(110, 231, 183, 0.16);
            color: var(--mint);
        }

        .timer-card.is-off .t-icon {
            background: rgba(248, 113, 113, 0.16);
            color: var(--coral);
        }

        .timer-card .t-meta {
            min-width: 0;
        }

        .timer-card .t-label {
            font-size: 10px;
            letter-spacing: 0.10em;
            text-transform: uppercase;
            color: var(--ink-3);
            font-weight: 700;
            margin: 0;
        }

        .timer-card .t-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 16px;
            font-weight: 700;
            color: var(--ink-0);
            margin: 2px 0 0;
            letter-spacing: -0.01em;
        }

        .timer-card .t-value.empty {
            color: var(--ink-4);
            font-weight: 500;
            font-size: 13px;
        }

        .timer-empty {
            text-align: center;
            padding: 18px 12px;
            background: var(--panel-1);
            border: 1px dashed var(--line);
            border-radius: var(--r-lg);
            color: var(--ink-3);
            font-size: 13px;
        }

        .timer-empty i {
            color: var(--ink-4);
            margin-bottom: 6px;
            display: block;
            font-size: 18px;
        }

        .ac-ctrl-busy {
            opacity: 0.50 !important;
            pointer-events: none !important;
            cursor: wait !important;
        }

        .ac-ctrl-busy i.fa-spinner {
            display: inline-block;
        }

        /* #2 Toolbar responsiveness for small screens */
        @media (max-width: 768px) {
            .selector-bar {
                flex-wrap: nowrap;
                gap: 8px;
                padding: 8px;
            }

            .selector-bar>div:first-child {
                flex: 1;
                min-width: 160px;
                max-width: 400px;
                order: 1;
            }

            .selector-bar>div:last-child {
                order: 2;
                display: flex;
                flex-wrap: nowrap;
                gap: 6px;
                flex-shrink: 0;
            }

            .btn.btn-sm {
                padding: 7px 10px;
                font-size: 11px;
                white-space: nowrap;
            }

            .btn-icon {
                width: 36px;
                height: 36px;
            }
        }

        /* #2b Very small screens (< 480px) */
        @media (max-width: 480px) {
            .selector-bar {
                padding: 6px;
                gap: 6px;
            }

            .selector-bar>div:first-child {
                flex: 1 1 auto;
                min-width: 0;
            }

            .selector {
                width: 100%;
            }

            .selector {
                padding: 6px 10px;
                font-size: 11px;
            }

            .selector i {
                font-size: 10px;
            }

            .selector-bar>div:last-child {
                display: inline-flex;
                gap: 6px;
                flex-shrink: 0;
            }

            .selector {
                min-height: 40px;
            }

            .selector-bar .btn.btn-sm {
                padding: 0 !important;
                width: 40px !important;
                height: 40px !important;
                min-height: 40px !important;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
            }

            .selector-bar .btn.btn-sm span {
                display: none;
            }

            .selector-bar .btn.btn-sm i {
                margin: 0 !important;
                font-size: 14px !important;
            }

            .selector-bar .btn-icon {
                width: 40px !important;
                height: 40px !important;
                font-size: 14px;
            }
        }

        /* #2c Tablet medium screens (600px - 768px) */
        @media (min-width: 600px) and (max-width: 768px) {
            .selector-bar {
                padding: 9px;
                gap: 10px;
            }

            .selector-bar>div:first-child {
                flex: 1;
            }

            .selector {
                padding: 10px 14px;
                font-size: 13px;
                min-height: 42px;
            }

            .selector-bar .btn.btn-sm {
                padding: 9px 14px;
                font-size: 13px;
                min-height: 42px;
            }

            .selector-bar .btn.btn-sm i {
                font-size: 13px;
            }

            .selector-bar .btn.btn-sm span {
                display: inline;
            }

            .selector-bar .btn-icon {
                width: 42px;
                height: 42px;
                font-size: 14px;
            }
        }

        /* Laptop / desktop (≥ 769px): comfortable button sizes in selector bar */
        @media (min-width: 769px) {
            .selector-bar .btn.btn-sm {
                padding: 10px 16px;
                font-size: 13px;
                min-height: 42px;
            }

            .selector-bar .btn.btn-sm i {
                font-size: 13px;
            }

            .selector-bar .btn.btn-sm span {
                display: inline !important;
            }

            .selector-bar .btn-icon {
                width: 42px;
                height: 42px;
                font-size: 14px;
            }

            .selector {
                min-height: 42px;
                padding: 9px 14px;
            }
        }

        /* #3 Mobile layout optimization */
        @media (max-width: 768px) {
            .grid[class*="md:grid-cols"] {
                grid-template-columns: 1fr !important;
            }

            .panel {
                padding: 16px 12px;
            }
        }

        /* #4 Touch targets minimum 44x44px */
        @media (max-width: 768px) {
            .ctrl-btn {
                width: 48px;
                height: 48px;
                font-size: 16px;
            }

            .power-btn {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }

            .mode-btn-h,
            .mode-btn-v {
                min-height: 44px;
                padding: 12px 8px;
            }

            .btn-icon {
                width: 40px;
                height: 40px;
            }

            .selector {
                padding: 9px 12px;
                min-height: 40px;
            }
        }

        /* Always-on fix: Tailwind didn't ship .grid-cols-4 in compiled CSS, so supply it ourselves */
        .panel>.grid.grid-cols-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        /* Selector text: truncate instead of wrap (prevents "Panasonic" jumping to a new line) */
        .selector {
            max-width: 100%;
        }

        .selector>#selectedAC {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            flex: 1;
        }

        /* Mobile (≤ 480 px): 2-col mode/fan/swing — 4 buttons cramped at this width */
        @media (max-width: 480px) {
            .panel>.grid.grid-cols-4 {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 7px !important;
            }

            .mode-btn-h {
                padding: 10px 8px !important;
                font-size: 12px !important;
                min-height: 40px !important;
                gap: 5px;
            }

            .mode-btn-h i {
                font-size: 11px !important;
            }
        }

        /* Mobile (≤ 480px): tighter selector bar + header */
        @media (max-width: 480px) {
            .selector-bar {
                padding: 6px !important;
                gap: 6px !important;
            }

            .selector {
                padding: 8px 10px !important;
                font-size: 11px !important;
                min-height: 38px;
            }

            .selector-bar>div:first-child {
                flex: 1 1 auto !important;
                min-width: 0 !important;
            }

            .selector-bar .btn.btn-sm {
                padding: 0 !important;
                width: 38px !important;
                height: 38px !important;
                min-height: 38px;
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
            }

            .selector-bar .btn.btn-sm i {
                font-size: 13px !important;
                margin: 0 !important;
            }

            .selector-bar .btn-icon {
                width: 38px !important;
                height: 38px !important;
                font-size: 13px;
            }

            .main-header {
                gap: 6px;
                padding-left: 10px;
                padding-right: 10px;
            }

            .main-header .app-header-title h1 {
                font-size: 13px;
                line-height: 1.2;
            }

            .main-header .app-header-title p {
                font-size: 10px;
                line-height: 1.2;
            }

            .main-header>.flex.items-center.gap-2 #espStatusPill span:not(.dot) {
                display: none;
            }

            .main-header>.flex.items-center.gap-2 #espStatusPill {
                padding: 4px 6px;
            }

            .main-header>.flex.items-center.gap-2 .btn-icon {
                width: 32px;
                height: 32px;
            }

            .temp-ring {
                width: 180px;
                height: 180px;
            }

            .temp-value {
                font-size: 56px !important;
            }

            .ctrl-btn {
                width: 42px !important;
                height: 42px !important;
            }

            .power-btn {
                width: 54px !important;
                height: 54px !important;
            }
        }

        /* Landscape mode optimization */
        @media (max-height: 600px) and (orientation: landscape) {
            .temp-ring {
                width: 140px;
                height: 140px;
            }

            .ring-temp {
                font-size: 40px;
            }

            .ctrl-row {
                gap: 18px;
            }

            .ring-chips {
                gap: 6px;
            }

            .ring-chip {
                font-size: 10px;
                padding: 4px 10px;
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
                        <h1>{{ $room->name }}</h1>
                        <p>AC control panel</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span id="espStatusPill" data-room-id="{{ $room->id }}"
                        class="pill {{ ($room->device_status ?? 'offline') === 'online' ? 'pill-online' : 'pill-error' }}">
                        <span class="dot"></span>
                        <span id="espStatusText">ESP
                            {{ ($room->device_status ?? 'offline') === 'online' ? 'Online' : 'Offline' }}</span>
                    </span>
                </div>
            </header>

            <div class="page-body">
                <div class="app-content">
                    <div class="app-content-inner space-y-3">
                        @php $firstAc = $acs->first(); @endphp

                        {{-- AC SELECTOR + ACTIONS --}}
                        <div class="selector-bar">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <div class="selector" onclick="toggleDropdown()">
                                    <i class="fa-solid fa-snowflake" style="color:var(--cyan);font-size:11px;"></i>
                                    <span id="selectedAC" style="text-transform:capitalize;">
                                        {{ $firstAc ? 'AC ' . $firstAc->ac_number . ' · ' . $firstAc->name . ($firstAc->brand ? ' · ' . $firstAc->brand : '') : 'No AC' }}
                                    </span>
                                    <i class="fa-solid fa-chevron-down"></i>

                                    <div id="dropdownAC">
                                        @foreach ($acs as $ac)
                                            @php
                                                $acLabel =
                                                    'AC ' .
                                                    $ac->ac_number .
                                                    ' · ' .
                                                    $ac->name .
                                                    ($ac->brand ? ' · ' . $ac->brand : '');
                                            @endphp
                                            <div data-id="{{ $ac->id }}"
                                                data-label="{{ $acLabel }}"
                                                onclick="selectAC({{ $ac->id }}, @js($acLabel))">
                                                <span class="num">#{{ $ac->ac_number }}</span>
                                                <span style="text-transform:capitalize;">{{ $ac->name }}
                                                    @if ($ac->brand)
                                                        <span style="opacity:.65;font-size:11px;">·
                                                            {{ $ac->brand }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <span class="kbd hidden sm:inline">{{ $room->name }}</span>
                            </div>

                            @auth
                                @if (in_array(Auth::user()->role, ['admin', 'operator']))
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" onclick="openEditModal()" class="btn-icon lavender"
                                            title="Edit Unit AC">
                                            <i class="fa-solid fa-pen text-[10px]"></i>
                                        </button>
                                        <form id="deleteForm" method="POST" onsubmit="return confirmDelete(event)"
                                            action="{{ $firstAc ? '/ac/' . $firstAc->id : '#' }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" {{ !$firstAc ? 'disabled' : '' }}
                                                class="btn-icon danger {{ !$firstAc ? 'disabled' : '' }}"
                                                title="Delete AC">
                                                <i class="fa-solid fa-trash text-[10px]"></i>
                                            </button>
                                        </form>
                                        <button type="button" {{ $acs->count() >= 15 ? 'disabled' : '' }}
                                            onclick="{{ $acs->count() >= 15 ? '' : 'openModal()' }}"
                                            class="btn btn-primary btn-sm {{ $acs->count() >= 15 ? 'disabled' : '' }}">
                                            <i class="fa-solid fa-plus text-[10px]"></i>
                                            <span class="hidden sm:inline">Add AC</span>
                                        </button>
                                    </div>
                                @endif
                            @endauth
                        </div>

                        {{-- AC PANELS --}}
                        @foreach ($acs as $ac)
                            <div id="ac-{{ $ac->id }}" class="ac-panel {{ $loop->first ? '' : 'hidden' }}"
                                data-ac-id="{{ $ac->id }}" data-ac-number="{{ $ac->ac_number }}"
                                data-ac-name="{{ $ac->name }}" data-ac-brand="{{ $ac->brand }}">
                                <div class="grid grid-cols-1 md:grid-cols-[300px_1fr] lg:grid-cols-[340px_1fr] gap-3">

                                    {{-- LEFT: Temp ring + Power + Stepper --}}
                                    @php
                                        $curTemp = $ac->status?->set_temperature ?? 24;
                                        $curMode = ucfirst(strtolower($ac->status?->mode ?? 'Cool'));
                                        $curFan = ucfirst(strtolower($ac->status?->fan_speed ?? 'Auto'));
                                        $curSwing = strtolower($ac->status?->swing ?? 'off');
                                        $swingLabel = match ($curSwing) {
                                            'off' => 'Still',
                                            'full' => 'Full',
                                            'half' => '½',
                                            'down' => 'Down',
                                            default => ucfirst($curSwing),
                                        };
                                        $isPowerOn = ($ac->status?->power ?? 'OFF') === 'ON';
                                    @endphp
                                    <div class="panel"
                                        style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:24px;padding:32px 20px;">
                                        @php
                                            $tempCategory = $curTemp <= 20 ? 'cool' : ($curTemp <= 25 ? 'warm' : 'hot');
                                        @endphp
                                        <div class="temp-ring temp-{{ $tempCategory }}"
                                            id="tempRing-{{ $ac->id }}">
                                            <div class="temp-ring-inner">
                                                <p class="ring-label">AC Temp</p>
                                                <div class="ring-temp">
                                                    <span class="temp-value">{{ $curTemp }}</span><span
                                                        class="unit">°C</span>
                                                </div>
                                                <p class="ring-summary" style="text-transform:capitalize;">
                                                    {{ $curMode }} · {{ $curFan }} ·
                                                    {{ $swingLabel }}</p>
                                            </div>
                                        </div>
                                        <div class="ctrl-row">
                                            <button type="button" class="ctrl-btn"
                                                onclick="setTemp({{ $ac->id }}, {{ $curTemp - 1 }})"
                                                title="Lower temperature" aria-label="Lower temperature">
                                                <i class="fa-solid fa-minus"></i>
                                            </button>
                                            <form action="/ac/{{ $ac->id }}/toggle" method="POST"
                                                class="power-form power-form-inline" aria-label="AC Power Control"
                                                data-ac-name="AC {{ $ac->ac_number }}{{ $ac->name ? ' · ' . $ac->name : '' }}"
                                                data-ac-power="{{ $ac->status?->power ?? 'OFF' }}">
                                                @csrf
                                                <input type="hidden" name="power"
                                                    value="{{ $isPowerOn ? 'OFF' : 'ON' }}">
                                                <button type="submit" class="power-btn {{ $isPowerOn ? 'on' : '' }}"
                                                    title="Toggle power">
                                                    <i class="fa-solid fa-power-off"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="ctrl-btn"
                                                onclick="setTemp({{ $ac->id }}, {{ $curTemp + 1 }})"
                                                title="Raise temperature" aria-label="Raise temperature">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </div>

                                        <div class="ring-chips">
                                            <span class="ring-chip">16°C min</span>
                                            <span class="ring-chip">30°C max</span>
                                        </div>
                                    </div>

                                    {{-- RIGHT --}}
                                    <div class="flex flex-col gap-3">

                                        {{-- Mode --}}
                                        <div class="panel">
                                            <p class="eyebrow" style="margin-bottom:12px;">Mode</p>
                                            <div class="grid grid-cols-4 gap-2">
                                                @foreach (['cool' => ['fa-snowflake', 'Cool'], 'heat' => ['fa-fire', 'Heat'], 'dry' => ['fa-droplet', 'Dry'], 'fan' => ['fa-fan', 'Fan']] as $m => [$icon, $lbl])
                                                    <form action="/ac/{{ $ac->id }}/mode/{{ $m }}"
                                                        method="POST" class="control-form">
                                                        @csrf
                                                        <button type="submit"
                                                            class="mode-btn-h {{ strtolower($ac->status?->mode ?? 'cool') === $m ? 'active' : '' }}">
                                                            <i
                                                                class="fa-solid {{ $icon }}"></i><span>{{ $lbl }}</span>
                                                        </button>
                                                    </form>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- Fan --}}
                                        <div class="panel">
                                            <p class="eyebrow" style="margin-bottom:12px;">Fan Speed</p>
                                            <div class="grid grid-cols-4 gap-2">
                                                @foreach (['auto' => ['fa-rotate', 'Auto'], 'low' => ['fa-equals', 'Low'], 'medium' => ['fa-bars', 'Med'], 'high' => ['fa-gauge-high', 'High']] as $s => [$icon, $lbl])
                                                    <form
                                                        action="/ac/{{ $ac->id }}/fan-speed/{{ $s }}"
                                                        method="POST" class="control-form">
                                                        @csrf
                                                        <button type="submit"
                                                            class="mode-btn-h {{ strtolower($ac->status?->fan_speed ?? 'auto') === $s ? 'active' : '' }}">
                                                            <i
                                                                class="fa-solid {{ $icon }}"></i><span>{{ $lbl }}</span>
                                                        </button>
                                                    </form>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- Swing --}}
                                        <div class="panel">
                                            <p class="eyebrow" style="margin-bottom:12px;">Swing</p>
                                            <div class="grid grid-cols-4 gap-2">
                                                @foreach (['off' => ['fa-ban', 'Still'], 'full' => ['fa-arrows-up-down', 'Full'], 'half' => ['fa-equals', '½'], 'down' => ['fa-arrow-down', 'Down']] as $sw => [$icon, $lbl])
                                                    <form action="/ac/{{ $ac->id }}/swing/{{ $sw }}"
                                                        method="POST" class="control-form">
                                                        @csrf
                                                        <button type="submit"
                                                            class="mode-btn-h {{ strtolower($ac->status?->swing ?? 'off') === $sw ? 'active' : '' }}">
                                                            <i
                                                                class="fa-solid {{ $icon }}"></i><span>{{ $lbl }}</span>
                                                        </button>
                                                    </form>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- Timer --}}
                                        <div class="panel">
                                            <div class="flex items-center justify-between mb-3">
                                                <p class="eyebrow" style="color:var(--amber);margin:0;"><i
                                                        class="fa-solid fa-clock"></i> Set Timer</p>
                                                <button id="btnTimer-{{ $ac->id }}" type="button"
                                                    onclick="toggleTimer({{ $ac->id }})"
                                                    class="btn btn-soft btn-xs">
                                                    <i class="fa-solid fa-pen text-[9px]"></i>
                                                    <span>Edit</span>
                                                </button>
                                            </div>
                                            <div id="timerView-{{ $ac->id }}">
                                                @if ($ac->timer_on || $ac->timer_off)
                                                    <div class="timer-state">
                                                        <div class="timer-card {{ $ac->timer_on ? 'is-on' : '' }}">
                                                            <span class="t-icon"><i
                                                                    class="fa-solid fa-circle-play"></i></span>
                                                            <div class="t-meta">
                                                                <p class="t-label">Turn On</p>
                                                                @if ($ac->timer_on)
                                                                    <p class="t-value">
                                                                        {{ \Carbon\Carbon::parse($ac->timer_on)->setTimezone('Asia/Jakarta')->format('H:i') }}
                                                                    </p>
                                                                @else
                                                                    <p class="t-value empty">—</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="timer-card {{ $ac->timer_off ? 'is-off' : '' }}">
                                                            <span class="t-icon"><i
                                                                    class="fa-solid fa-circle-stop"></i></span>
                                                            <div class="t-meta">
                                                                <p class="t-label">Turn Off</p>
                                                                @if ($ac->timer_off)
                                                                    <p class="t-value">
                                                                        {{ \Carbon\Carbon::parse($ac->timer_off)->setTimezone('Asia/Jakarta')->format('H:i') }}
                                                                    </p>
                                                                @else
                                                                    <p class="t-value empty">—</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <form action="/ac/{{ $ac->id }}/schedule" method="POST"
                                                        class="delete-timer-form mt-3">
                                                        @csrf
                                                        <input type="hidden" name="timer_on" value="">
                                                        <input type="hidden" name="timer_off" value="">
                                                        <button type="submit" class="btn btn-ghost btn-sm btn-block">
                                                            <i class="fa-solid fa-trash text-[10px]"></i>
                                                            <span>Delete Timer</span>
                                                        </button>
                                                    </form>
                                                @else
                                                    <div class="timer-empty">
                                                        <i class="fa-regular fa-clock"></i>
                                                        No timer set
                                                    </div>
                                                @endif
                                            </div>
                                            <form id="timerEdit-{{ $ac->id }}" class="hidden timer-form"
                                                action="/ac/{{ $ac->id }}/schedule" method="POST">
                                                @csrf
                                                <input type="hidden" name="ac_id" value="{{ $ac->id }}">
                                                <div class="grid grid-cols-2 gap-3 mb-3">
                                                    <div class="field">
                                                        <label class="field-label"><i
                                                                class="fa-solid fa-circle-play text-[9px]"
                                                                style="color:var(--mint);"></i> Turn ON</label>
                                                        <input class="input text-mono" type="time" name="timer_on"
                                                            value="{{ $ac->timer_on ? \Carbon\Carbon::parse($ac->timer_on)->format('H:i') : '' }}">
                                                    </div>
                                                    <div class="field">
                                                        <label class="field-label"><i
                                                                class="fa-solid fa-circle-stop text-[9px]"
                                                                style="color:var(--coral);"></i> Turn OFF</label>
                                                        <input class="input text-mono" type="time"
                                                            name="timer_off"
                                                            value="{{ $ac->timer_off ? \Carbon\Carbon::parse($ac->timer_off)->format('H:i') : '' }}">
                                                    </div>
                                                </div>
                                                <div class="flex gap-2">
                                                    <button type="button" class="btn btn-ghost btn-sm flex-1"
                                                        onclick="toggleTimer({{ $ac->id }})">Cancel</button>
                                                    <button type="submit"
                                                        class="btn btn-primary btn-sm flex-1 save-timer-btn">
                                                        <i class="fa-solid fa-check text-[10px]"></i>
                                                        <span>Save</span>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if ($acs->count() === 0)
                            <div class="panel">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-solid fa-snowflake"></i></div>
                                    <p class="empty-title">No AC units</p>
                                    <p class="empty-sub">Add the first AC unit to start controlling</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('components.bottom-nav')

    {{-- Power confirm modal --}}
    <div id="powerModal" class="modal-backdrop">
        <div class="modal" style="max-width:380px;">
            <div class="modal-body text-center" style="padding-top:22px;">
                <div id="powerModalIcon" class="confirm-icon info"><i class="fa-solid fa-power-off"></i></div>
                <h2 style="font-size:16px;font-weight:600;color:var(--ink-0);margin:0 0 4px;">Konfirmasi Power</h2>
                <p id="powerModalDesc" class="text-sm" style="color:var(--ink-2);margin:0;"></p>
            </div>
            <div class="modal-footer" style="padding-top:6px;">
                <button type="button" onclick="cancelPower()" class="btn btn-ghost flex-1">Cancel</button>
                <button type="button" id="powerModalConfirm" onclick="confirmPower()"
                    class="btn btn-primary flex-1">Lanjutkan</button>
            </div>
        </div>
    </div>


    {{-- Add AC modal --}}
    @auth
        @if (in_array(Auth::user()->role, ['admin', 'operator']))
            <div id="modal" class="modal-backdrop">
                <div class="modal">
                    <div class="modal-header">
                        <div>
                            <p class="eyebrow"><i class="fa-solid fa-plus"></i> New</p>
                            <h2>Add AC Unit</h2>
                        </div>
                    </div>
                    <form id="addACForm" method="POST" action="/rooms/{{ $room->id }}/ac">
                        @csrf
                        <div class="modal-body space-y-3">
                            <div class="field">
                                <label class="field-label">AC Number</label>
                                <input class="input text-mono" type="number" name="ac_number" min="1"
                                    max="15" placeholder="1" required>
                            </div>
                            <div class="field">
                                <label class="field-label">AC Name</label>
                                <input class="input" type="text" name="name" placeholder="unit_a" pattern="\S+"
                                    title="AC name must not contain spaces" required>
                                <p class="field-hint" style="font-size:11px;color:var(--ink-3);margin-top:4px;">No spaces allowed</p>
                            </div>
                            <div class="field">
                                <label class="field-label">Brand</label>
                                <input class="input" type="text" name="brand" placeholder="daikin" pattern="\S+"
                                    title="Brand must not contain spaces" required>
                                <p class="field-hint" style="font-size:11px;color:var(--ink-3);margin-top:4px;">No spaces allowed</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create AC Unit</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="editModal" class="modal-backdrop">
                <div class="modal">
                    <div class="modal-header">
                        <div>
                            <p class="eyebrow" style="color:var(--lavender);"><i class="fa-solid fa-pen"></i> Edit</p>
                            <h2>Edit AC Unit</h2>
                        </div>
                    </div>
                    <form id="editACForm" method="POST" action="">
                        @csrf
                        @method('PUT')
                        <div class="modal-body space-y-3">
                            <div class="field">
                                <label class="field-label">AC Number</label>
                                <input class="input text-mono" id="editAcNumber" type="number" name="ac_number"
                                    min="1" max="15" required>
                            </div>
                            <div class="field">
                                <label class="field-label">AC Name</label>
                                <input class="input" id="editAcName" type="text" name="name" pattern="\S+"
                                    title="AC name must not contain spaces" required>
                                <p class="field-hint" style="font-size:11px;color:var(--ink-3);margin-top:4px;">No spaces allowed</p>
                            </div>
                            <div class="field">
                                <label class="field-label">Brand</label>
                                <input class="input" id="editAcBrand" type="text" name="brand" pattern="\S+"
                                    title="Brand must not contain spaces" required>
                                <p class="field-hint" style="font-size:11px;color:var(--ink-3);margin-top:4px;">No spaces allowed</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-ghost" onclick="closeEditModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endauth

    <script>
        let currentAcId = null;
        const normalizeFormValue = value => (value || '').trim().toLowerCase();

        async function acFetch(url, body = null) {
            const headers = {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            };
            const opts = { method: 'POST', headers };
            if (body) {
                headers['Content-Type'] = 'application/x-www-form-urlencoded';
                opts.body = new URLSearchParams(body).toString();
            }
            const res = await fetch(url, opts);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res;
        }

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

        function acPanelsExcept(ignoreId = null) {
            return Array.from(document.querySelectorAll('.ac-panel'))
                .filter(panel => String(panel.dataset.acId) !== String(ignoreId));
        }

        function acNumberExists(acNumber, ignoreId = null) {
            return acPanelsExcept(ignoreId)
                .some(panel => Number(panel.dataset.acNumber) === Number(acNumber));
        }

        function acNameExists(acName, ignoreId = null) {
            return acPanelsExcept(ignoreId)
                .some(panel => normalizeFormValue(panel.dataset.acName) === acName);
        }

        function normalizeAcForm(form) {
            const nameInput = form.querySelector('[name="name"]');
            const brandInput = form.querySelector('[name="brand"]');

            nameInput.value = normalizeFormValue(nameInput.value);
            brandInput.value = normalizeFormValue(brandInput.value);

            return {
                numberInput: form.querySelector('[name="ac_number"]'),
                nameInput,
                brandInput,
            };
        }

        document.querySelectorAll('#addACForm input, #editACForm input').forEach(input => {
            input.addEventListener('input', () => {
                if (input.name === 'name') {
                    validateNoSpaces(input, 'AC Name');
                    return;
                }

                if (input.name === 'brand') {
                    validateNoSpaces(input, 'Brand');
                    return;
                }

                clearInputValidity(input);
            });
        });

        document.getElementById('addACForm')?.addEventListener('submit', e => {
            const form = e.currentTarget;
            const {
                numberInput,
                nameInput,
                brandInput
            } = normalizeAcForm(form);

            if (!validateNoSpaces(nameInput, 'AC Name')) {
                e.preventDefault();
                nameInput.reportValidity();
                return;
            }

            if (!validateNoSpaces(brandInput, 'Brand')) {
                e.preventDefault();
                brandInput.reportValidity();
                return;
            }

            if (acNumberExists(numberInput.value)) {
                e.preventDefault();
                blockDuplicateInput(numberInput, 'AC number already exists in this room');
                return;
            }

            if (acNameExists(nameInput.value)) {
                e.preventDefault();
                blockDuplicateInput(nameInput, 'AC name already exists in this room');
            }
        });

        document.getElementById('editACForm')?.addEventListener('submit', e => {
            const form = e.currentTarget;
            const {
                numberInput,
                nameInput,
                brandInput
            } = normalizeAcForm(form);

            if (!validateNoSpaces(nameInput, 'AC Name')) {
                e.preventDefault();
                nameInput.reportValidity();
                return;
            }

            if (!validateNoSpaces(brandInput, 'Brand')) {
                e.preventDefault();
                brandInput.reportValidity();
                return;
            }

            if (acNumberExists(numberInput.value, currentAcId)) {
                e.preventDefault();
                blockDuplicateInput(numberInput, 'AC number already exists in this room');
                return;
            }

            if (acNameExists(nameInput.value, currentAcId)) {
                e.preventDefault();
                blockDuplicateInput(nameInput, 'AC name already exists in this room');
            }
        });

        function openEditModal() {
            if (!currentAcId) return;
            const panel = document.getElementById('ac-' + currentAcId);
            if (!panel) return;
            document.getElementById('editAcNumber').value = panel.dataset.acNumber || '';
            document.getElementById('editAcName').value = panel.dataset.acName || '';
            document.getElementById('editAcBrand').value = panel.dataset.acBrand || '';
            document.getElementById('editACForm').action = '/ac/' + currentAcId;
            document.getElementById('editModal')?.classList.add('is-open');
        }

        function closeEditModal() {
            document.getElementById('editModal')?.classList.remove('is-open');
        }
        document.getElementById('editModal')?.addEventListener('click', e => {
            if (e.target === document.getElementById('editModal')) closeEditModal();
        });

        function openModal() {
            if ({{ $acs->count() }} >= 15) {
                window.smToast('Maximum 15 AC units reached', 'error');
                return;
            }
            document.getElementById('modal')?.classList.add('is-open');
        }

        function closeModal() {
            document.getElementById('modal')?.classList.remove('is-open');
            document.querySelector('#modal form')?.reset();
        }
        document.getElementById('modal')?.addEventListener('click', e => {
            if (e.target === document.getElementById('modal')) closeModal();
        });

        async function setTemp(id, temp) {
            if (temp < 16) temp = 16;
            if (temp > 30) temp = 30;

            const panel = document.getElementById(`ac-${id}`);
            const tempEl = panel?.querySelector('.temp-value');
            const current = tempEl ? parseInt(tempEl.textContent, 10) : NaN;
            if (!isNaN(current) && current === temp) {
                window.smToast?.(
                    temp === 16 ? 'Already at minimum (16°C)' :
                    temp === 30 ? 'Already at maximum (30°C)' :
                    `Already at ${temp}°C`,
                    'info'
                );
                return;
            }

            const btnMinus = panel?.querySelector('.ctrl-btn[title*="Lower"]');
            const btnPlus  = panel?.querySelector('.ctrl-btn[title*="Raise"]');
            [btnMinus, btnPlus].forEach(b => b && b.classList.add('ac-ctrl-busy'));

            // Optimistic UI
            if (tempEl) tempEl.textContent = temp;
            const ring = document.getElementById(`tempRing-${id}`);
            if (ring) {
                ring.classList.remove('temp-cool', 'temp-warm', 'temp-hot');
                ring.classList.add(temp <= 20 ? 'temp-cool' : temp <= 25 ? 'temp-warm' : 'temp-hot');
            }

            const acName = panel
                ? `AC ${panel.dataset.acNumber}${panel.dataset.acName ? ' · ' + panel.dataset.acName : ''}`
                : 'AC';

            const minDelay = new Promise(r => setTimeout(r, 2000));
            try {
                await acFetch(`/ac/${id}/temp/${temp}`);
                window.smToast?.(`${acName} temperature set to ${temp}°C`, 'success');
            } catch {
                // Revert
                if (tempEl) tempEl.textContent = current;
                if (ring) {
                    ring.classList.remove('temp-cool', 'temp-warm', 'temp-hot');
                    ring.classList.add(current <= 20 ? 'temp-cool' : current <= 25 ? 'temp-warm' : 'temp-hot');
                }
                window.smToast?.('Failed to update AC temperature', 'error');
            } finally {
                await minDelay;
                [btnMinus, btnPlus].forEach(b => b && b.classList.remove('ac-ctrl-busy'));
                const actualTemp = tempEl ? parseInt(tempEl.textContent, 10) : temp;
                if (btnMinus) {
                    btnMinus.disabled = actualTemp <= 16;
                    btnMinus.setAttribute('onclick', `setTemp(${id}, ${actualTemp - 1})`);
                }
                if (btnPlus) {
                    btnPlus.disabled = actualTemp >= 30;
                    btnPlus.setAttribute('onclick', `setTemp(${id}, ${actualTemp + 1})`);
                }
            }
        }

        function toggleTimer(id) {
            const view = document.getElementById('timerView-' + id);
            const edit = document.getElementById('timerEdit-' + id);
            const btn = document.getElementById('btnTimer-' + id);
            if (!view || !edit || !btn) return;
            const editing = edit.classList.contains('hidden');
            view.classList.toggle('hidden', editing);
            edit.classList.toggle('hidden', !editing);
            btn.innerHTML = editing ?
                '<i class="fa-solid fa-xmark text-[9px]"></i><span>Cancel</span>' :
                '<i class="fa-solid fa-pen text-[9px]"></i><span>Edit</span>';
        }
        document.querySelectorAll('.timer-form').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const on = this.querySelector('[name="timer_on"]').value;
                const off = this.querySelector('[name="timer_off"]').value;
                if (on === off && on !== '') {
                    window.smToast?.('Timer ON and OFF cannot be the same', 'error');
                    return;
                }
                const acId = this.querySelector('[name="ac_id"]')?.value;
                const saveBtn = this.querySelector('.save-timer-btn');
                if (saveBtn) saveBtn.classList.add('ac-ctrl-busy');

                const minDelay = new Promise(r => setTimeout(r, 2000));
                try {
                    await acFetch(this.action, { timer_on: on, timer_off: off });
                    updateTimerPanel({ ac_unit_id: acId, room_id: null, timer_on: on || null, timer_off: off || null });
                    window.smToast?.('Timer saved successfully', 'success');
                } catch {
                    window.smToast?.('Failed to save timer', 'error');
                } finally {
                    await minDelay;
                    if (saveBtn?.isConnected) saveBtn.classList.remove('ac-ctrl-busy');
                }
            });
        });

        async function handleDeleteTimer(e, form) {
            e.preventDefault();
            if (!confirm('Delete this AC timer?')) return;
            const panel = form.closest('.ac-panel');
            const acId = panel?.dataset.acId;
            const deleteBtn = form.querySelector('button[type="submit"]');
            if (deleteBtn) deleteBtn.classList.add('ac-ctrl-busy');

            const minDelay = new Promise(r => setTimeout(r, 2000));
            let ok = false;
            try {
                await acFetch(form.action, { timer_on: '', timer_off: '' });
                ok = true;
                updateTimerPanel({ ac_unit_id: acId, room_id: null, timer_on: null, timer_off: null });
                window.smToast?.('Timer deleted successfully', 'success');
            } catch {
                window.smToast?.('Failed to delete timer', 'error');
            } finally {
                await minDelay;
                if (!ok && deleteBtn?.isConnected) deleteBtn.classList.remove('ac-ctrl-busy');
            }
        }

        document.querySelectorAll('.delete-timer-form').forEach(form => {
            form.addEventListener('submit', e => handleDeleteTimer(e, form));
        });

        function toggleDropdown() {
            document.getElementById('dropdownAC')?.classList.toggle('show');
        }

        function selectAC(id, name) {
            currentAcId = id;
            localStorage.setItem('selectedAC', id);
            const span = document.getElementById('selectedAC');
            if (span) span.textContent = name;
            document.querySelectorAll('.ac-panel').forEach(el => el.classList.add('hidden'));
            document.getElementById('ac-' + id)?.classList.remove('hidden');
            const df = document.getElementById('deleteForm');
            if (df) df.action = '/ac/' + id;
            document.getElementById('dropdownAC')?.classList.remove('show');
        }
        document.addEventListener('click', e => {
            const dd = document.getElementById('dropdownAC');
            const tr = document.querySelector('.selector');
            if (dd && tr && !dd.contains(e.target) && !tr.contains(e.target)) dd.classList.remove('show');
        });

        function confirmDelete(e) {
            e.preventDefault();
            if (confirm('Delete this AC? This action cannot be undone.')) e.target.submit();
            return false;
        }

        let pendingPower = null;
        document.querySelectorAll('.power-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const targetPower = (this.querySelector('[name="power"]')?.value || '').toUpperCase();
                const turnOn = targetPower === 'ON';
                const acName = this.dataset.acName || 'AC ini';
                pendingPower = {
                    id: this.closest('.ac-panel')?.dataset.acId,
                    power: targetPower,
                    acName,
                    form: this,
                    btn: this.querySelector('.power-btn'),
                };
                const icon = document.getElementById('powerModalIcon');
                const desc = document.getElementById('powerModalDesc');
                const conf = document.getElementById('powerModalConfirm');
                if (turnOn) {
                    icon.className = 'confirm-icon success';
                    conf.className = 'btn btn-mint flex-1';
                    desc.textContent = `Nyalakan ${acName}?`;
                } else {
                    icon.className = 'confirm-icon danger';
                    conf.className = 'btn btn-danger flex-1';
                    desc.textContent = `Matikan ${acName}?`;
                }
                document.getElementById('powerModal').classList.add('is-open');
            });
        });

        async function confirmPower() {
            document.getElementById('powerModal').classList.remove('is-open');
            if (!pendingPower) return;
            const { id, power, acName, form, btn } = pendingPower;
            pendingPower = null;

            const turnOn = power === 'ON';

            // Optimistic UI
            if (btn) { btn.classList.toggle('on', turnOn); btn.classList.add('ac-ctrl-busy'); }
            const powerInput = form?.querySelector('[name="power"]');
            if (powerInput) powerInput.value = turnOn ? 'OFF' : 'ON';
            if (form) form.dataset.acPower = power;

            const minDelay = new Promise(r => setTimeout(r, 2000));
            try {
                await acFetch(`/ac/${id}/toggle`, { power });
                window.smToast?.(`${acName} power ${power}`, 'success');
            } catch {
                // Revert
                if (btn) btn.classList.toggle('on', !turnOn);
                if (powerInput) powerInput.value = power;
                if (form) form.dataset.acPower = turnOn ? 'OFF' : 'ON';
                window.smToast?.('Failed to update AC power', 'error');
            } finally {
                await minDelay;
                if (btn) btn.classList.remove('ac-ctrl-busy');
            }
        }

        function cancelPower() {
            document.getElementById('powerModal').classList.remove('is-open');
            pendingPower = null;
        }
        document.getElementById('powerModal')?.addEventListener('click', e => {
            if (e.target === document.getElementById('powerModal')) cancelPower();
        });

document.querySelectorAll('.control-form').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const btn = this.querySelector('.mode-btn-v, .mode-btn-h');
                if (!btn || btn.classList.contains('active')) return;

                const action = this.action;
                const panel = this.closest('.ac-panel');
                const acId = panel?.dataset.acId;
                const acName = panel
                    ? `AC ${panel.dataset.acNumber}${panel.dataset.acName ? ' · ' + panel.dataset.acName : ''}`
                    : 'AC';

                let prefix, segment, label;
                if (action.includes('/mode/')) {
                    prefix = `/ac/${acId}/mode/`;
                    segment = action.split('/mode/')[1];
                    label = `mode ${segment}`;
                } else if (action.includes('/fan-speed/')) {
                    prefix = `/ac/${acId}/fan-speed/`;
                    segment = action.split('/fan-speed/')[1];
                    label = `fan ${segment}`;
                } else if (action.includes('/swing/')) {
                    prefix = `/ac/${acId}/swing/`;
                    segment = action.split('/swing/')[1];
                    label = `swing ${segment}`;
                }

                // Optimistic: pindah active state
                const oldActive = panel?.querySelector(`form[action^="${prefix}"] button.active`);
                if (oldActive) oldActive.classList.remove('active');
                btn.classList.add('active');

                // Disable semua tombol dalam grup selama loading
                const groupBtns = panel?.querySelectorAll(`form[action^="${prefix}"] button`);
                groupBtns?.forEach(b => b.classList.add('ac-ctrl-busy'));

                const minDelay = new Promise(r => setTimeout(r, 2000));
                try {
                    await acFetch(action);
                    window.smToast?.(`${acName} ${label}`, 'success');
                } catch {
                    // Revert
                    btn.classList.remove('active');
                    if (oldActive) oldActive.classList.add('active');
                    window.smToast?.('Failed to update AC setting', 'error');
                } finally {
                    await minDelay;
                    groupBtns?.forEach(b => b.classList.remove('ac-ctrl-busy'));
                }
            });
        });

        let currentRoomId = 0;

        function updateTimerPanel(payload) {
            if (!payload?.ac_unit_id) return;
            if (currentRoomId && payload.room_id && Number(payload.room_id) !== currentRoomId) return;

            const id = payload.ac_unit_id;
            const view = document.getElementById('timerView-' + id);
            const edit = document.getElementById('timerEdit-' + id);
            const btn = document.getElementById('btnTimer-' + id);
            if (!view) return;

            const on = payload.timer_on || null;
            const off = payload.timer_off || null;

            if (on || off) {
                view.innerHTML = `
                    <div class="timer-state">
                        <div class="timer-card ${on ? 'is-on' : ''}">
                            <span class="t-icon"><i class="fa-solid fa-circle-play"></i></span>
                            <div class="t-meta">
                                <p class="t-label">Turn On</p>
                                <p class="t-value ${on ? '' : 'empty'}">${on || '—'}</p>
                            </div>
                        </div>
                        <div class="timer-card ${off ? 'is-off' : ''}">
                            <span class="t-icon"><i class="fa-solid fa-circle-stop"></i></span>
                            <div class="t-meta">
                                <p class="t-label">Turn Off</p>
                                <p class="t-value ${off ? '' : 'empty'}">${off || '—'}</p>
                            </div>
                        </div>
                    </div>
                    <form action="/ac/${id}/schedule" method="POST" class="delete-timer-form mt-3">
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content || ''}">
                        <input type="hidden" name="timer_on" value="">
                        <input type="hidden" name="timer_off" value="">
                        <button type="submit" class="btn btn-ghost btn-sm btn-block">
                            <i class="fa-solid fa-trash text-[10px]"></i>
                            <span>Delete Timer</span>
                        </button>
                    </form>
                `;
                const delForm = view.querySelector('.delete-timer-form');
                if (delForm) {
                    delForm.addEventListener('submit', e => handleDeleteTimer(e, delForm));
                }
            } else {
                view.innerHTML = `
                    <div class="timer-empty">
                        <i class="fa-regular fa-clock"></i>
                        No timer set
                    </div>
                `;
            }

            if (edit) {
                const onInput = edit.querySelector('[name="timer_on"]');
                const offInput = edit.querySelector('[name="timer_off"]');
                if (onInput) onInput.value = on || '';
                if (offInput) offInput.value = off || '';
                if (edit.classList.contains('hidden') === false) {
                    edit.classList.add('hidden');
                    view.classList.remove('hidden');
                    if (btn) btn.innerHTML = '<i class="fa-solid fa-pen text-[9px]"></i><span>Edit</span>';
                }
            }
        }

        let _espFetchFailed = false;

        const updateEspStatus = () => {
            const pill = document.getElementById('espStatusPill');
            const text = document.getElementById('espStatusText');
            if (!pill || !text) return;

            const roomId = Number(pill.dataset.roomId);
            fetch('/device-status', {
                    headers: {
                        'Accept': 'application/json'
                    },
                    cache: 'no-store'
                })
                .then(r => r.ok ? r.json() : Promise.reject())
                .then(devices => {
                    _espFetchFailed = false;
                    const current = Array.isArray(devices) ? devices.find(d => Number(d.room_id) === roomId) : null;
                    if (!current) return;

                    const online = current.is_online === true || current.status === 'online';
                    pill.classList.toggle('pill-online', online);
                    pill.classList.toggle('pill-error', !online);
                    text.textContent = `ESP ${online ? 'Online' : 'Offline'}`;
                })
                .catch(() => {
                    if (!_espFetchFailed) {
                        _espFetchFailed = true;
                        window.smToast?.('Failed to load device status', 'error');
                    }
                });
        };

        document.addEventListener('DOMContentLoaded', () => {
            updateEspStatus();

            // Real-time: ESP status + AC state push via Reverb (update DOM langsung tanpa reload)
            if (window.Echo) {
                currentRoomId = Number(document.getElementById('espStatusPill')?.dataset.roomId);

                const swingLabelMap = {
                    off: 'Still',
                    full: 'Full',
                    half: '½',
                    down: 'Down'
                };
                const ucfirst = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1).toLowerCase() : '';

                function updateAcPanel(payload) {
                    if (!payload?.ac_unit_id) return;
                    if (currentRoomId && payload.room_id && Number(payload.room_id) !== currentRoomId) return;

                    const panel = document.getElementById(`ac-${payload.ac_unit_id}`);
                    if (!panel) return;

                    const power = (payload.power || 'OFF').toUpperCase();
                    const mode = (payload.mode || 'COOL').toLowerCase();
                    const fan = (payload.fan_speed || 'AUTO').toLowerCase();
                    const swing = (payload.swing || 'OFF').toLowerCase();
                    const temp = Number(payload.set_temperature) || 24;

                    // Temp value
                    const valEl = panel.querySelector('.temp-value');
                    if (valEl) valEl.textContent = temp;

                    // Temp ring color category
                    const ring = document.getElementById(`tempRing-${payload.ac_unit_id}`);
                    if (ring) {
                        ring.classList.remove('temp-cool', 'temp-warm', 'temp-hot');
                        ring.classList.add(temp <= 20 ? 'temp-cool' : (temp <= 25 ? 'temp-warm' : 'temp-hot'));
                    }

                    // Ring summary: "Cool · Auto · Still"
                    const summary = panel.querySelector('.ring-summary');
                    if (summary) {
                        summary.textContent =
                            `${ucfirst(mode)} · ${ucfirst(fan)} · ${swingLabelMap[swing] || ucfirst(swing)}`;
                    }

                    // Power button (toggle .on)
                    const powerBtn = panel.querySelector('.power-btn');
                    if (powerBtn) powerBtn.classList.toggle('on', power === 'ON');

                    // Power form data attribute (dipakai modal konfirmasi)
                    const powerForm = panel.querySelector('.power-form');
                    if (powerForm) {
                        powerForm.dataset.acPower = power;
                        const powerInput = powerForm.querySelector('[name="power"]');
                        if (powerInput) powerInput.value = power === 'ON' ? 'OFF' : 'ON';
                    }

                    // +/- temp button onclick handlers (selalu refer ke nilai temp saat ini)
                    const btnMinus = panel.querySelector('.ctrl-btn[title*="Lower"]');
                    const btnPlus = panel.querySelector('.ctrl-btn[title*="Raise"]');

                    if (btnMinus) {
                        btnMinus.setAttribute('onclick', `setTemp(${payload.ac_unit_id}, ${temp - 1})`);
                        btnMinus.disabled = (temp <= 16);
                    }
                    if (btnPlus) {
                        btnPlus.setAttribute('onclick', `setTemp(${payload.ac_unit_id}, ${temp + 1})`);
                        btnPlus.disabled = (temp >= 30);
                    }

                    // Mode / Fan / Swing buttons — toggle .active sesuai value baru
                    const setActiveByForm = (actionPrefix, value) => {
                        panel.querySelectorAll(`form[action^="${actionPrefix}"]`).forEach(form => {
                            const action = form.getAttribute('action') || '';
                            const segment = action.split('/').pop();
                            const btn = form.querySelector('button');
                            if (btn) btn.classList.toggle('active', segment === value);
                        });
                    };
                    setActiveByForm(`/ac/${payload.ac_unit_id}/mode/`, mode);
                    setActiveByForm(`/ac/${payload.ac_unit_id}/fan-speed/`, fan);
                    setActiveByForm(`/ac/${payload.ac_unit_id}/swing/`, swing);
                }

                window.Echo.channel('device-status')
                    .listen('.DeviceStatusUpdated', () => updateEspStatus())
                    .listen('.AcStatusUpdated', (e) => updateAcPanel(e))
                    .listen('.AcTimerUpdated', (e) => updateTimerPanel(e));
            }

            @if (session('new_ac_id'))
                const id = @js(session('new_ac_id'));
                localStorage.setItem('selectedAC', id);
                const el = document.querySelector(`#dropdownAC div[data-id="${id}"]`);
                selectAC(id, el ? el.dataset.label :
                    @js($firstAc ? 'AC ' . $firstAc->ac_number . ' · ' . $firstAc->name . ($firstAc->brand ? ' · ' . $firstAc->brand : '') : '')
                    );
                @if (session('success'))
                    window.smToast(@js(session('success')), 'success');
                @endif
            @else
                const saved = localStorage.getItem('selectedAC');
                if (saved && document.getElementById('ac-' + saved)) {
                    const el = document.querySelector(`#dropdownAC div[data-id="${saved}"]`);
                    selectAC(saved, el ? el.dataset.label :
                        @js($firstAc ? 'AC ' . $firstAc->ac_number . ' · ' . $firstAc->name . ($firstAc->brand ? ' · ' . $firstAc->brand : '') : '')
                        );
                } else {
                    localStorage.removeItem('selectedAC');
                    @if ($firstAc)
                        selectAC({{ $firstAc->id }},
                            @js('AC ' . $firstAc->ac_number . ' · ' . $firstAc->name . ($firstAc->brand ? ' · ' . $firstAc->brand : ''))
                            );
                    @endif
                }
            @endif
            @if (session('success') && !session('new_ac_id'))
                window.smToast(@js(session('success')), 'success');
            @endif
            @if (session('error'))
                window.smToast(@js(session('error')), 'error');
            @endif
            @if (session('warning'))
                window.smToast(@js(session('warning')), 'warn');
            @endif
            @if ($errors->any())
                window.smToast(@js($errors->first()), 'error');
            @endif
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeModal();
                closeEditModal();
                cancelPower();
                document.getElementById('dropdownAC')?.classList.remove('show');
            }
        });
        if (window.history.replaceState) window.history.replaceState(null, null, window.location.href);

        // Jalankan interval status perangkat
        setInterval(updateEspStatus, 5000);

    </script>
    @include('components.sidebar-scripts')
</body>

</html>
