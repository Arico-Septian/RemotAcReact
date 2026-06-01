<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — SmartAC</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="/js/chart.umd.js"></script>
    @vite('resources/js/app.js')
    @include('components.sidebar-styles')
    <style>
        /* Wrapper jadi pill (div selalu hormati border-radius, beda dgn select native mobile) */
        .trend-filter {
            position: relative;
            display: inline-flex;
            align-items: center;
            background-color: var(--panel-2);
            border: 1px solid var(--line);
            border-radius: 8px;
            transition: var(--t-base);
        }

        .trend-filter::after {
            content: '';
            position: absolute;
            right: 13px;
            top: 50%;
            width: 11px;
            height: 11px;
            transform: translateY(-50%);
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23c8d0e0' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat center / contain;
            pointer-events: none;
        }

        .trend-filter:hover,
        .trend-filter:focus-within {
            background-color: rgba(77, 212, 255, 0.10);
            border-color: rgba(77, 212, 255, 0.50);
            box-shadow: 0 0 14px rgba(77, 212, 255, 0.18);
        }

        .trend-filter-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background: transparent;
            border: none;
            color: var(--ink-1);
            border-radius: 8px;
            padding: 8px 34px 8px 16px;
            font-size: 12px;
            font-weight: 600;
            font-family: var(--font-sans);
            cursor: pointer;
            outline: none;
            width: 100%;
        }

        .trend-filter-select option {
            background: #161a24;
            color: #f0f2f8;
            font-weight: 600;
        }

        .trend-filter:hover .trend-filter-select,
        .trend-filter-select:focus {
            color: var(--cyan);
        }

        .dashboard-rooms-panel {
            padding: 20px;
            border-radius: var(--r-2xl);
            background: var(--panel-1);
            border: none;
            box-shadow: var(--inset-hi);
        }

        /* Temperature chart panel */
        .temp-chart-panel {
            border: none !important;
            position: relative;
        }

        /* Judul + eyebrow grafik digeser sejajar dengan judul panel lain */
        .temp-chart-panel .panel-header > div:first-child {
            padding-left: 12px;
        }

        /* Bottom row: Server Rooms + Recent Activity */
        .dashboard-bottom-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        /* Tablet (≥ 768px) & laptop: berjajar 2 kolom + panel sama tinggi */
        @media (min-width: 768px) {
            .dashboard-bottom-row {
                grid-template-columns: 1fr 1fr;
                gap: 16px;
                align-items: stretch;
            }
        }

        /* Tablet (768-1023px): teks tetap terbaca, chip boleh wrap ke bawah */
        @media (min-width: 768px) and (max-width: 1023px) {
            .activity-desc {
                font-size: 13px;
            }
            .activity-chips .chip {
                font-size: 11px;
                padding: 2px 7px;
            }
            .activity-chips .chip i {
                font-size: 9px;
            }
            .activity-chips {
                gap: 4px;
            }
            .activity-desc-row {
                gap: 6px;
                flex-wrap: wrap;
            }
        }

        /* Card min-height untuk visual rhythm konsisten — content boleh grow di atasnya */
        .dashboard-room-row,
        .activity-item {
            min-height: 72px !important;
        }

        @media (max-width: 768px) {
            .dashboard-room-row,
            .activity-item {
                min-height: 64px !important;
            }
        }

        @media (max-width: 480px) {
            .dashboard-room-row,
            .activity-item {
                min-height: 58px !important;
            }
        }

        .dashboard-rooms-panel,
        .dashboard-activity-panel {
            min-width: 0;
        }

        /* ===== Recent Activity widget — premium ===== */
        .dashboard-activity-panel {
            padding: 20px;
            border-radius: var(--r-2xl);
            background: var(--panel-1);
            border: none;
            box-shadow: var(--inset-hi);
            position: relative;
        }

        .activity-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
            padding-left: 12px;
        }

        .activity-title-group {
            display: inline-flex;
            align-items: center;
            gap: 9px;
        }

        .activity-title-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--r-md);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: linear-gradient(135deg, rgba(34, 211, 238, 0.18), rgba(167, 139, 250, 0.18));
            border: 1px solid rgba(34, 211, 238, 0.30);
            color: var(--cyan);
            font-size: 15px;
        }

        .activity-title {
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
            color: var(--ink-0);
            margin: 0;
            letter-spacing: 0em;
        }

        .activity-subtitle {
            margin: 3px 0 0;
            font-size: 12px;
            line-height: 1.3;
            color: var(--ink-3);
        }

        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 9px;
            border-radius: var(--r-full);
            background: rgb(var(--mint-d-rgb) / 0.10);
            border: 1px solid rgb(var(--mint-d-rgb) / 0.32);
            color: var(--mint);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.05em;
            backdrop-filter: blur(8px);
        }

        .live-dot {
            width: 6px;
            height: 6px;
            border-radius: var(--r-full);
            background: var(--mint);
            box-shadow: 0 0 0 0 rgb(var(--mint-d-rgb) / 0.55);
            animation: livePulse 1.8s ease-out infinite;
        }

        @keyframes livePulse {
            0% {
                box-shadow: 0 0 0 0 rgb(var(--mint-d-rgb) / 0.55);
            }

            70% {
                box-shadow: 0 0 0 7px rgb(var(--mint-d-rgb) / 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgb(var(--mint-d-rgb) / 0);
            }
        }

        .activity-list {
            display: grid;
            gap: 10px;
        }

        .activity-item {
            position: relative;
            display: grid;
            grid-template-columns: 36px 1fr;
            align-items: center;
            gap: 12px;
            padding: 10px 14px 10px 26px;
            border-radius: var(--r-xl);
            background: var(--panel-2);
            border: 1px solid var(--line-soft);
            transition: var(--t-base);
        }

        .activity-item .activity-rail {
            align-self: stretch;
        }

        .activity-item:hover {
            background: var(--panel-2);
            border-color: var(--line);
        }

        .activity-rail {
            position: absolute;
            left: 12px;
            top: 12px;
            bottom: 12px;
            width: 5px;
            border-radius: 999px;
            background: var(--tone, var(--ink-2));
            opacity: 1;
            /* Efek menyala yang lebih nyata */
            box-shadow: 0 0 10px color-mix(in srgb, var(--tone) 40%, transparent);
        }

        .activity-icon-wrap {
            width: 32px;
            height: 32px;
            border-radius: var(--r-md);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: color-mix(in srgb, var(--tone, var(--ink-2)) 14%, transparent);
            border: 1px solid color-mix(in srgb, var(--tone, var(--ink-2)) 30%, transparent);
            color: var(--tone, var(--ink-2));
            font-size: 12px;
            flex-shrink: 0;
        }

        /* Avatar with activity icon badge overlay */
        .activity-avatar-wrap {
            position: relative;
            width: 36px;
            height: 36px;
            flex-shrink: 0;
        }

        .activity-avatar-inner {
            display: block;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            overflow: hidden;
        }

        .activity-avatar-img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: block;
            object-fit: cover;
            object-position: center;
        }

        .activity-avatar-fallback {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            background: linear-gradient(135deg, color-mix(in srgb, var(--tone, var(--ink-2)) 35%, #1e293b), color-mix(in srgb, var(--tone, var(--ink-2)) 18%, #0f172a));
            color: #ffffff;
            border: 1px solid color-mix(in srgb, var(--tone, var(--ink-2)) 40%, transparent);
        }

        .activity-icon-badge {
            position: absolute;
            right: -3px;
            bottom: -3px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--tone, var(--ink-2));
            color: #0b1220;
            font-size: 8px;
            border: 2px solid var(--bg-0, #07101f);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
        }

        /* Pastikan ikon tidak miring (tag <i> default italic) & benar-benar center */
        /* Semua ikon di panel Recent Activity tidak boleh miring (tag <i> default italic) */
        .dashboard-activity-panel i,
        .activity-list i {
            font-style: normal !important;
        }

        .activity-icon-badge i {
            font-style: normal !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            height: 100% !important;
            line-height: 1 !important;
            text-align: center;
        }

        .activity-item.tone-coral .activity-icon-badge,
        .activity-item.tone-lavender .activity-icon-badge,
        .activity-item.tone-slate .activity-icon-badge {
            color: #ffffff;
        }

        /* Tone variants — sets --tone per item */
        .activity-item.tone-cyan {
            --tone: #22d3ee;
        }

        .activity-item.tone-mint {
            --tone: #34d399;
        }

        .activity-item.tone-lavender {
            --tone: #a78bfa;
        }

        .activity-item.tone-coral {
            --tone: #fb7185;
        }

        .activity-item.tone-amber {
            --tone: #fbbf24;
        }

        .activity-item.tone-sky {
            --tone: #38bdf8;
        }

        .activity-item.tone-slate {
            --tone: #94a3b8;
        }

        .activity-body {
            min-width: 0;
        }

        .activity-line {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 8px;
        }

        .activity-user {
            font-size: 14px;
            font-weight: 700;
            color: var(--ink-0);
            letter-spacing: 0em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .activity-time {
            font-family: var(--font-mono);
            font-size: 13px;
            color: var(--ink-4);
            flex-shrink: 0;
        }

        .activity-desc-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 3px;
            min-width: 0;
        }

        .activity-desc {
            margin: 0;
            font-size: 13px;
            line-height: 1.3;
            color: var(--ink-2);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
            min-width: 0;
        }

        .activity-chips {
            display: inline-flex;
            flex-wrap: nowrap;
            gap: 4px;
            flex-shrink: 0;
        }

        .activity-chips .chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: var(--r-sm);
            background: rgb(var(--ink-2-rgb) / 0.10);
            border: 1px solid rgb(var(--ink-2-rgb) / 0.18);
            color: var(--ink-2);
            font-size: 12px;
            font-weight: 600;
        }

        .activity-chips .chip i {
            font-size: 10px;
            opacity: 0.7;
        }

        @media (max-width: 768px) {
            .dashboard-rooms-panel,
            .dashboard-activity-panel {
                padding: 14px;
            }

            .dashboard-rooms-panel .panel-header,
            .activity-header {
                flex-wrap: wrap;
                gap: 12px;
            }

            .dashboard-rooms-title,
            .activity-title {
                font-size: 18px;
                flex: 1;
            }

            .dashboard-rooms-action {
                min-width: 80px;
                min-height: 44px;
                padding: 8px 12px;
                font-size: 12px;
                flex-shrink: 0;
            }

            .dashboard-room-row,
            .activity-item {
                grid-template-columns: 1fr auto;
                gap: 10px;
                padding: 12px 14px 12px 18px;
                min-height: 64px;
            }

            .activity-item {
                grid-template-columns: 32px 1fr;
                padding: 10px 14px 10px 22px;
                gap: 10px;
            }

            .dashboard-room-temp {
                min-width: 60px;
                font-size: 13px;
                text-align: right;
            }

            .dashboard-room-status {
                min-width: 72px;
                font-size: 10px;
                padding: 4px 8px;
            }

            .dashboard-room-name {
                font-size: 14px;
            }

            .dashboard-room-meta {
                font-size: 12px;
                margin-top: 2px;
            }

            .dashboard-room-row::before {
                width: 4px;
                left: 6px;
                top: 10px;
                bottom: 10px;
            }

            .activity-list {
                gap: 8px;
            }

            .activity-avatar-wrap,
            .activity-avatar-img,
            .activity-avatar-fallback {
                width: 32px;
                height: 32px;
            }

            .activity-avatar-fallback {
                font-size: 12px;
            }

            .activity-icon-badge {
                width: 14px;
                height: 14px;
                font-size: 8px;
            }

            .activity-user {
                font-size: 13px;
            }

            .activity-desc {
                font-size: 12px;
            }

            .activity-time {
                font-size: 11px;
            }
        }

        @media (max-width: 480px) {
            .dashboard-activity-panel {
                padding: 12px;
                border-radius: var(--r-xl);
            }

            .activity-title {
                font-size: 16px;
            }

            .activity-title-icon {
                width: 24px;
                height: 24px;
            }

            .activity-list {
                max-height: 420px;
                gap: 10px;
            }

            .activity-item {
                grid-template-columns: 30px 1fr;
                padding: 9px 10px 9px 24px;
                gap: 10px;
                border-radius: var(--r-lg);
                min-height: 60px;
            }

            .activity-item .activity-rail {
                left: 8px;
                top: 9px;
                bottom: 9px;
            }

            .activity-icon-wrap {
                width: 28px;
                height: 28px;
                font-size: 11px;
            }

            .activity-avatar-wrap {
                width: 30px;
                height: 30px;
            }

            .activity-avatar-img,
            .activity-avatar-fallback {
                width: 30px;
                height: 30px;
                font-size: 11px;
            }

            .activity-icon-badge {
                width: 15px;
                height: 15px;
                font-size: 8px;
                border-width: 1.5px;
            }

            .activity-user {
                font-size: 14px;
            }
            .activity-desc {
                font-size: 13px;
            }

            .activity-time {
                font-size: 12px;
            }

            .activity-chips .chip {
                font-size: 10px;
                padding: 2px 6px;
            }
            .activity-chips .chip i {
                font-size: 8px;
            }
        }

        .dashboard-rooms-panel .panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
            padding-left: 12px;
        }

        .dashboard-rooms-title-group {
            display: inline-flex;
            align-items: center;
            gap: 9px;
        }

        .dashboard-rooms-title-icon {
            width: 26px;
            height: 26px;
            border-radius: var(--r-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(34, 211, 238, 0.18), rgba(167, 139, 250, 0.18));
            border: 1px solid rgba(34, 211, 238, 0.30);
            color: var(--cyan);
            font-size: 11px;
        }

        .dashboard-rooms-title {
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
            color: var(--ink-0);
            margin: 0;
            letter-spacing: 0em;
        }

        .dashboard-rooms-subtitle {
            margin-top: 3px;
            font-size: 12px;
            line-height: 1.3;
            color: var(--ink-3);
        }

        .dashboard-rooms-action {
            min-height: 40px;
            padding: 8px 14px;
            border-radius: var(--r-md);
            background: var(--panel-2);
            border: 1px solid var(--line-soft);
            color: var(--ink-0);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 600;
            font-size: 13px;
            line-height: 1;
            white-space: nowrap;
            transition: var(--t-base);
        }

        .dashboard-rooms-action:hover {
            background: rgba(77, 212, 255, 0.10);
            border-color: rgba(77, 212, 255, 0.50);
            color: var(--cyan);
            box-shadow: 0 0 14px rgba(77, 212, 255, 0.18);
        }

        .dashboard-room-list {
            display: grid;
            gap: 10px;
        }

        .dashboard-room-row {
            padding: 10px 14px 10px 26px;
            border-radius: var(--r-xl);
            background: var(--panel-2);
            border: 1px solid var(--line-soft);
            color: inherit;
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 14px;
            position: relative;
            transition: var(--t-base);
        }

        .dashboard-room-row::before {
            content: '';
            position: absolute;
            left: 12px;
            top: 12px;
            bottom: 12px;
            width: 5px;
            border-radius: 999px;
            background: #fca5a5;
            opacity: 1;
            transition: background var(--t-base);
            /* Tambahkan glow pada baris ruangan agar seragam */
            box-shadow: 0 0 8px color-mix(in srgb, currentColor 30%, transparent);
        }

        /* Indikator status via warna garis kiri (tanpa glow, match activity rail) */
        .dashboard-room-row[data-status="online"]::before {
            background: var(--mint-d);
        }
        .dashboard-room-row[data-status="offline"]::before {
            background: var(--coral);
        }

        /* Dashboard sections spacing */
        .app-content-inner>*+* {
            margin-top: 32px;
        }

        .dashboard-room-row:hover {
            background: var(--panel-2);
            border-color: var(--line-soft);
            transform: none;
        }

        .dashboard-room-main {
            min-width: 0;
        }

        .dashboard-room-name {
            font-size: 16px;
            font-weight: 700;
            line-height: 1.2;
            color: var(--ink-0);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dashboard-room-meta {
            margin-top: 3px;
            color: var(--ink-3);
            font-size: 13px;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dashboard-room-temp {
            min-width: 76px;
            text-align: right;
            color: var(--ink-3);
            font-family: var(--font-mono);
            font-size: 16px;
            font-weight: 700;
        }

        .dashboard-room-status {
            min-width: 82px;
            padding: 6px 12px;
            border-radius: var(--r-full);
            text-align: center;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
        }

        .dashboard-room-status.online {
            background: var(--mint-soft);
            color: var(--mint);
        }

        .dashboard-room-status.offline {
            background: rgb(var(--coral-rgb) / 0.14);
            color: #fca5a5;
        }

        @media (max-width: 768px) {
            .dashboard-rooms-panel {
                padding: 16px;
            }

            .dashboard-rooms-panel .panel-header {
                flex-wrap: wrap;
                gap: 12px;
            }

            .dashboard-rooms-title {
                font-size: 18px;
                flex: 1;
            }

            .dashboard-rooms-action {
                min-width: 80px;
                min-height: 44px;
                padding: 8px 12px;
                font-size: 12px;
                flex-shrink: 0;
            }

            .dashboard-room-row {
                grid-template-columns: 1fr auto;
                gap: 10px;
                padding: 12px 14px 12px 16px;
                min-height: 64px;
            }

            .dashboard-room-temp {
                grid-column: 2;
                grid-row: 1;
                min-width: 60px;
                font-size: 13px;
                text-align: right;
            }

            .dashboard-room-status {
                grid-column: 2;
                grid-row: 2;
                min-width: 72px;
                font-size: 10px;
                padding: 4px 8px;
            }

            .dashboard-room-name {
                font-size: 14px;
            }

            .dashboard-room-meta {
                font-size: 12px;
                margin-top: 2px;
            }

            .dashboard-room-row::before {
                width: 4px;
                left: 6px;
                top: 10px;
                bottom: 10px;
            }
        }

        /* Very small screens (< 480px) */
        @media (max-width: 480px) {
            .dashboard-rooms-panel {
                padding: 12px;
                border-radius: var(--r-xl);
            }

            .dashboard-rooms-panel .panel-header {
                flex-direction: row;
                gap: 8px;
                align-items: center;
                flex-wrap: nowrap;
            }

            .dashboard-rooms-panel .panel-header > div:first-child {
                min-width: 0;
                flex: 1;
            }

            .dashboard-rooms-title {
                font-size: 16px;
            }

            .dashboard-rooms-action {
                flex-shrink: 0;
                width: auto;
                justify-content: center;
                min-height: 36px;
                font-size: 11px;
                padding: 6px 10px;
            }

            .dashboard-room-list {
                gap: 8px;
            }

            .dashboard-room-row {
                grid-template-columns: 1fr auto;
                gap: 8px;
                padding: 10px 12px 10px 16px;
                min-height: 60px;
                border-radius: var(--r-lg);
            }

            .dashboard-room-row::before {
                width: 3px;
                left: 6px;
                top: 9px;
                bottom: 9px;
            }

            .dashboard-room-main {
                min-width: 0;
            }

            .dashboard-room-name {
                font-size: 14px;
                font-weight: 600;
            }

            .dashboard-room-meta {
                font-size: 12px;
            }

            .dashboard-room-temp {
                min-width: 50px;
                font-size: 12px;
                padding: 0 4px;
            }

            .dashboard-room-status {
                min-width: 60px;
                font-size: 10px;
                padding: 3px 6px;
            }

            .trend-filter-select {
                font-size: 11px;
                padding: 6px 26px 6px 11px;
            }
        }

        /* Landscape mode (max-height: 600px) */
        @media (max-height: 600px) and (orientation: landscape) {
            .dashboard-rooms-panel {
                padding: 12px;
            }

            .dashboard-rooms-title {
                font-size: 16px;
                margin-bottom: 8px;
            }

            .dashboard-room-row {
                min-height: 56px;
                padding: 8px 12px;
                gap: 8px;
            }

            .dashboard-room-name {
                font-size: 13px;
            }

            .dashboard-room-meta {
                font-size: 11px;
            }

            .dashboard-room-temp {
                font-size: 12px;
            }

            .dashboard-room-status {
                font-size: 10px;
                padding: 3px 6px;
            }
        }

        /* Lock badge ikon: cegah glyph (mis. fa-bolt) menarik badge jadi lebih besar */
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

        /* Stat card text styling */
        .stat-card .stat-label-sm {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--ink-3);
        }

        .stat-card .stat-num-lg {
            font-family: var(--font-sans);
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

        /* Angka utama selalu putih untuk hierarki yang kuat */
        .stat-card .stat-num-lg {
            color: var(--ink-0);
        }

        /* Label kecil di atas mengambil warna accent per kartu */
        .stat-card.acc-cyan .stat-label-sm,
        .stat-card.acc-cyan .stat-label,
        .stat-card.acc-mint .stat-label-sm,
        .stat-card.acc-mint .stat-label,
        .stat-card.acc-lavender .stat-label-sm,
        .stat-card.acc-lavender .stat-label,
        .stat-card.acc-coral .stat-label-sm,
        .stat-card.acc-coral .stat-label {
            color: var(--ink-0);
        }

        /* Device compatibility - Tablet & Below (768px) */
        @media (max-width: 768px) {
            .dashboard-rooms-panel,
            .dashboard-activity-panel {
                padding: 16px;
            }

            .dashboard-rooms-title,
            .activity-title {
                font-size: 18px;
            }

            .dashboard-rooms-subtitle,
            .activity-subtitle {
                font-size: 12px;
            }

            .dashboard-room-row,
            .activity-item {
                padding: 12px 14px;
                min-height: 64px;
                gap: 10px;
            }

            .dashboard-room-name {
                font-size: 14px;
            }

            .dashboard-room-meta {
                font-size: 12px;
            }

            .dashboard-room-temp {
                font-size: 13px;
            }

            .dashboard-room-status {
                font-size: 10px;
                padding: 4px 8px;
                min-width: 60px;
            }
        }

        /* Stat cards optimization - Tablet (640px - 768px) */
        @media (min-width: 641px) and (max-width: 768px) {
            .grid.grid-cols-2.lg\:grid-cols-4 {
                gap: 12px;
            }

            .stat-card {
                padding: 16px 18px;
            }

            .stat-label-sm {
                font-size: 10px;
            }

            .stat-num-lg {
                font-size: 32px;
                margin: 6px 0 4px;
            }

            .stat-sub {
                font-size: 10px;
            }

            .stat-icon {
                font-size: 20px;
            }
        }

        /* Stat cards optimization for small screens (≤ 768px) — Tablet */
        @media (max-width: 768px) {
            .grid.grid-cols-2.lg\:grid-cols-4 {
                gap: 12px;
            }

            .stat-card {
                padding: 14px 16px;
            }

            .stat-card .stat-label-sm,
            .stat-card .stat-label {
                font-size: 10px;
                letter-spacing: 0.08em;
            }

            .stat-card .stat-num-lg {
                font-size: 28px;
                margin: 6px 0 4px;
            }

            .stat-card .stat-sub,
            .stat-card .stat-meta {
                font-size: 10px;
                line-height: 1.4;
            }

            .stat-card .stat-icon {
                width: 34px;
                height: 34px;
                border-radius: var(--r-md);
                font-size: 14px;
            }
        }

        /* Mobile M (< 480px) */
        @media (max-width: 480px) {
            .grid.grid-cols-2.lg\:grid-cols-4 {
                gap: 10px;
            }

            .stat-card {
                padding: 12px 14px;
            }

            .stat-card .stat-label-sm,
            .stat-card .stat-label {
                font-size: 8px;
                letter-spacing: 0.05em;
            }

            .stat-card .stat-num-lg {
                font-size: 24px;
                margin: 4px 0 2px;
            }

            .stat-card .stat-sub,
            .stat-card .stat-meta {
                font-size: 10px;
                line-height: 1.3;
            }

            .stat-card .stat-icon {
                width: 30px;
                height: 30px;
                border-radius: var(--r-md);
                font-size: 12px;
            }

            .stat-card .accent-bar {
                top: 12px;
                bottom: 12px;
            }

            .dashboard-rooms-panel,
            .dashboard-activity-panel {
                padding: 12px;
                border-radius: var(--r-xl);
            }

            .dashboard-rooms-title,
            .activity-title {
                font-size: 16px;
            }

            .dashboard-rooms-title-icon,
            .activity-title-icon {
                width: 34px;
                height: 34px;
                font-size: 14px;
            }

            .dashboard-rooms-action {
                min-height: 40px;
                min-width: 70px;
                padding: 8px 10px;
                font-size: 11px;
            }

            .dashboard-room-list,
            .activity-list {
                gap: 8px;
            }

            .dashboard-room-row,
            .activity-item {
                padding: 10px 12px;
                gap: 8px;
                border-radius: var(--r-lg);
                min-height: 60px;
            }

            .dashboard-room-row::before {
                width: 3px;
                left: 6px;
                top: 9px;
                bottom: 9px;
            }

            .dashboard-room-main {
                min-width: 0;
            }

            .dashboard-room-name {
                font-size: 13px;
                font-weight: 600;
            }

            .dashboard-room-meta {
                font-size: 11px;
            }

            .dashboard-room-temp {
                min-width: 50px;
                font-size: 12px;
                padding: 0 4px;
            }

            .dashboard-room-status {
                min-width: 60px;
                font-size: 10px;
                padding: 3px 6px;
            }

            .activity-item {
                grid-template-columns: 30px 1fr;
                padding: 9px 10px 9px 24px;
            }

            .activity-icon-wrap {
                width: 28px;
                height: 28px;
                font-size: 11px;
            }

            .activity-avatar-wrap {
                width: 30px;
                height: 30px;
            }

            .activity-avatar-img,
            .activity-avatar-fallback {
                width: 30px;
                height: 30px;
                font-size: 11px;
            }

            .activity-icon-badge {
                width: 15px;
                height: 15px;
                font-size: 8px;
                border-width: 1.5px;
            }

            .activity-user {
                font-size: 14px;
            }
            .activity-desc {
                font-size: 13px;
            }

            .activity-time {
                font-size: 12px;
            }

            .trend-filter-select {
                font-size: 11px;
                padding: 6px 26px 6px 11px;
            }
        }

        /* Mobile (≤ 480px) — extra-tight stat cards */
        @media (max-width: 480px) {
            .grid.grid-cols-2.lg\:grid-cols-4 {
                gap: 8px;
            }

            .stat-card {
                padding: 10px 12px;
            }

            .stat-card .stat-label-sm,
            .stat-card .stat-label {
                font-size: 10px;
                letter-spacing: 0.05em;
            }

            .stat-card .stat-num-lg {
                font-size: 20px;
                margin: 3px 0 2px;
            }

            .stat-card .stat-sub,
            .stat-card .stat-meta {
                font-size: 10px;
                line-height: 1.3;
            }

            .stat-card .stat-icon {
                width: 26px;
                height: 26px;
                border-radius: var(--r-sm);
                font-size: 11px;
            }

            .stat-card .accent-bar {
                top: 10px;
                bottom: 10px;
            }

            .dashboard-rooms-panel,
            .dashboard-activity-panel {
                padding: 10px;
                border-radius: var(--r-lg);
            }

            .dashboard-rooms-title,
            .activity-title {
                font-size: 14px;
            }

            .dashboard-rooms-title-icon,
            .activity-title-icon {
                width: 32px;
                height: 32px;
                font-size: 13px;
            }

            .dashboard-rooms-action {
                min-height: 32px;
                min-width: 60px;
                padding: 6px 8px;
                font-size: 10px;
            }

            .dashboard-room-list,
            .activity-list {
                gap: 6px;
            }

            .dashboard-room-row,
            .activity-item {
                padding: 8px 10px 8px 22px;
                gap: 8px;
                border-radius: var(--r-md);
                min-height: 54px;
            }

            .dashboard-room-row::before {
                width: 4px;
                left: 8px;
                top: 8px;
                bottom: 8px;
            }

            .dashboard-room-name {
                font-size: 13px;
            }

            .dashboard-room-meta {
                font-size: 11px;
            }

            .dashboard-room-temp {
                min-width: 50px;
                font-size: 12px;
            }

            .dashboard-room-status {
                min-width: 54px;
                font-size: 8px;
                padding: 2px 5px;
            }

            .activity-item {
                grid-template-columns: 34px 1fr;
                padding: 9px 10px 9px 22px;
            }

            .activity-icon-wrap {
                width: 26px;
                height: 26px;
                font-size: 10px;
            }

            .activity-avatar-wrap,
            .activity-avatar-inner,
            .activity-avatar-img,
            .activity-avatar-fallback {
                width: 32px;
                height: 32px;
                font-size: 11px;
                border-radius: 50%;
            }
            .activity-avatar-img {
                object-fit: cover;
                aspect-ratio: 1 / 1;
            }

            .activity-icon-badge {
                width: 14px;
                height: 14px;
                font-size: 8px;
                border-width: 1px;
            }

            .activity-user {
                font-size: 14px;
            }
            .activity-desc {
                font-size: 11px;
            }

            .activity-time {
                font-size: 12px;
            }

            .activity-chips .chip {
                font-size: 10px;
                padding: 2px 6px;
            }
            .activity-chips .chip i {
                font-size: 8px;
            }
        }

        /* Temperature chart height responsive */
        @media (max-width: 768px) {
            .temp-chart-wrap {
                height: 260px !important;
            }
        }

        /* Tablet (≤ 1024px): teks chart sedikit lebih kecil */
        @media (max-width: 1024px) {
            .temp-chart-panel .panel-title {
                font-size: 13px !important;
            }
            .temp-chart-panel .eyebrow {
                font-size: 10px !important;
            }
            .temp-chart-panel .trend-filter-select {
                font-size: 11px !important;
                padding: 5px 26px 5px 11px !important;
                border-radius: 8px !important;
            }
        }

        @media (max-width: 480px) {
            .temp-chart-wrap {
                height: 240px !important;
            }
            /* Panel temperatur lebih kompak di mobile */
            .temp-chart-panel {
                padding: 16px !important;
            }
            /* Title kiri, filter kanan — tetap 1 baris, tidak boleh wrap */
            .temp-chart-panel .panel-header {
                margin-bottom: 10px !important;
                gap: 8px !important;
                flex-wrap: nowrap !important;
                align-items: center !important;
            }
            .temp-chart-panel .panel-header > div:first-child {
                min-width: 0;
                flex: 1;
                overflow: hidden;
            }
            .temp-chart-panel .panel-header > div:last-child {
                flex-shrink: 0;
                gap: 6px !important;
            }
            .temp-chart-panel .panel-title {
                font-size: 12px !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .temp-chart-panel .eyebrow {
                font-size: 8px !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                gap: 4px !important;
            }
            .temp-chart-panel .eyebrow i {
                font-size: 10px !important;
            }
            .temp-chart-panel .trend-filter-select {
                font-size: 10px !important;
                padding: 5px 24px 5px 10px !important;
                border-radius: 8px !important;
                min-width: 0;
            }
            .temp-chart-panel #trendInfo {
                margin-top: 6px !important;
                font-size: 10px !important;
                line-height: 1.4 !important;
            }
        }

        @media (max-width: 480px) {
            .temp-chart-wrap {
                height: 220px !important;
            }
            .temp-chart-panel {
                padding: 16px !important;
            }
            .temp-chart-panel .panel-title {
                font-size: 11px !important;
            }
            .temp-chart-panel .eyebrow {
                font-size: 8px !important;
                letter-spacing: 0.05em !important;
            }
            .temp-chart-panel .trend-filter-select {
                font-size: 10px !important;
                padding: 5px 24px 5px 10px !important;
                border-radius: var(--r-md) !important;
            }
            .temp-chart-panel #trendInfo {
                font-size: 10px !important;
            }
        }

        /* Touch target optimization */
        @media (hover: none) and (pointer: coarse) {
            .dashboard-rooms-action {
                min-height: 48px;
                min-width: 48px;
            }

            .dashboard-room-row {
                padding: 12px 14px 12px 22px;
                min-height: 72px;
            }
        }

        /* Tablet portrait (769px - 1023px) — promote stat grid to 4 columns */
        @media (min-width: 769px) and (max-width: 1023px) {
            .grid.grid-cols-2.lg\:grid-cols-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 10px;
            }

            .stat-card {
                padding: 14px 16px;
            }

            .stat-card .stat-label-sm {
                font-size: 10px;
            }

            .stat-card .stat-num-lg {
                font-size: 28px;
                margin: 6px 0 4px;
            }

            .stat-card .stat-sub {
                font-size: 10px;
            }

            .stat-icon {
                font-size: 18px;
            }
        }

        /* Header keep on one row across breakpoints */
        .main-header { flex-wrap: nowrap; }
        .main-header > .flex.items-center.gap-3 { min-width: 0; flex: 1; }
        .main-header > .flex.items-center.gap-2 { flex-shrink: 0; }

        /* Mobile (≤ 480px): shrink header so title fits in one row */
        @media (max-width: 480px) {
            .main-header { gap: 6px; padding-left: 10px; padding-right: 10px; }
            .main-header > .flex.items-center.gap-3 { gap: 6px; }
            .main-header > .flex.items-center.gap-2 { gap: 4px; }
            .main-header .app-header-title h1 { font-size: 13px; line-height: 1.2; }
            .main-header .app-header-title p { font-size: 10px; line-height: 1.2; }
            .main-header .btn-icon { width: 32px; height: 32px; }
        }

        /* Ultra-wide screens (>1600px) — cap content width for readability */
        @media (min-width: 1600px) {
            .app-content-inner {
                max-width: 1480px;
                margin-left: auto;
                margin-right: auto;
            }
        }

        @media (min-width: 1920px) {
            .app-content-inner {
                max-width: 1600px;
            }
        }
    </style>
