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
        .trend-filter-select {
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
            color: var(--ink-1);
            border-radius: var(--r-md);
            padding: 6px 10px;
            font-size: 11px;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            outline: none;
            transition: var(--t-base);
        }

        .trend-filter-select:hover {
            background: var(--panel-2);
            border-color: var(--line);
        }

        .trend-filter-select:focus {
            border-color: var(--cyan);
        }

        .trend-demo-toggle {
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
            color: var(--ink-2);
            border-radius: var(--r-md);
            padding: 6px 10px;
            font-size: 11px;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: var(--t-base);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .trend-demo-toggle.active {
            color: var(--cyan);
            border-color: rgba(77, 212, 255, 0.35);
            background: rgba(77, 212, 255, 0.1);
        }

        .trend-demo-toggle i {
            font-size: 10px;
        }

        .dashboard-rooms-panel {
            padding: 20px;
            border-radius: 20px;
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
            box-shadow: var(--inset-hi);
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

        /* Tablet (768-1023px): kecilkan text supaya muat inline */
        @media (min-width: 768px) and (max-width: 1023px) {
            .activity-desc {
                font-size: 9.5px;
            }
            .activity-chips .chip {
                font-size: 8px;
                padding: 1px 4px;
            }
            .activity-chips .chip i {
                font-size: 5.5px;
            }
            .activity-chips {
                gap: 3px;
            }
            .activity-desc-row {
                gap: 5px;
            }
        }

        /* Card min-height untuk visual rhythm konsisten — content boleh grow di atasnya */
        .dashboard-room-row,
        .activity-item {
            min-height: 72px !important;
        }

        @media (max-width: 640px) {
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

        @media (max-width: 360px) {
            .dashboard-room-row,
            .activity-item {
                min-height: 52px !important;
            }
        }

        .dashboard-rooms-panel,
        .dashboard-activity-panel {
            min-width: 0;
        }

        /* ===== Recent Activity widget — premium ===== */
        .dashboard-activity-panel {
            padding: 20px;
            border-radius: 20px;
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
            box-shadow: var(--inset-hi);
            position: relative;
        }

        .dashboard-activity-panel::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(34, 211, 238, 0.45), transparent);
            opacity: 0.7;
        }

        .activity-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
        }

        .activity-title-group {
            display: inline-flex;
            align-items: center;
            gap: 9px;
        }

        .activity-title-icon {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(34, 211, 238, 0.18), rgba(167, 139, 250, 0.18));
            border: 1px solid rgba(34, 211, 238, 0.30);
            color: var(--cyan);
            font-size: 11px;
        }

        .activity-title {
            font-size: 17px;
            font-weight: 700;
            line-height: 1.15;
            color: var(--ink-0);
            margin: 0;
            letter-spacing: -0.01em;
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
            border-radius: 999px;
            background: rgba(52, 211, 153, 0.10);
            border: 1px solid rgba(52, 211, 153, 0.32);
            color: var(--mint);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.05em;
            backdrop-filter: blur(8px);
        }

        .live-dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: var(--mint);
            box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.55);
            animation: livePulse 1.8s ease-out infinite;
        }

        @keyframes livePulse {
            0% {
                box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.55);
            }

            70% {
                box-shadow: 0 0 0 7px rgba(52, 211, 153, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(52, 211, 153, 0);
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
            padding: 10px 14px 10px 18px;
            border-radius: 14px;
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
            transform: translateY(-1px);
        }

        .activity-rail {
            position: absolute;
            left: 6px;
            top: 10px;
            bottom: 10px;
            width: 4px;
            border-radius: 999px;
            background: var(--tone, #94a3b8);
            opacity: 1;
            /* Efek menyala yang lebih nyata */
            box-shadow: 0 0 10px color-mix(in srgb, var(--tone) 40%, transparent);
        }

        .activity-icon-wrap {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: color-mix(in srgb, var(--tone, #94a3b8) 14%, transparent);
            border: 1px solid color-mix(in srgb, var(--tone, #94a3b8) 30%, transparent);
            color: var(--tone, #94a3b8);
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

        .activity-avatar-img,
        .activity-avatar-fallback {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            object-fit: cover;
            background: linear-gradient(135deg, color-mix(in srgb, var(--tone, #94a3b8) 35%, #1e293b), color-mix(in srgb, var(--tone, #94a3b8) 18%, #0f172a));
            color: #ffffff;
            border: 1px solid color-mix(in srgb, var(--tone, #94a3b8) 40%, transparent);
        }

        .activity-icon-badge {
            position: absolute;
            right: -3px;
            bottom: -3px;
            width: 16px;
            height: 16px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--tone, #94a3b8);
            color: #0b1220;
            font-size: 8px;
            border: 2px solid var(--panel-1);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
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
            font-size: 14.5px;
            font-weight: 700;
            color: var(--ink-0);
            letter-spacing: -0.005em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .activity-time {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
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
            gap: 3px;
            padding: 1px 5px;
            border-radius: 5px;
            background: rgba(148, 163, 184, 0.10);
            border: 1px solid rgba(148, 163, 184, 0.18);
            color: var(--ink-3);
            font-size: 9.5px;
            font-weight: 600;
        }

        .activity-chips .chip i {
            font-size: 7px;
            opacity: 0.7;
        }

        @media (max-width: 640px) {
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
                font-size: 17px;
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
                padding: 10px 14px 10px 16px;
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

            .activity-avatar-img,
            .activity-avatar-fallback {
                font-size: 12px;
            }

            .activity-icon-badge {
                width: 14px;
                height: 14px;
                font-size: 7px;
            }

            .activity-user {
                font-size: 13px;
            }

            .activity-desc {
                font-size: 12px;
            }

            .activity-time {
                font-size: 10.5px;
            }
        }

        @media (max-width: 480px) {
            .dashboard-activity-panel {
                padding: 12px;
                border-radius: 16px;
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
                grid-template-columns: 28px 1fr;
                padding: 8px 10px 8px 14px;
                gap: 8px;
                border-radius: 12px;
                min-height: 60px;
            }

            .activity-item .activity-rail {
                left: 6px;
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

            .activity-item .activity-rail {
                left: 5px;
                top: 7px;
                bottom: 7px;
            }

            .activity-user,
            .activity-desc {
                font-size: 12px;
            }

            .activity-time {
                font-size: 10px;
            }
        }

        .dashboard-rooms-panel .panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
        }

        .dashboard-rooms-title-group {
            display: inline-flex;
            align-items: center;
            gap: 9px;
        }

        .dashboard-rooms-title-icon {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(34, 211, 238, 0.18), rgba(167, 139, 250, 0.18));
            border: 1px solid rgba(34, 211, 238, 0.30);
            color: var(--cyan);
            font-size: 11px;
        }

        .dashboard-rooms-title {
            font-size: 17px;
            font-weight: 700;
            line-height: 1.15;
            color: var(--ink-0);
            margin: 0;
            letter-spacing: -0.01em;
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
            border-radius: 10px;
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
            background: var(--panel-2);
            border-color: var(--line);
            color: var(--ink-0);
            transform: translateY(-1px);
        }

        .dashboard-room-list {
            display: grid;
            gap: 10px;
        }

        .dashboard-room-row {
            padding: 10px 12px 10px 18px;
            border-radius: 14px;
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
            left: 6px;
            top: 10px;
            bottom: 10px;
            width: 4px;
            border-radius: 999px;
            background: #fca5a5;
            opacity: 1;
            transition: background var(--t-base);
            /* Tambahkan glow pada baris ruangan agar seragam */
            box-shadow: 0 0 8px color-mix(in srgb, currentColor 30%, transparent);
        }

        /* Indikator status via warna garis kiri (tanpa glow, match activity rail) */
        .dashboard-room-row[data-status="online"]::before {
            background: #34d399;
        }
        .dashboard-room-row[data-status="offline"]::before {
            background: #fb7185;
        }

        /* Dashboard sections spacing */
        .app-content-inner>*+* {
            margin-top: 32px;
        }

        .dashboard-room-row:hover {
            background: var(--panel-2);
            border-color: var(--line);
            transform: translateY(-1px);
        }

        .dashboard-room-main {
            min-width: 0;
        }

        .dashboard-room-name {
            font-size: 16px;
            font-weight: 700;
            line-height: 1.15;
            color: var(--ink-0);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dashboard-room-meta {
            margin-top: 3px;
            color: var(--ink-3);
            font-size: 13px;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dashboard-room-temp {
            min-width: 76px;
            text-align: right;
            color: var(--ink-3);
            font-family: 'JetBrains Mono', monospace;
            font-size: 15px;
            font-weight: 700;
        }

        .dashboard-room-status {
            min-width: 82px;
            padding: 6px 12px;
            border-radius: 999px;
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
            background: rgba(251, 113, 133, 0.14);
            color: #fca5a5;
        }

        @media (max-width: 640px) {
            .dashboard-rooms-panel {
                padding: 16px;
            }

            .dashboard-rooms-panel .panel-header {
                flex-wrap: wrap;
                gap: 12px;
            }

            .dashboard-rooms-title {
                font-size: 17px;
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
                border-radius: 16px;
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
                border-radius: 12px;
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
                font-size: 10px;
                padding: 5px 8px;
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
                font-size: 9px;
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

        /* Angka utama selalu putih untuk hierarki yang kuat */
        .stat-card .stat-num-lg {
            color: var(--ink-0);
        }

        /* Label kecil di atas mengambil warna accent per kartu */
        .stat-card.acc-cyan .stat-label-sm,
        .stat-card.acc-cyan .stat-label {
            color: var(--cyan);
        }

        .stat-card.acc-mint .stat-label-sm,
        .stat-card.acc-mint .stat-label {
            color: var(--mint);
        }

        .stat-card.acc-lavender .stat-label-sm,
        .stat-card.acc-lavender .stat-label {
            color: var(--lavender);
        }

        .stat-card.acc-coral .stat-label-sm,
        .stat-card.acc-coral .stat-label {
            color: var(--coral);
        }

        /* Device compatibility - Tablet & Below (768px) */
        @media (max-width: 768px) {
            .dashboard-rooms-panel,
            .dashboard-activity-panel {
                padding: 16px;
            }

            .dashboard-rooms-title,
            .activity-title {
                font-size: 17px;
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
                font-size: 9px;
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

        /* Stat cards optimization for small screens (< 640px) — Mobile L */
        @media (max-width: 640px) {
            .grid.grid-cols-2.lg\:grid-cols-4 {
                gap: 12px;
            }

            .stat-card {
                padding: 14px 16px;
            }

            .stat-card .stat-label-sm,
            .stat-card .stat-label {
                font-size: 9.5px;
                letter-spacing: 0.08em;
            }

            .stat-card .stat-num-lg {
                font-size: 28px;
                margin: 6px 0 4px;
            }

            .stat-card .stat-sub,
            .stat-card .stat-meta {
                font-size: 10px;
                line-height: 1.35;
            }

            .stat-card .stat-icon {
                width: 34px;
                height: 34px;
                border-radius: 10px;
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
                font-size: 8.5px;
                letter-spacing: 0.06em;
            }

            .stat-card .stat-num-lg {
                font-size: 24px;
                margin: 4px 0 2px;
            }

            .stat-card .stat-sub,
            .stat-card .stat-meta {
                font-size: 9.5px;
                line-height: 1.3;
            }

            .stat-card .stat-icon {
                width: 30px;
                height: 30px;
                border-radius: 9px;
                font-size: 12px;
            }

            .stat-card .accent-bar {
                top: 12px;
                bottom: 12px;
            }

            .dashboard-rooms-panel,
            .dashboard-activity-panel {
                padding: 12px;
                border-radius: 16px;
            }

            .dashboard-rooms-title,
            .activity-title {
                font-size: 16px;
            }

            .dashboard-rooms-title-icon,
            .activity-title-icon {
                width: 24px;
                height: 24px;
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
                border-radius: 12px;
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
                padding: 8px 10px 8px 16px;
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

            .activity-user,
            .activity-desc {
                font-size: 11px;
            }

            .activity-time {
                font-size: 10px;
            }

            .trend-filter-select {
                font-size: 10px;
                padding: 5px 8px;
            }
        }

        /* Mobile S (≤ 360px) — extra-tight stat cards */
        @media (max-width: 360px) {
            .grid.grid-cols-2.lg\:grid-cols-4 {
                gap: 8px;
            }

            .stat-card {
                padding: 10px 12px;
            }

            .stat-card .stat-label-sm,
            .stat-card .stat-label {
                font-size: 9px;
                letter-spacing: 0.05em;
            }

            .stat-card .stat-num-lg {
                font-size: 21px;
                margin: 3px 0 2px;
            }

            .stat-card .stat-sub,
            .stat-card .stat-meta {
                font-size: 9px;
                line-height: 1.3;
            }

            .stat-card .stat-icon {
                width: 26px;
                height: 26px;
                border-radius: 8px;
                font-size: 11px;
            }

            .stat-card .accent-bar {
                top: 10px;
                bottom: 10px;
            }

            .dashboard-rooms-panel,
            .dashboard-activity-panel {
                padding: 10px;
                border-radius: 12px;
            }

            .dashboard-rooms-title,
            .activity-title {
                font-size: 14px;
            }

            .dashboard-rooms-title-icon,
            .activity-title-icon {
                width: 22px;
                height: 22px;
                font-size: 10px;
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
                padding: 8px 10px 8px 14px;
                gap: 8px;
                border-radius: 10px;
                min-height: 54px;
            }

            .dashboard-room-row::before {
                width: 3px;
                left: 5px;
                top: 7px;
                bottom: 7px;
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
                grid-template-columns: 28px 1fr;
                padding: 8px 10px 8px 14px;
            }

            .activity-icon-wrap {
                width: 26px;
                height: 26px;
                font-size: 10px;
            }

            .activity-avatar-wrap,
            .activity-avatar-img,
            .activity-avatar-fallback {
                width: 28px;
                height: 28px;
                font-size: 10px;
            }

            .activity-icon-badge {
                width: 14px;
                height: 14px;
                font-size: 7px;
                border-width: 1px;
            }

            .activity-user,
            .activity-desc {
                font-size: 10px;
            }

            .activity-time {
                font-size: 8px;
            }
        }

        /* Temperature chart height responsive */
        @media (max-width: 768px) {
            .temp-chart-wrap {
                height: 260px !important;
            }
        }

        /* Tablet (≤ 1023px): teks chart sedikit lebih kecil */
        @media (max-width: 1023px) {
            .temp-chart-panel .panel-title {
                font-size: 13px !important;
            }
            .temp-chart-panel .eyebrow {
                font-size: 9.5px !important;
            }
            .temp-chart-panel .trend-filter-select {
                font-size: 10px !important;
                padding: 4px 6px !important;
                border-radius: 7px !important;
            }
            .temp-chart-panel .trend-demo-toggle {
                font-size: 10px !important;
                padding: 4px 7px !important;
                border-radius: 7px !important;
            }
        }

        @media (max-width: 480px) {
            .temp-chart-wrap {
                height: 240px !important;
            }
            /* Panel temperatur lebih kompak di mobile */
            .temp-chart-panel {
                padding: 14px 12px !important;
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
                font-size: 8.5px !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                gap: 4px !important;
            }
            .temp-chart-panel .eyebrow i {
                font-size: 9px !important;
            }
            .temp-chart-panel .trend-filter-select {
                font-size: 9.5px !important;
                padding: 3px 5px !important;
                border-radius: 6px !important;
                min-width: 0;
            }
            .temp-chart-panel .trend-demo-toggle {
                font-size: 9.5px !important;
                padding: 3px 6px !important;
                border-radius: 6px !important;
                gap: 4px !important;
            }
            .temp-chart-panel #trendInfo {
                margin-top: 6px !important;
                font-size: 10px !important;
                line-height: 1.35 !important;
            }
        }

        @media (max-width: 360px) {
            .temp-chart-wrap {
                height: 220px !important;
            }
            .temp-chart-panel {
                padding: 12px 10px !important;
            }
            .temp-chart-panel .panel-title {
                font-size: 11px !important;
            }
            .temp-chart-panel .eyebrow {
                font-size: 8px !important;
                letter-spacing: 0.06em !important;
            }
            .temp-chart-panel .trend-filter-select {
                font-size: 9px !important;
                padding: 2px 4px !important;
                border-radius: 5px !important;
            }
            .temp-chart-panel .trend-demo-toggle {
                font-size: 9px !important;
                padding: 2px 5px !important;
                border-radius: 5px !important;
                gap: 3px !important;
            }
            .temp-chart-panel #trendInfo {
                font-size: 9.5px !important;
            }
        }

        /* Touch target optimization */
        @media (hover: none) and (pointer: coarse) {
            .dashboard-rooms-action {
                min-height: 48px;
                min-width: 48px;
            }

            .dashboard-room-row {
                padding: 12px 14px;
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
                font-size: 9px;
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

        /* Mobile S (≤ 360 px): shrink header so title fits in one row */
        @media (max-width: 360px) {
            .main-header { gap: 6px; padding-left: 10px; padding-right: 10px; }
            .main-header > .flex.items-center.gap-3 { gap: 6px; }
            .main-header > .flex.items-center.gap-2 { gap: 4px; }
            .main-header .app-header-title h1 { font-size: 13px; line-height: 1.2; }
            .main-header .app-header-title p { font-size: 9.5px; line-height: 1.2; }
            .main-header #systemStatus span:not(.dot) { display: none; }
            .main-header #systemStatus { padding: 4px 6px; }
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
                    <span id="systemStatus" class="pill pill-offline">
                        <span class="dot"></span><span>Offline</span>
                    </span>
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
                                        <p class="stat-sub">Terdaftar</p>
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
                                        <p class="stat-sub">Menyala</p>
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
                                        <p class="stat-sub">Tidak aktif</p>
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
                                            id="trendRangeLabel">Trend 1 jam terakhir</span></p>
                                    <h2 class="panel-title">Room Temperatures</h2>
                                </div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <select id="trendRange" class="trend-filter-select" title="Pilih range waktu">
                                        <option value="1h">1 Jam</option>
                                        <option value="3h">3 Jam</option>
                                        <option value="6h">6 Jam</option>
                                        <option value="24h">24 Jam</option>
                                    </select>
                                    <button id="trendDemoToggle" type="button" class="trend-demo-toggle"
                                        title="Tampilkan data contoh">
                                        <i class="fa-solid fa-flask"></i>
                                        <span>Demo Data</span>
                                    </button>
                                </div>
                            </div>
                            <div class="temp-chart-wrap" style="height:300px;position:relative;">
                                <canvas id="tempChart"></canvas>
                                <div id="tempChartEmpty" class="empty-state"
                                    style="position:absolute;inset:0;display:none;align-items:center;justify-content:center;">
                                    <div style="text-align:center;">
                                        <div class="empty-icon"><i class="fa-solid fa-temperature-empty"></i></div>
                                        <p class="empty-sub">Belum ada data suhu dalam 1 jam terakhir</p>
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
                                                <p class="dashboard-rooms-subtitle">{{ $totalRooms }} ruangan terdaftar</p>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="{{ route('rooms.overview') }}" class="dashboard-rooms-action"
                                        aria-label="Lihat semua server rooms">
                                        <span>Lihat semua</span>
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
                                            <a href="{{ route('rooms.overview') }}" class="dashboard-room-row"
                                                data-dashboard-room-id="{{ $room->id }}"
                                                data-status="{{ $status }}">
                                                <div class="dashboard-room-main">
                                                    <h3 class="dashboard-room-name">{{ $room->name }}</h3>
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
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="empty-state" style="padding:28px 12px;">
                                        <div class="empty-icon"><i class="fa-solid fa-server"></i></div>
                                        <p class="empty-title">Belum ada ruangan</p>
                                        <p class="empty-sub">Tambahkan ruangan untuk mulai monitoring</p>
                                    </div>
                                @endif
                            </section>

                            {{-- Recent Activity widget --}}
                            <section class="panel dashboard-activity-panel">
                                <div class="activity-header">
                                    <div>
                                        <h2 class="activity-title">Aktivitas Terkini</h2>
                                        <p class="activity-subtitle">{{ count($recentActivities) }} aktivitas terbaru</p>
                                    </div>
                                    <span class="activity-title-icon"><i class="fa-solid fa-bolt"></i></span>
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
                                            <p class="empty-title">Belum ada aktivitas</p>
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

        const temperatureThresholdPlugin = {
            id: 'temperatureThreshold',
            afterDraw(chart) {
                const yScale = chart.scales.y;
                const { ctx, chartArea } = chart;
                if (!yScale || !chartArea) return;

                const y = yScale.getPixelForValue(30);
                ctx.save();
                ctx.setLineDash([5, 5]);
                ctx.lineWidth = 1;
                ctx.strokeStyle = 'rgba(251,191,36,0.45)';
                ctx.beginPath();
                ctx.moveTo(chartArea.left, y);
                ctx.lineTo(chartArea.right, y);
                ctx.stroke();
                ctx.setLineDash([]);
                ctx.fillStyle = 'rgba(251,191,36,0.85)';
                ctx.font = '10px Inter, sans-serif';
                ctx.textAlign = 'right';
                ctx.fillText('30\u00b0C', chartArea.right - 2, y - 6);
                ctx.restore();
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
                window.smToast('Browser tidak mendukung notifikasi', 'error');
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
                if (perm === 'denied') window.smToast('Izin notifikasi ditolak', 'error');
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
                plugins: [glowLinePlugin, temperatureThresholdPlugin, crosshairGlowPlugin, {
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
                                color: '#94a3b8',
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
                            backgroundColor: 'rgba(7,16,31,0.95)',
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
                                color: '#64748b',
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
                                color: '#64748b',
                                font: {
                                    size: s.tickFontSize
                                },
                                maxTicksLimit: s.yMaxTicks,
                                stepSize: 2,
                                callback: v => v + '°C'
                            },
                            grid: {
                                color: 'rgba(103,232,249,0.12)'
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
                        window.smToast?.('Gagal memuat data suhu ruangan', 'error');
                    }
                });
        }

        function getTrendLimit() {
            const saved = localStorage.getItem('trendLimit');
            return saved !== null ? saved : '5';
        }

        function getTrendRange() {
            const saved = localStorage.getItem('trendRange');
            return saved !== null ? saved : '1h';
        }

        const RANGE_LABELS = {
            '1h': 'Trend 1 jam terakhir',
            '3h': 'Trend 3 jam terakhir',
            '6h': 'Trend 6 jam terakhir',
            '24h': 'Trend 24 jam terakhir',
        };

        function isTrendDemoMode() {
            return localStorage.getItem('trendDemoMode') === 'true';
        }

        function updateTrendDemoToggle() {
            const btn = document.getElementById('trendDemoToggle');
            if (!btn) return;
            const active = isTrendDemoMode();
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            const label = btn.querySelector('span');
            if (label) label.textContent = active ? 'Demo ON' : 'Demo Data';
        }

        function padTime(value) {
            return String(value).padStart(2, '0');
        }

        function makeDemoLabels(range) {
            const config = {
                '1h': { slots: 12, interval: 5, hourly: false },
                '3h': { slots: 18, interval: 10, hourly: false },
                '6h': { slots: 24, interval: 15, hourly: false },
                '24h': { slots: 24, interval: 60, hourly: true },
            }[range] || { slots: 12, interval: 5, hourly: false };

            const now = new Date();
            const labels = [];
            for (let i = config.slots - 1; i >= 0; i--) {
                const date = new Date(now);
                date.setMinutes(now.getMinutes() - (i * config.interval), 0, 0);
                if (config.hourly) {
                    labels.push(`${padTime(date.getHours())}:00`);
                } else {
                    labels.push(`${padTime(date.getHours())}:${padTime(date.getMinutes())}`);
                }
            }

            return labels;
        }

        function makeDemoSeries(slots, baseTemp, swing, offset = 0) {
            return Array.from({ length: slots }, (_, i) => {
                const wave = Math.sin((i + offset) / 2.2) * swing;
                const drift = i * 0.04;
                return Number((baseTemp + wave + drift).toFixed(1));
            });
        }

        function generateDemoTrendPayload(range, limit) {
            const labels = makeDemoLabels(range);
            const slots = labels.length;
            const count = Math.min(Math.max(Number(limit) || 5, 1), 5);
            const palette = ['#fb7185', '#fbbf24', '#4dd4ff', '#a78bfa', '#34d399'];
            const rooms = [
                { room: 'Server A', base: 24.6, swing: 0.7, offline: false },
                { room: 'Server B', base: 26.1, swing: 0.9, offline: false },
                { room: 'Network Rack', base: 28.4, swing: 1.1, offline: false },
                { room: 'Storage Cold', base: 22.9, swing: 0.5, offline: false },
                { room: 'Backup Room', base: 25.2, swing: 0.6, offline: true },
            ].slice(0, count);

            const datasets = rooms.map((room, idx) => {
                let data = makeDemoSeries(slots, room.base, room.swing, idx);
                if (room.offline) {
                    data = data.map((value, pointIdx) => pointIdx >= slots - 4 ? null : value);
                }

                const visibleValues = data.filter(value => value !== null && !Number.isNaN(value));
                const lastValue = visibleValues.length ? visibleValues[visibleValues.length - 1] : null;

                return {
                    room: room.room,
                    room_id: `demo-${idx + 1}`,
                    current_temp: room.offline ? null : lastValue,
                    last_temp: lastValue,
                    is_offline: room.offline,
                    offline_since: room.offline ? labels[Math.max(0, slots - 5)] : null,
                    data,
                    color: palette[idx % palette.length],
                };
            });

            return {
                labels,
                datasets,
                total_rooms: rooms.length,
                shown: datasets.length,
                limit: count,
                range,
                interval_minutes: null,
                demo: true,
            };
        }

        function refreshTrendChart(showLoader = false) {
            if (!tempChart) return;

            const limit = getTrendLimit();
            const range = getTrendRange();
            const demoMode = isTrendDemoMode();

            const labelEl = document.getElementById('trendRangeLabel');
            if (labelEl) labelEl.textContent = demoMode ? `Demo ${range.toUpperCase()}` : (RANGE_LABELS[range] || RANGE_LABELS['1h']);

            if (showLoader) {
                const spinner = document.getElementById('tempChartLoading');
                if (spinner) spinner.style.display = 'flex';
            }

            const trendRequest = demoMode ?
                Promise.resolve(generateDemoTrendPayload(range, limit)) :
                fetch(`/temperature/trend?limit=${encodeURIComponent(limit)}&range=${encodeURIComponent(range)}`)
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
                            const rangeText = { '1h': '1 jam terakhir', '3h': '3 jam terakhir', '6h': '6 jam terakhir', '24h': '24 jam terakhir' };
                            emptyTextEl.textContent = `Belum ada data suhu dalam ${rangeText[range] || range}`;
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
                        infoEl.textContent = demoMode ?
                            `Demo: ${shown} room contoh, ${offlineCount} offline. Data asli aman.` :
                            `${onlineCount} online, ${offlineCount} offline. Grafik memakai data historis tercatat.`;
                    }
                })
                .catch(() => {
                    document.getElementById('tempChartLoading')?.style.setProperty('display', 'none');
                    window.smToast?.('Gagal memuat data grafik suhu', 'error');
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
                        window.smToast?.('Gagal memuat status perangkat', 'error');
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
                        window.smToast?.('Gagal memuat statistik dashboard', 'error');
                    }
                });
        }

        function setSystemStatus(online) {
            const el = document.getElementById('systemStatus');
            if (!el) return;
            el.className = 'pill ' + (online ? 'pill-online' : 'pill-offline');
            el.innerHTML = `<span class="dot"></span><span>${online ? 'Online' : 'Offline'}</span>`;
        }
        window.addEventListener('online', () => setSystemStatus(true));
        window.addEventListener('offline', () =>
            setSystemStatus(false));

        document.addEventListener('DOMContentLoaded', () => {
            initChart();

            // Adapt chart sizing on viewport changes (rotate / resize)
            let resizeTimer = null;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(applyChartSizing, 150);
            });

            setSystemStatus(navigator.onLine);
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

            const demoToggle = document.getElementById('trendDemoToggle');
            updateTrendDemoToggle();
            if (demoToggle) {
                demoToggle.addEventListener('click', () => {
                    localStorage.setItem('trendDemoMode', isTrendDemoMode() ? 'false' : 'true');
                    updateTrendDemoToggle();
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
                    `<img src="${escapeHtml(item.user_avatar)}" alt="${name}" class="activity-avatar-img">` :
                    `<div class="activity-avatar-fallback">${initial}</div>`;

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
