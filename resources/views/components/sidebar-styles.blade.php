{{-- Layout shim: sidebar/header behavior. Most styling lives in app.css. --}}
<style>
    /* Hide all visual scrollbars (scroll wheel/touch tetap berfungsi) */
    * {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    *::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    /* 2025 Modern Wallpaper — clean, harmonious color palette with much darker background */
    html, body { height: 100%; overflow: hidden; }
    body {
        background:
            /* Subtle cyan glow from top — primary accent */
            radial-gradient(800px 400px at 50% -20%, rgba(78, 215, 255, 0.08), transparent 60%),
            /* Complementary indigo glow from bottom-right — secondary accent */
            radial-gradient(600px 350px at 85% 110%, rgba(139, 162, 255, 0.04), transparent 65%),
            /* Darkened gradient overlay */
            linear-gradient(135deg,
                rgba(4, 7, 16, 0.86) 0%,
                rgba(6, 11, 28, 0.84) 25%,
                rgba(7, 14, 34, 0.83) 50%,
                rgba(5, 10, 26, 0.84) 75%,
                rgba(4, 7, 20, 0.86) 100%
            ),
            /* Wallpaper image — texture underneath */
            url('/images/wallpaper.jpeg') center/cover no-repeat fixed !important;
        background-blend-mode: multiply;
    }

    /* Modern color system — harmonious palette */
    :root {
        --panel-1: rgba(13, 21, 44, 0.72) !important;
        --panel-2: rgba(15, 25, 52, 0.78) !important;
        --bg-1:    #0d1530 !important;
        --cyan-d-rgb: 34 184 230;
    }

    /* Glass effect on all panels */
    .panel, .ac-panel, .stat-card, .card {
        -webkit-backdrop-filter: blur(12px);
        backdrop-filter: blur(12px);
    }

    /* Main content area with much darker overlay */
    .main-content {
        background: transparent;
    }

    /* Backwards-compat aliases for legacy class names used across pages */
    .layout         { display: flex; min-height: 100vh; width: 100vw; position: relative; }
    .main-content   {
        margin-left: 248px;
        width: calc(100% - 248px);
        height: 100vh;
        display: flex;
        flex-direction: column;
        transition: margin-left .25s var(--ease), width .25s var(--ease);
        overflow: hidden;
    }
    .sidebar.close ~ .main-content,
    .app-sidebar.collapsed ~ .main-content {
        margin-left: 76px;
        width: calc(100% - 76px);
    }

    .main-header {
        flex-shrink: 0;
        height: 64px;
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 24px;
        background:
            radial-gradient(950px 320px at 50% -40%, rgb(var(--cyan-d-rgb) / 0.12), transparent 65%),
            radial-gradient(480px 320px at 85% 125%, rgb(var(--cyan-rgb) / 0.08), transparent 65%),
            linear-gradient(180deg, rgba(13, 22, 46, 0.98) 0%, rgba(10, 17, 38, 0.99) 100%) !important;
        border-bottom: 1px solid rgb(var(--cyan-d-rgb) / 0.12) !important;
        box-shadow: 0 0 32px rgba(0, 0, 0, 0.40), inset 0 -1px 0 rgb(var(--cyan-d-rgb) / 0.08);
        color: var(--ink-0);
        position: sticky; top: 0;
        z-index: 30;
    }
    /* Top accent line — vibrant cyan glow */
    .main-header::before {
        content: '';
        position: absolute;
        top: 0; left: 16%; right: 16%;
        height: 2px;
        background: linear-gradient(90deg, transparent, rgb(var(--cyan-d-rgb) / 0.45), transparent);
        filter: blur(0.8px);
        pointer-events: none;
        z-index: 2;
    }
    /* Bottom accent line — complementary indigo glow */
    .main-header::after {
        content: '';
        position: absolute;
        left: 16%; right: 16%; bottom: -1px;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgb(var(--cyan-rgb) / 0.20), transparent);
        pointer-events: none;
    }
    @media (max-width: 1024px) { .main-header { padding: 0 16px; } }

    /* Header title */
    .main-header .app-header-title h1 {
        font-size: 16px !important;
        font-weight: 700 !important;
        color: var(--ink-0) !important;
        letter-spacing: -0.02em !important;
        margin: 0;
        line-height: 1.2;
    }
    .main-header .app-header-title p {
        font-size: 12px !important;
        color: var(--ink-3) !important;
        margin: 2px 0 0 !important;
        letter-spacing: 0.02em;
    }

    /* Right-cluster (notification bell + status pill) */
    .main-header > div:last-child {
        gap: 10px !important;
    }

    /* 2026 modern notification bell with vibrant interactions */
    #notifBellBtn,
    .main-header .btn-icon {
        width: 38px !important;
        height: 38px !important;
        border-radius: var(--r-md) !important;
        background: rgb(var(--cyan-d-rgb) / 0.08) !important;
        border: 1px solid rgb(var(--cyan-d-rgb) / 0.16) !important;
        color: #7fa0c8 !important;
        transition: all var(--t-base) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        font-size: 14px !important;
        line-height: 1 !important;
    }
    #notifBellBtn:hover,
    .main-header .btn-icon:hover {
        background: rgb(var(--cyan-d-rgb) / 0.15) !important;
        border-color: rgb(var(--cyan-d-rgb) / 0.38) !important;
        color: var(--cyan-d) !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px -2px rgb(var(--cyan-d-rgb) / 0.40);
    }
    .main-header .btn-icon i {
        font-size: 14px !important;
    }

    /* 2026 vibrant notification badge */
    #notifBadge,
    .notif-badge {
        background: linear-gradient(135deg, var(--danger), var(--danger-d)) !important;
        color: #fff !important;
        font-size: 10px !important;
        font-weight: 700 !important;
        min-width: 18px;
        height: 18px !important;
        border-radius: var(--r-full) !important;
        border: 2px solid rgba(8, 12, 28, 0.97) !important;
        box-shadow: 0 6px 16px -2px rgb(var(--danger-rgb) / 0.60) !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        top: -4px !important;
        right: -4px !important;
        padding: 0 4px;
    }

    .page-body {
        flex: 1;
        overflow-y: auto;
        scroll-behavior: smooth;
        padding-bottom: 24px;
        min-height: 0;
    }

    /* Mobile sidebar */
    @media (max-width: 1024px) {
        .main-content   { margin-left: 0 !important; width: 100% !important; }
        .app-sidebar    { position: fixed; left: 0; top: 0; height: 100vh; transform: translateX(-100%); width: 280px !important; z-index: 50; transition: transform var(--t-slow); }
        .app-sidebar.open { transform: translateX(0); box-shadow: 0 24px 48px rgba(0,0,0,.40); }
        .sidebar-toggle.desktop-only { display: none !important; }
    }

    /* Phone (≤ 480px): sidebar setengah layar + nav-link kompak */
    @media (max-width: 480px) {
        .app-sidebar {
            width: 50vw !important;
            min-width: 200px !important;
            max-width: 260px !important;
        }

        /* Kecilkan nav supaya label panjang muat 1 baris */
        .app-sidebar .nav-link {
            padding: 8px 10px !important;
            font-size: 12px !important;
            gap: 9px !important;
        }
        .app-sidebar .nav-link i {
            width: 24px !important;
            height: 24px !important;
            font-size: 11px !important;
            border-radius: var(--r-sm) !important;
        }

        /* Section heading (OVERVIEW / MANAGEMENT / ADMINISTRATION) */
        .app-sidebar .nav-section-label {
            font-size: 10px !important;
            letter-spacing: 0.12em !important;
        }

        /* Footer user info compact */
        .app-sidebar .sidebar-footer .profile-info .name { font-size: 12px !important; }
        .app-sidebar .sidebar-footer .profile-info .role { font-size: 10px !important; }
    }

    /* Show toggle button on mobile/tablet only */
    @media (max-width: 1024px) {
        .main-header .lg\:hidden { display: inline-flex !important; visibility: visible !important; }
        .main-header button[onclick*="toggleSidebar"] { display: inline-flex !important; }
    }

    /* Hamburger button — ikon polos tanpa kotak (global, semua halaman) */
    .main-header button[onclick*="toggleSidebar"] {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        width: auto !important;
        height: auto !important;
        padding: 6px !important;
        color: var(--ink-0) !important;
        font-size: 18px !important;
        border-radius: 0 !important;
    }
    .main-header button[onclick*="toggleSidebar"] i {
        font-size: 18px !important;
    }
    .main-header button[onclick*="toggleSidebar"]:hover {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        color: var(--cyan) !important;
        transform: none !important;
    }

    /* Mobile (≤ 480px): hamburger lebih kecil */
    @media (max-width: 480px) {
        .main-header button[onclick*="toggleSidebar"],
        .main-header button[onclick*="toggleSidebar"] i {
            font-size: 16px !important;
        }
        .main-header button[onclick*="toggleSidebar"] {
            padding: 4px !important;
        }
    }

    /* Mobile S (≤ 480px): paling kecil */
    @media (max-width: 480px) {
        .main-header button[onclick*="toggleSidebar"],
        .main-header button[onclick*="toggleSidebar"] i {
            font-size: 14px !important;
        }
        .main-header button[onclick*="toggleSidebar"] {
            padding: 3px !important;
        }
    }

    /* Hide mobile/tablet toggle button on desktop (≥1024px) */
    @media (min-width: 1025px) {
        .main-header .lg\:hidden,
        .main-header button[onclick*="toggleSidebar"] {
            display: none !important;
        }
    }

    /* 2026 modern backdrop with vibrant glassmorphism */
    #overlay {
        position: fixed; inset: 0; z-index: 40;
        background:
            radial-gradient(500px 350px at 50% 50%, rgb(var(--cyan-d-rgb) / 0.03), transparent 70%),
            rgba(0, 0, 0, 0.64);
        -webkit-backdrop-filter: blur(6px);
        backdrop-filter: blur(6px);
        opacity: 0; pointer-events: none;
        transition: opacity .25s var(--ease);
    }
    #overlay.active { opacity: 1; pointer-events: auto; }

    .custom-bg {
        position: fixed;
        inset: 0;
        z-index: -1;
        background:
            radial-gradient(60% 50% at 12% 0%, rgb(var(--cyan-rgb) / 0.035), transparent 60%),
            radial-gradient(50% 45% at 88% 12%, rgb(var(--lavender-rgb) / 0.03), transparent 65%),
            radial-gradient(55% 50% at 50% 100%, rgb(var(--mint-rgb) / 0.022), transparent 60%),
            var(--bg-0);
        pointer-events: none;
    }

    /* 2026 modern sidebar with vibrant accents */
    .app-sidebar {
        background:
            /* Vibrant cyan glow from top */
            radial-gradient(600px 400px at 50% -25%, rgb(var(--cyan-d-rgb) / 0.12), transparent 65%),
            /* Vibrant indigo glow from bottom */
            radial-gradient(480px 320px at 15% 125%, rgb(var(--cyan-rgb) / 0.08), transparent 65%),
            /* Enhanced dark blue gradient */
            linear-gradient(180deg, rgba(13, 22, 46, 0.98), rgba(10, 17, 38, 0.99)) !important;
        border-right: 1px solid rgb(var(--cyan-d-rgb) / 0.12) !important;
        box-shadow: 0 0 32px rgba(0, 0, 0, 0.40), inset -1px 0 0 rgb(var(--cyan-d-rgb) / 0.08);
    }

    /* Top accent line — vibrant and modern */
    .app-sidebar::before {
        content: '';
        position: absolute;
        top: 0; left: 16%; right: 16%;
        height: 2px;
        background: linear-gradient(90deg, transparent, rgb(var(--cyan-d-rgb) / 0.40), transparent);
        filter: blur(0.8px);
        pointer-events: none;
        z-index: 2;
    }

    /* 2026 brand with vibrant, modern aesthetic */
    .brand {
        height: 64px !important;
        border-bottom: 1px solid rgb(var(--cyan-d-rgb) / 0.14) !important;
        position: relative;
        background: linear-gradient(180deg, rgba(20, 32, 60, 0.6), rgba(14, 22, 46, 0.20));
    }
    .brand::after {
        content: '';
        position: absolute;
        left: 18px; right: 18px; bottom: -1px;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgb(var(--cyan-d-rgb) / 0.28), rgb(var(--cyan-rgb) / 0.14), transparent);
    }
    .brand-logo {
        background: conic-gradient(from 220deg, var(--cyan-d), var(--cyan), #70f5d0, var(--cyan-d)) !important;
        box-shadow: 0 12px 40px -6px rgb(var(--cyan-d-rgb) / 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.35) !important;
        position: relative;
    }
    .brand-logo::after {
        content: '';
        position: absolute;
        inset: 3px;
        border-radius: var(--r-sm);
        background: rgba(7, 12, 30, 0.96);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .brand-logo i {
        position: relative;
        z-index: 2;
        color: var(--cyan-d);
        filter: drop-shadow(0 0 8px rgb(var(--cyan-d-rgb) / 0.65));
    }
    .brand-text .sub {
        background: linear-gradient(90deg, var(--cyan-d), var(--cyan));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        font-weight: 700 !important;
    }

    /* 2026 nav section labels with vibrant accents */
    .nav-section-label {
        font-size: 10px !important;
        letter-spacing: 0.16em !important;
        color: #85a0cc !important;
        padding: 14px 12px 8px !important;
        margin-top: 8px !important;
        display: flex;
        align-items: center;
        gap: 8px;
        text-transform: uppercase;
        font-weight: 600;
    }
    .nav-section-label::before {
        content: '';
        width: 14px;
        height: 1px;
        background: linear-gradient(90deg, rgb(var(--cyan-d-rgb) / 0.50), transparent);
        flex-shrink: 0;
    }

    /* 2026 nav links with vibrant, modern interactions */
    .nav-list { gap: 3px !important; }
    .nav-link {
        padding: 10px 12px !important;
        border-radius: var(--r-lg) !important;
        font-size: 13px !important;
        position: relative;
        transition: all var(--t-base) !important;
    }
    .nav-link i {
        width: 28px !important; height: 28px !important;
        border-radius: var(--r-sm);
        background: rgb(var(--cyan-d-rgb) / 0.06);
        border: 1px solid rgb(var(--cyan-d-rgb) / 0.10);
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 12px !important;
        line-height: 1 !important;
        text-align: center !important;
        padding: 0 !important;
        margin: 0 !important;
        color: #7fa0c8 !important;
        transition: all var(--t-base) !important;
        flex-shrink: 0;
        box-sizing: border-box !important;
    }
    .nav-link i::before {
        display: block;
        line-height: 1;
        text-align: center;
    }

    .nav-link:hover {
        background: rgb(var(--cyan-d-rgb) / 0.10) !important;
        color: var(--ink-0) !important;
        transform: translateX(2px);
    }
    .nav-link:hover i {
        background: linear-gradient(135deg, rgb(var(--cyan-d-rgb) / 0.18), rgb(var(--cyan-rgb) / 0.10));
        border-color: rgb(var(--cyan-d-rgb) / 0.35);
        color: var(--cyan-d) !important;
    }

    .nav-link.active {
        background:
            linear-gradient(90deg, rgb(var(--cyan-d-rgb) / 0.14) 0%, rgb(var(--cyan-d-rgb) / 0.06) 100%) !important;
        color: var(--ink-0) !important;
        font-weight: 600 !important;
        box-shadow: inset 0 1px 0 rgb(var(--cyan-d-rgb) / 0.12);
    }
    .nav-link.active i {
        background: linear-gradient(135deg, rgb(var(--cyan-d-rgb) / 0.24), rgb(var(--cyan-rgb) / 0.16));
        border-color: rgb(var(--cyan-d-rgb) / 0.45);
        color: var(--cyan-d) !important;
        box-shadow: 0 0 16px -2px rgb(var(--cyan-d-rgb) / 0.50);
    }
    .nav-link.active::before {
        width: 3px !important;
        background: linear-gradient(180deg, var(--cyan-d), var(--cyan)) !important;
        top: 10px !important;
        bottom: 10px !important;
        border-radius: 0 3px 3px 0 !important;
        box-shadow: 0 0 14px rgb(var(--cyan-d-rgb) / 0.55);
    }

    /* Collapsed sidebar — icon-only state */
    .app-sidebar.collapsed .nav-link i { margin: 0 auto; }

    /* 2026 modern sidebar footer with vibrant accents */
    .sidebar-footer {
        border-top: 1px solid rgb(var(--cyan-d-rgb) / 0.12) !important;
        position: relative;
        background: linear-gradient(180deg, rgba(13, 22, 46, 0.08) 0%, rgba(0, 0, 0, 0.25) 100%);
    }
    .sidebar-footer::before {
        content: '';
        position: absolute;
        left: 18px; right: 18px; top: -1px;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgb(var(--cyan-d-rgb) / 0.22), rgb(var(--cyan-rgb) / 0.12), transparent);
    }

    .profile-full {
        border-radius: var(--r-lg) !important;
        padding: 8px 10px !important;
        transition: all var(--t-base) !important;
    }
    .profile-full:hover {
        background: rgb(var(--cyan-d-rgb) / 0.10) !important;
    }
    .profile-full .avatar {
        transition: transform var(--t-base), box-shadow var(--t-base);
    }
    .profile-full:hover .avatar {
        transform: scale(1.10);
        box-shadow: 0 8px 24px -4px rgb(var(--cyan-d-rgb) / 0.50);
    }
    .profile-info .role {
        font-size: 10px !important;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #85a0cc !important;
        font-weight: 600;
        margin-top: 2px;
    }
    .icon-btn.danger {
        background: rgb(var(--danger-rgb) / 0.12) !important;
        border: 1px solid rgb(var(--danger-rgb) / 0.24) !important;
        color: var(--danger) !important;
        border-radius: var(--r-sm) !important;
        transition: all var(--t-base);
    }
    .icon-btn.danger:hover {
        background: rgb(var(--danger-rgb) / 0.18) !important;
        border-color: rgb(var(--danger-rgb) / 0.40) !important;
        transform: translateY(-1px);
    }

    /* Mobile polish: keep operational labels readable without changing layout. */
    @media (max-width: 480px) {
        .main-header .app-header-title p,
        .stat-card .stat-sub,
        .nlist-item .text-mono,
        .profile-info-label {
            font-size: 10px !important;
            line-height: 1.3 !important;
        }

        .stat-card .stat-label-sm,
        .label-tag,
        .ac-mini .lbl,
        .notif-msg,
        .notif-time,
        .room-status-pill,
        .badge-role {
            font-size: 10px !important;
            line-height: 1.3 !important;
            letter-spacing: 0.05em !important;
        }

        .tbl-toolbar .btn,
        .modal-footer .btn,
        .selector-bar .btn-icon,
        .user-card-actions .btn-icon {
            min-height: 36px !important;
        }
    }

    @media (max-width: 480px) {
        .main-header .app-header-title p {
            font-size: 10px !important;
        }

        .stat-card .stat-label-sm,
        .stat-card .stat-sub,
        .label-tag,
        .ac-mini .lbl,
        .nlist-item .text-mono {
            font-size: 10px !important;
        }
    }
</style>