</head>

<body>
    <div class="app-layout">
    <div class="app-bg"></div>
    <div id="overlay"></div>

    <div class="layout">
        @include('components.sidebar')

        <div class="app-main">
            {{-- HEADER --}}
            <header class="main-header">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="lg:hidden btn-icon" title="Menu">
                        <i class="fa-solid fa-bars-staggered"></i>
                    </button>
                    <div class="app-header-title">
                        <h1>Dashboard</h1>
                        <p>Overview &amp; live monitoring</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @include('components.notification-bell')

                </div>
            </header>

            {{-- BODY --}}
            <div class="page-body">
                <div class="app-content">
                    <div class="app-content-inner space-y-4">

                        {{-- Stat cards --}}
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                            <div class="stat-card acc-cyan">
                                <span class="accent-bar"></span>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="stat-label-sm">Rooms</p>
                                        <p class="stat-num-lg">{{ $rooms->count() }}</p>
                                        <p class="stat-sub">Registered</p>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-server"></i></div>
                                </div>
                            </div>
                            <div class="stat-card acc-lavender">
                                <span class="accent-bar"></span>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="stat-label-sm">AC Units</p>
                                        <p class="stat-num-lg" id="statTotalAc">{{ $totalAc }}</p>
                                        <p class="stat-sub">Total unit</p>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-snowflake"></i></div>
                                </div>
                            </div>
                            <div class="stat-card acc-mint">
                                <span class="accent-bar"></span>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="stat-label-sm">AC Active</p>
                                        <p class="stat-num-lg" id="statActiveAc">{{ $activeAc }}</p>
                                        <p class="stat-sub">Powered on</p>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-bolt"></i></div>
                                </div>
                            </div>
                            <div class="stat-card acc-coral">
                                <span class="accent-bar"></span>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="stat-label-sm">AC Idle</p>
                                        <p class="stat-num-lg" id="statInactiveAc">{{ $inactiveAc }}</p>
                                        <p class="stat-sub">Not active</p>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-power-off"></i></div>
                                </div>
                            </div>
                        </div>

                        {{-- Temperature trend chart (full width) --}}
                        <div class="panel temp-chart-panel">
                            <div class="panel-header">
                                <div>
                                    <p class="eyebrow"><i class="fa-solid fa-chart-line"></i> <span
                                            id="trendRangeLabel">Trend last 1 hour</span></p>
                                    <h2 class="panel-title">Room Temperatures</h2>
                                </div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="trend-filter">
                                        <select id="trendRange" class="trend-filter-select" title="Select time range">
                                            <option value="1h">1 Hour</option>
                                            <option value="3h">3 Hours</option>
                                            <option value="6h">6 Hours</option>
                                            <option value="today">Today</option>
                                        </select>
                                    </span>
                                </div>
                            </div>
                            <div class="temp-chart-wrap" style="height:300px;position:relative;">
                                <canvas id="tempChart"></canvas>
                                <div id="tempChartEmpty" class="empty-state"
                                    style="position:absolute;inset:0;display:none;align-items:center;justify-content:center;">
                                    <div style="text-align:center;">
                                        <div class="empty-icon"><i class="fa-solid fa-temperature-empty"></i></div>
                                        <p class="empty-sub">No temperature data in the last 1 hour</p>
                                    </div>
                                </div>
                                <div id="tempChartLoading" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:transparent;pointer-events:none;">
                                    <i class="fa-solid fa-spinner fa-spin" style="font-size:20px;color:var(--ink-4);opacity:0.5;"></i>
                                </div>
                            </div>
                            <p id="trendInfo" class="panel-meta"
                                style="margin-top:8px;font-size:11px;color:var(--ink-4);"></p>
                        </div>

                        {{-- Bottom row: Server Rooms + Recent Activity --}}
                        <div class="dashboard-bottom-row">
                            {{-- Server rooms preview --}}
                            <section class="panel dashboard-rooms-panel">
                                <div class="panel-header">
                                    <div>
                                        <div class="dashboard-rooms-title-group">
                                            <div>
                                                <h2 class="dashboard-rooms-title">Server Rooms</h2>
                                                <p class="dashboard-rooms-subtitle">{{ $totalRooms }} rooms registered</p>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="{{ route('rooms.overview') }}" class="dashboard-rooms-action"
                                        aria-label="View all server rooms">
                                        <span>View all</span>
                                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                    </a>
                                </div>

                                @php
                                    $previewRooms = $rooms->take(5);
                                @endphp

                                @if ($previewRooms->isNotEmpty())
                                    <div class="dashboard-room-list">
                                        @foreach ($previewRooms as $room)
                                            @php
                                                $temperature = $room->temperature ?? $room->last_temperature ?? null;
                                                $status = $room->device_status === 'online' ? 'online' : 'offline';
                                            @endphp
                                            <div class="dashboard-room-row"
                                                data-dashboard-room-id="{{ $room->id }}"
                                                data-status="{{ $status }}">
                                                <div class="dashboard-room-main">
                                                    <h3 class="dashboard-room-name">{{ ucfirst($room->name) }}</h3>
                                                    <p class="dashboard-room-meta">
                                                        {{ $room->acUnits->count() }} unit &middot;
                                                        {{ $room->device_id ?: '-' }}
                                                    </p>
                                                </div>
                                                <div id="dashboard-room-temp-{{ $room->id }}"
                                                    class="dashboard-room-temp">
                                                    @if ($temperature !== null)
                                                        {{ number_format((float) $temperature, 1) }}&deg;C
                                                    @else
                                                        -- &deg;C
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="empty-state" style="padding:28px 12px;">
                                        <div class="empty-icon"><i class="fa-solid fa-server"></i></div>
                                        <p class="empty-title">No rooms</p>
                                        <p class="empty-sub">Add a room to start monitoring</p>
                                    </div>
                                @endif
                            </section>

                            {{-- Recent Activity widget --}}
                            <section class="panel dashboard-activity-panel">
                                <div class="activity-header">
                                    <div>
                                        <h2 class="activity-title">Recent Activity</h2>
                                        <p class="activity-subtitle">{{ count($recentActivities) }} recent activities</p>
                                    </div>
                                    <span class="activity-title-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
                                </div>

                                <div class="activity-list" id="activityList">
                                    @forelse ($recentActivities->take(5) as $log)
                                        <div class="activity-item tone-{{ $log['tone'] }}">
                                            <div class="activity-rail"></div>
                                            <div class="activity-avatar-wrap">
                                                @if (!empty($log['user_avatar']))
                                                    <img src="{{ $log['user_avatar'] }}"
                                                        alt="{{ $log['user_name'] }}" class="activity-avatar-img">
                                                @else
                                                    <div class="activity-avatar-fallback">{{ $log['user_initial'] }}
                                                    </div>
                                                @endif
                                                <span class="activity-icon-badge"><i
                                                        class="{{ $log['icon'] }}"></i></span>
                                            </div>
                                            <div class="activity-body">
                                                <div class="activity-line">
                                                    <span class="activity-user">{{ $log['user_name'] }}</span>
                                                    <span class="activity-time">{{ $log['time'] }}</span>
                                                </div>
                                                @php
                                                    $hasRoom = !empty($log['room']) && $log['room'] !== '-';
                                                    $hasAc = !empty($log['ac']) && $log['ac'] !== '-';
                                                @endphp
                                                <div class="activity-desc-row">
                                                    <p class="activity-desc">{{ $log['description'] }}</p>
                                                    @if ($hasRoom || $hasAc)
                                                        <span class="activity-chips">
                                                            @if ($hasRoom)
                                                                <span class="chip"><i class="fa-solid fa-door-open"></i>{{ $log['room'] }}</span>
                                                            @endif
                                                            @if ($hasAc)
                                                                <span class="chip"><i class="fa-solid fa-snowflake"></i>{{ $log['ac'] }}</span>
                                                            @endif
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="empty-state" style="padding:24px 12px;">
                                            <div class="empty-icon"><i class="fa-solid fa-clock-rotate-left"></i>
                                            </div>
                                            <p class="empty-title">No activity</p>
                                        </div>
                                    @endforelse
                                </div>
                            </section>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('components.bottom-nav')

    <script>
        function tempColor(t) {
            if (t === null || isNaN(Number(t))) return 'rgba(100,116,139,0.55)';
            if (t > 30) return 'rgba(251,113,133,0.85)'; // coral
            if (t > 25) return 'rgba(251,191,36,0.85)'; // amber
            return 'rgba(77,212,255,0.85)'; // cyan
        }

        // ===== Helpers (hex -> rgba) =====
        function hexToRgba(hex, a = 1) {
            if (!hex) return `rgba(255,255,255,${a})`;
            const h = hex.replace('#', '').trim();
            const full = h.length === 3 ? h.split('').map(c => c + c).join('') : h;
            const r = parseInt(full.slice(0, 2), 16);
            const g = parseInt(full.slice(2, 4), 16);
            const b = parseInt(full.slice(4, 6), 16);
            return `rgba(${r},${g},${b},${a})`;
        }

        const _gradCache = new Map();
        function makeAreaGradient(chart, hex) {
            const { ctx, chartArea } = chart;
            if (!chartArea) return hexToRgba(hex, 0.12);

            const key = `${hex}:${Math.round(chartArea.top)}:${Math.round(chartArea.bottom)}`;
            if (_gradCache.has(key)) return _gradCache.get(key);

            const g = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            g.addColorStop(0, hexToRgba(hex, 0.55));
            g.addColorStop(0.6, hexToRgba(hex, 0.22));
            g.addColorStop(1, hexToRgba(hex, 0.0));
            _gradCache.set(key, g);
            return g;
        }

        // ===== Crosshair plugin (garis vertikal + glow point) =====
        const crosshairGlowPlugin = {
            id: 'crosshairGlow',
            afterDatasetsDraw(chart) {
                const tooltip = chart.tooltip;
                if (!tooltip || !tooltip.getActiveElements || tooltip.getActiveElements().length === 0) return;

                const { ctx, chartArea: { top, bottom } } = chart;
                const active = tooltip.getActiveElements()[0];
                const x = active.element.x;
                const y = active.element.y;

                ctx.save();
                // garis vertikal
                ctx.beginPath();
                ctx.moveTo(x, top);
                ctx.lineTo(x, bottom);
                ctx.lineWidth = 1;
                ctx.strokeStyle = 'rgba(103,232,249,0.25)';
                ctx.stroke();

                // titik aktif — tanpa shadowBlur (mahal di canvas)
                ctx.fillStyle = 'rgba(255,255,255,0.9)';
                ctx.beginPath();
                ctx.arc(x, y, 3.5, 0, Math.PI * 2);
                ctx.fill();

                ctx.restore();
            }
        };

        const glowLinePlugin = {
            id: 'glowLine',
            beforeDatasetsDraw(chart) {
                chart.ctx.save();
                // shadowBlur dikurangi drastis — nilai tinggi paksa GPU blur tiap frame
                chart.ctx.shadowBlur = 6;
                chart.ctx.shadowColor = 'rgba(103,232,249,0.20)';
            },
            afterDatasetsDraw(chart) {
                chart.ctx.restore();
            }
        };


        /* ===== NOTIFICATIONS ===== */
        let notifEnabled = localStorage.getItem('notifEnabled') === 'true';
        const notifCooldown = {};

        function updateNotifButton() {
            const btn = document.getElementById('notifBtn');
            if (!btn) return;
            const i = btn.querySelector('i');
            if (notifEnabled && Notification.permission === 'granted') {
                btn.style.color = 'var(--amber)';
                btn.style.background = 'var(--amber-soft)';
                btn.style.borderColor = 'var(--amber-soft-2)';
                i.className = 'fa-solid fa-bell text-xs';
                btn.title = 'Notifications enabled — click to disable';
            } else {
                btn.style.color = '';
                btn.style.background = '';
                btn.style.borderColor = '';
                i.className = 'fa-regular fa-bell text-xs';
                btn.title = 'Enable critical temperature notifications';
            }
        }

        function toggleNotifications() {
            if (!('Notification' in window)) {
                window.smToast('Browser does not support notifications', 'error');
                return;
            }
            if (notifEnabled) {
                notifEnabled = false;
                localStorage.setItem('notifEnabled', 'false');
                updateNotifButton();
                return;
            }
            Notification.requestPermission().then(perm => {
                notifEnabled = perm === 'granted';
                localStorage.setItem('notifEnabled', notifEnabled ? 'true' : 'false');
                updateNotifButton();
                if (perm === 'denied') window.smToast('Notification permission denied', 'error');
            });
        }

        let tempChart;

        function chartSizingForViewport() {
            const w = window.innerWidth;
            if (w <= 480) {
                // Mobile
                return {
                    legendFontSize: 9, legendBoxSize: 5, legendPadding: 6,
                    legendAlign: 'center',
                    tickFontSize: 9, xMaxTicks: 5, yMaxTicks: 9, legendRoomNameMax: 8, compactLegend: true,
                };
            } else if (w <= 1023) {
                // Tablet
                return {
                    legendFontSize: 10, legendBoxSize: 7, legendPadding: 9,
                    legendAlign: 'center',
                    tickFontSize: 10, xMaxTicks: 7, yMaxTicks: 9, legendRoomNameMax: 18, compactLegend: false,
                };
            }
            // Laptop / desktop — nilai asli
            return {
                legendFontSize: 11, legendBoxSize: 8, legendPadding: 12,
                legendAlign: 'end',
                tickFontSize: 10, xMaxTicks: undefined, yMaxTicks: undefined, legendRoomNameMax: 999, compactLegend: false,
            };
        }

        function truncateLegendText(text, maxLength) {
            const value = String(text ?? '');
            if (value.length <= maxLength) return value;
            return `${value.slice(0, Math.max(0, maxLength - 1))}\u2026`;
        }

        function makeTrendDatasetLabel(roomName, tempText) {
            const s = chartSizingForViewport();
            const room = truncateLegendText(roomName, s.legendRoomNameMax);
            if (!s.compactLegend) return `${room} (${tempText})`;

            const tempMatch = String(tempText).match(/(\d+(?:\.\d+)?)/);
            if (!tempMatch) return room;
            return `${room} ${Math.round(Number(tempMatch[1]))}\u00b0`;
        }

        function applyChartSizing() {
            if (!tempChart) return;
            const s = chartSizingForViewport();
            tempChart.options.plugins.legend.align = s.legendAlign;
            tempChart.options.plugins.legend.labels.font.size = s.legendFontSize;
            tempChart.options.plugins.legend.labels.boxWidth = s.legendBoxSize;
            tempChart.options.plugins.legend.labels.boxHeight = s.legendBoxSize;
            tempChart.options.plugins.legend.labels.padding = s.legendPadding;
            tempChart.options.scales.x.ticks.font.size = s.tickFontSize;
            tempChart.options.scales.y.ticks.font.size = s.tickFontSize;
            tempChart.options.scales.x.ticks.maxTicksLimit = s.xMaxTicks;
            tempChart.options.scales.y.ticks.maxTicksLimit = s.yMaxTicks;
            tempChart.data.datasets.forEach(ds => {
                if (ds._roomName && ds._tempText) {
                    ds.label = makeTrendDatasetLabel(ds._roomName, ds._tempText);
                }
            });
            tempChart.update('none');
        }

        function initChart() {
            const canvas = document.getElementById('tempChart');
            if (!canvas) return;

            const s = chartSizingForViewport();

            tempChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: []
                },
                plugins: [glowLinePlugin, crosshairGlowPlugin, {
                    id: 'hoverFocus',
                    _lastActive: -1,
                    afterEvent(chart, args) {
                        const { event } = args;
                        if (event.type !== 'mousemove' && event.type !== 'mouseout') return;

                        const activeElements = chart.getElementsAtEventForMode(event, 'nearest', { intersect: false }, true);
                        const activeIndex = activeElements.length > 0 ? activeElements[0].datasetIndex : -1;

                        // update() hanya dipanggil saat dataset aktif benar-benar berubah
                        if (activeIndex === this._lastActive) return;
                        this._lastActive = activeIndex;

                        chart.data.datasets.forEach((ds, i) => {
                            if (activeIndex >= 0) {
                                if (i === activeIndex) {
                                    ds.borderColor = ds._originalColor;
                                    ds.borderWidth = 4;
                                } else {
                                    ds.borderColor = hexToRgba(ds._originalHex, 0.1);
                                    ds.borderWidth = 1.5;
                                }
                            } else {
                                ds.borderColor = ds._originalColor;
                                ds.borderWidth = ds._isOffline ? 2.4 : 3.2;
                            }
                        });

                        chart.update('none');
                    }
                }],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'nearest',
                        intersect: false,
                        axis: 'x'
                    },

                    animation: {
                        duration: 450
                    },

                    elements: {
                        line: {
                            tension: 0.48,
                            borderWidth: 2.5,
                            borderCapStyle: 'round',
                            borderJoinStyle: 'round',
                        },
                        point: {
                            radius: 0,
                            hitRadius: 12,
                            hoverRadius: 5
                        }
                    },

                    plugins: {
                        legend: {
                            position: 'top',
                            align: s.legendAlign,
                            labels: {
                                color: '#e2e6f0',
                                font: {
                                    family: 'Inter',
                                    size: s.legendFontSize
                                },
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: s.legendBoxSize,
                                boxHeight: s.legendBoxSize,
                                padding: s.legendPadding,
                            }
                        },

                        tooltip: {
                            enabled: true,
                            displayColors: false,
                            backgroundColor: 'rgba(22,26,36,0.97)',
                            borderColor: 'rgba(255,255,255,0.12)',
                            borderWidth: 1,
                            padding: 10,
                            cornerRadius: 10,
                            mode: 'nearest',
                            intersect: false,
                            caretSize: 6,
                            caretPadding: 8,
                            titleColor: '#e2e8f0',
                            bodyColor: '#f8fafc',
                            titleFont: {
                                family: 'Inter',
                                size: 11,
                                weight: '700'
                            },
                            bodyFont: {
                                family: 'Inter',
                                size: 12,
                                weight: '800'
                            },
                            callbacks: {
                                title: (items) => items?.[0]?.label ?? '',
                                label: (item) => {
                                    const v = item.parsed.y;
                                    if (v === null || Number.isNaN(v)) return `${item.dataset.label}: —`;
                                    return `${item.dataset.label}: ${v.toFixed(1)}°C`;
                                }
                            }
                        }
                    },

                    scales: {
                        x: {
                            ticks: {
                                color: '#c8d0e0',
                                maxRotation: 0,
                                font: {
                                    size: s.tickFontSize
                                },
                                maxTicksLimit: s.xMaxTicks,
                                autoSkip: true
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            min: 20,
                            max: 36,
                            ticks: {
                                color: '#c8d0e0',
                                font: {
                                    size: s.tickFontSize
                                },
                                maxTicksLimit: s.yMaxTicks,
                                stepSize: 2,
                                callback: v => v + '°C'
                            },
                            grid: {
                                color: 'rgba(255,255,255,0.06)'
                            }
                        }
                    }
                }
            });
        }

        let _tempFetchFailed = false;
        function refreshTemperature() {
            fetch('/temperature')
                .then(r => r.ok ? r.json() : Promise.reject())
                .then(data => {
                    _tempFetchFailed = false;
                    if (!data) return;
                    data.forEach(room => {
                        const tempEl = document.getElementById(`dashboard-room-temp-${room.id}`);
                        if (!tempEl) return;
                        const temp = parseFloat(room.temp ?? room.last_temp ?? room.temperature);
                        tempEl.textContent = isNaN(temp) ? '-- \u00b0C' : `${temp.toFixed(1)}\u00b0C`;
                    });
                })
                .catch(() => {
                    if (!_tempFetchFailed) {
                        _tempFetchFailed = true;
                        window.smToast?.('Failed to load room temperature data', 'error');
                    }
                });
        }

        function getTrendLimit() {
            const saved = localStorage.getItem('trendLimit');
            return saved !== null ? saved : '5';
        }

        function getTrendRange() {
            const saved = localStorage.getItem('trendRange');
            const normalized = saved === '24h' ? 'today' : saved;
            const allowed = ['1h', '3h', '6h', 'today'];
            return allowed.includes(normalized) ? normalized : '1h';
        }

        const RANGE_LABELS = {
            '1h': 'Trend last 1 hour',
            '3h': 'Trend last 3 hours',
            '6h': 'Trend last 6 hours',
            'today': 'Trend today',
        };

        function refreshTrendChart(showLoader = false) {
            if (!tempChart) return;

            const limit = getTrendLimit();
            const range = getTrendRange();

            const labelEl = document.getElementById('trendRangeLabel');
            if (labelEl) labelEl.textContent = RANGE_LABELS[range] || RANGE_LABELS['1h'];

            if (showLoader) {
                const spinner = document.getElementById('tempChartLoading');
                if (spinner) spinner.style.display = 'flex';
            }

            const trendRequest = fetch(`/temperature/trend?limit=${encodeURIComponent(limit)}&range=${encodeURIComponent(range)}`)
                    .then(r => (r.ok ? r.json() : Promise.reject(r.status)));

            trendRequest
                .then(data => {
                    document.getElementById('tempChartLoading')?.style.setProperty('display', 'none');
                    if (!data || !tempChart) return;

                    const hasAnyData = (data.datasets || []).some(ds =>
                        (ds.data || []).some(v => v !== null && !isNaN(v))
                    );

                    const emptyEl = document.getElementById('tempChartEmpty');
                    const canvasEl = document.getElementById('tempChart');
                    if (emptyEl && canvasEl) {
                        emptyEl.style.display = hasAnyData ? 'none' : 'flex';
                        canvasEl.style.display = hasAnyData ? 'block' : 'none';
                        const emptyTextEl = emptyEl.querySelector('.empty-sub');
                        if (emptyTextEl) {
                            const rangeText = { '1h': 'last 1 hour', '3h': 'last 3 hours', '6h': 'last 6 hours', 'today': 'today' };
                            emptyTextEl.textContent = `No temperature data for ${rangeText[range] || range}`;
                        }
                    }

                    tempChart.data.labels = data.labels || [];

                    // Warna seragam untuk room offline → sinyal visual jelas
                    const OFFLINE_HEX = '#64748b';
                    const OFFLINE_LINE_ALPHA = 0.4;

                    tempChart.data.datasets = (data.datasets || []).map(ds => {
                        const hasCurrentTemp = ds.current_temp !== null && ds.current_temp !== undefined;
                        const hasLastTemp = ds.last_temp !== null && ds.last_temp !== undefined;
                        const tempStr = ds.is_offline ?
                            (hasLastTemp ? `Last ${Number(ds.last_temp).toFixed(1)}\u00b0C` : 'Offline') :
                            (hasCurrentTemp ? `${Number(ds.current_temp).toFixed(1)}\u00b0C` : 'No data');

                        const effectiveHex = ds.is_offline ? OFFLINE_HEX : ds.color;
                        const lineColor = ds.is_offline ?
                            hexToRgba(OFFLINE_HEX, OFFLINE_LINE_ALPHA) :
                            ds.color;

                        return {
                            label: makeTrendDatasetLabel(ds.room, tempStr),
                            data: ds.data,
                            _roomName: ds.room,
                            _tempText: tempStr,

                            borderColor: lineColor,
                            borderDash: ds.is_offline ? [4, 4] : [],
                            _originalColor: lineColor,
                            _originalHex: effectiveHex,
                            borderWidth: ds.is_offline ? 2 : 3.2,

                            tension: 0.48,
                            cubicInterpolationMode: 'monotone',

                            pointRadius: ds.is_offline ? 0 : 2,
                            pointHitRadius: 40,
                            pointHoverRadius: ds.is_offline ? 0 : 5,
                            pointBackgroundColor: ds.is_offline ? hexToRgba(OFFLINE_HEX, 0.6) : '#67e8f9',
                            pointBorderWidth: 0,

                            fill: true,
                            backgroundColor: (ctx) => makeAreaGradient(ctx.chart, effectiveHex),

                            spanGaps: true,
                            _isOffline: ds.is_offline,
                            _offlineSince: ds.offline_since,
                        };
                    });

                    tempChart.update();

                    const infoEl = document.getElementById('trendInfo');
                    if (infoEl) {
                        const shown = (data.datasets || []).length;
                        const onlineCount = (data.datasets || []).filter(d => !d.is_offline).length;
                        const offlineCount = shown - onlineCount;
                        infoEl.textContent = `${onlineCount} online, ${offlineCount} offline. Chart uses recorded historical data.`;
                    }
                })
                .catch(() => {
                    document.getElementById('tempChartLoading')?.style.setProperty('display', 'none');
                    window.smToast?.('Failed to load temperature chart data', 'error');
                });
        }

        setInterval(refreshTemperature, 15000);
        setInterval(refreshTrendChart, 15000);

        let _statusFetchFailed = false;
        let _statsFetchFailed = false;

        function refreshDashboardRoomStatuses() {
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
                        const row = document.querySelector(
                            `[data-dashboard-room-id="${device.room_id}"]`);
                        if (!row) return;

                        const isOnline = device.is_online === true || device.status === 'online';
                        row.setAttribute('data-status', isOnline ? 'online' : 'offline');
                    });
                })
                .catch(() => {
                    if (!_statusFetchFailed) {
                        _statusFetchFailed = true;
                        window.smToast?.('Failed to load device status', 'error');
                    }
                });
        }

        setInterval(refreshDashboardRoomStatuses, 15000);

        function refreshDashboardStats() {
            fetch('/dashboard/stats', {
                    headers: {
                        'Accept': 'application/json'
                    },
                    cache: 'no-store'
                })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    _statsFetchFailed = false;
                    if (!data) return;
                    const set = (id, v) => {
                        const el = document.getElementById(id);
                        if (el && v !== undefined && v !== null) el.textContent = v;
                    };
                    set('statTotalAc', data.total_ac);
                    set('statActiveAc', data.active_ac);
                    set('statInactiveAc', data.inactive_ac);
                })
                .catch(() => {
                    if (!_statsFetchFailed) {
                        _statsFetchFailed = true;
                        window.smToast?.('Failed to load dashboard statistics', 'error');
                    }
                });
        }


        document.addEventListener('DOMContentLoaded', () => {
            initChart();

            // Adapt chart sizing on viewport changes (rotate / resize)
            let resizeTimer = null;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(applyChartSizing, 150);
            });

updateNotifButton();

            // Real-time via Reverb: trigger refresh segera saat event masuk
            if (window.Echo) {
                window.Echo.channel('device-status')
                    .listen('.DeviceStatusUpdated', () => {
                        refreshDashboardRoomStatuses();
                        refreshTemperature();
                    })
                    .listen('.RoomTemperatureUpdated', () => {
                        refreshTemperature();
                        refreshTrendChart();
                    })
                    .listen('.AcStatusUpdated', () => {
                        // AC power/mode/temp berubah dari user/tab lain → refresh trend, status, counter
                        refreshTrendChart();
                        refreshDashboardRoomStatuses();
                        refreshDashboardStats();
                    })
                    .listen('.UserLogCreated', () => {
                        if (typeof refreshRecentActivities === 'function') {
                            refreshRecentActivities();
                        }
                    })
                    .listen('.NotificationCreated', () => {
                        if (typeof updateNotifButton === 'function') {
                            updateNotifButton();
                        }
                    });
            }

            // Setup trend filter dropdowns
            const rangeSelect = document.getElementById('trendRange');
            if (rangeSelect) {
                rangeSelect.value = getTrendRange();
                rangeSelect.addEventListener('change', (e) => {
                    localStorage.setItem('trendRange', e.target.value);
                    refreshTrendChart(true);
                });
            }

            setTimeout(refreshTemperature, 400);
            setTimeout(refreshTrendChart, 500);
            setTimeout(refreshDashboardRoomStatuses, 600);
            setTimeout(refreshDashboardStats, 700);

            // Recent Activity live polling
            const activityList = document.getElementById('activityList');
            const liveBadge = document.getElementById('activityLiveBadge');
            const allowedTones = ['cyan', 'mint', 'lavender', 'coral', 'amber', 'sky', 'slate'];
            const allowedIconPrefix = /^fa-(solid|regular|brands)\s+fa-[a-z0-9-]+$/i;

            function escapeHtml(str) {
                return String(str ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function safeIcon(icon) {
                const v = String(icon || '').trim();
                return allowedIconPrefix.test(v) ? v : 'fa-solid fa-circle-info';
            }

            function safeTone(tone) {
                return allowedTones.includes(tone) ? tone : 'slate';
            }

            function renderActivity(item) {
                const tone = safeTone(item.tone);
                const icon = safeIcon(item.icon);
                const name = escapeHtml(item.user_name || 'System');
                const initial = escapeHtml(item.user_initial || (item.user_name || '?').charAt(0)
                    .toUpperCase());
                const desc = escapeHtml(item.description || item.raw_activity || '');
                const time = escapeHtml(item.time || '');
                const hasRoom = item.room && item.room !== '-';
                const hasAc = item.ac && item.ac !== '-';
                const room = hasRoom ?
                    `<span class="chip"><i class="fa-solid fa-door-open"></i>${escapeHtml(item.room)}</span>` :
                    '';
                const ac = hasAc ?
                    `<span class="chip"><i class="fa-solid fa-snowflake"></i>${escapeHtml(item.ac)}</span>` :
                    '';
                const chips = (room || ac) ? `<span class="activity-chips">${room}${ac}</span>` : '';

                const avatar = item.user_avatar ?
                    `<span class="activity-avatar-inner"><img src="${escapeHtml(item.user_avatar)}" alt="${name}" class="activity-avatar-img"></span>` :
                    `<span class="activity-avatar-inner"><div class="activity-avatar-fallback">${initial}</div></span>`;

                return `
            <div class="activity-item tone-${tone}" data-id="${item.id}">
                <div class="activity-rail"></div>
                <div class="activity-avatar-wrap">
                    ${avatar}
                    <span class="activity-icon-badge"><i class="${icon}"></i></span>
                </div>
                <div class="activity-body">
                    <div class="activity-line">
                        <span class="activity-user">${name}</span>
                        <span class="activity-time">${time}</span>
                    </div>
                    <div class="activity-desc-row">
                        <p class="activity-desc">${desc}</p>
                        ${chips}
                    </div>
                </div>
            </div>
        `;
            }

            async function refreshRecentActivities() {
                if (!activityList) return;
                try {
                    const res = await fetch('/dashboard/recent-activities', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error('fetch failed');
                    const data = await res.json();
                    if (!Array.isArray(data) || data.length === 0) return;
                    activityList.innerHTML = data.slice(0, 5).map(renderActivity).join('');
                    if (liveBadge) liveBadge.style.opacity = '1';
                } catch (e) {
                    if (liveBadge) liveBadge.style.opacity = '0.5';
                }
            }

            setInterval(refreshRecentActivities, 12000);
        });
    </script>
    @include('components.sidebar-scripts')
</body>

</html>
