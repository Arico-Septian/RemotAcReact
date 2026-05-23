<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>User Management — SmartAC</title>
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite('resources/js/app.js')
    @include('components.sidebar-styles')
    <style>
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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--panel-1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }

        th {
            padding: 12px 16px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--ink-3);
        }

        tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            transition: background var(--t-fast);
            height: auto;
            min-height: 56px;
        }

        tbody tr:hover {
            background: var(--panel-1);
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        td {
            padding: 14px 16px;
            vertical-align: middle;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            height: 100%;
        }

        .user-info {
            min-width: 0;
            flex: 1;
        }

        .user-name {
            font-size: 14.5px;
            font-weight: 600;
            color: var(--ink-0);
            margin: 0;
            line-height: 1.2;
        }

        .user-handle {
            font-size: 11.5px;
            color: var(--ink-3);
            margin: 2px 0 0;
            line-height: 1.2;
        }

        .user-email {
            font-size: 11px;
            color: var(--ink-4);
            margin: 1px 0 0;
            line-height: 1.2;
        }

        /* Unify user-table row padding with logs table (14 × 18) */
        .user-table td {
            padding: 14px 18px;
            vertical-align: middle;
        }

        /* Role color badges */
        .badge-role {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            height: 28px;
            min-width: 90px;
            white-space: nowrap;
            vertical-align: middle;
        }

        .badge-role.admin {
            background: var(--coral-soft);
            color: var(--coral);
            border: 1px solid var(--coral-soft-2);
        }

        .badge-role.operator {
            background: var(--amber-soft);
            color: var(--amber);
            border: 1px solid var(--amber-soft-2);
        }

        .badge-role.user {
            background: var(--cyan-soft);
            color: var(--cyan);
            border: 1px solid var(--cyan-soft-2);
        }

        .user-avatar-sm {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
            color: var(--bg-1);
            flex-shrink: 0;
        }

        .status-cell {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            line-height: 1;
            color: var(--ink-2);
            justify-content: center;
            vertical-align: middle;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
            background: var(--ink-3);
            margin: 0;
            padding: 0;
        }

        .status-dot.online {
            background: var(--mint);
        }

        .status-dot.active {
            background: var(--mint);
        }

        .actions-cell {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            justify-content: flex-end;
            vertical-align: middle;
        }

        /* Table column alignment */
        .user-table th:nth-child(2),
        .user-table td:nth-child(2) {
            text-align: center;
            vertical-align: middle;
            padding: 14px 12px;
        }

        .user-table th:nth-child(3),
        .user-table td:nth-child(3) {
            text-align: center;
            vertical-align: middle;
            padding: 14px 12px;
        }

        .user-table th:nth-child(4),
        .user-table td:nth-child(4) {
            text-align: right;
            vertical-align: middle;
            padding: 14px 24px 14px 12px;
        }

        .user-table th {
            padding: 14px 18px;
            font-size: 11.5px;
            letter-spacing: 0.1em;
            color: var(--ink-0);
        }

        @media (max-width: 720px) {
            th, td {
                padding: 10px 12px;
                font-size: 12px;
            }

            .user-table td:nth-child(4) {
                padding-right: 12px;
            }

            .user-avatar-sm {
                width: 38px;
                height: 38px;
                font-size: 14px;
            }
        }

        /* Toolbar responsiveness for tablet and below */
        @media (max-width: 768px) {
            .tbl-toolbar {
                gap: 6px;
                padding: 8px 10px;
            }

            .tbl-toolbar > form {
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

            .tbl-toolbar .btn {
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

        /* Very small screens (< 480px) — wrap toolbar to 2 rows: search full width, filters + Add on row 2 */
        @media (max-width: 480px) {
            .tbl-toolbar {
                padding: 8px;
                gap: 8px;
                flex-wrap: wrap;
                row-gap: 8px;
            }

            .tbl-toolbar > form {
                flex: 1 1 100%;
                min-width: 0;
            }

            .tbl-toolbar > div {
                display: flex;
                gap: 6px;
                align-items: center;
                flex: 1 1 100%;
                justify-content: flex-start;
                flex-shrink: 0;
            }

            /* Unify height across search / segmented / Add User — 36 px */
            .tbl-toolbar .search-input {
                width: 100%;
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

            .tbl-toolbar .btn span { display: inline; }
            .tbl-toolbar .btn i { margin-right: 4px; font-size: 11px; }

            .search-input input::placeholder {
                color: var(--ink-3);
            }

            .search-input i {
                font-size: 12px;
                left: 12px;
            }
        }

        /* Filter chips */
        .filter-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px 0;
            align-items: center;
            border-bottom: 1px solid var(--line-soft);
        }

        .filter-chips span {
            font-size: 12px;
            color: var(--ink-3);
            font-weight: 500;
        }

        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: rgba(77, 212, 255, 0.1);
            border: 1px solid rgba(77, 212, 255, 0.25);
            border-radius: 999px;
            font-size: 12px;
            color: var(--cyan);
        }

        .filter-chip button {
            background: none;
            border: none;
            color: var(--cyan);
            cursor: pointer;
            padding: 0;
            font-size: 10px;
            opacity: 0.7;
        }

        .filter-chip button:hover {
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

        /* Sortable table headers */
        .user-table th {
            cursor: pointer;
            user-select: none;
            position: relative;
            transition: background 0.12s ease;
        }

        .user-table th:hover {
            background: var(--panel-2);
        }

        .user-table th.sortable::after {
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

        .user-table th.sort-asc::after {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12" fill="%234dd4ff"><path d="M6 1L3 4h6z"/></svg>');
            opacity: 1;
        }

        .user-table th.sort-desc::after {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12" fill="%234dd4ff"><path d="M6 11L3 8h6z"/></svg>');
            opacity: 1;
        }

        /* Mobile user list — clean simple layout */
        .user-cards {
            display: none;
        }

        @media (max-width: 640px) {
            .user-cards {
                display: flex;
                flex-direction: column;
                width: 100%;
            }

            /* Sama ukuran dengan log activity row */
            .user-card {
                padding: 10px 12px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                display: flex;
                align-items: center;
                gap: 10px;
                width: 100%;
                min-height: 56px;
            }
            .user-card:last-child {
                border-bottom: none;
            }

            /* Header & footer wrappers transparent — children jadi direct child .user-card */
            .user-card-header,
            .user-card-footer {
                display: contents;
            }

            .user-card-info {
                display: flex;
                align-items: center;
                gap: 10px;
                flex: 1;
                min-width: 0;
            }

            .user-card .user-avatar-sm {
                width: 34px !important;
                height: 34px !important;
                border-radius: 9px !important;
                font-size: 13px !important;
                font-weight: 600 !important;
                flex-shrink: 0;
            }

            .user-card-name {
                display: flex;
                flex-direction: column;
                gap: 1px;
                min-width: 0;
                overflow: hidden;
            }

            .user-card-name-text {
                font-size: 13px;
                font-weight: 600;
                color: var(--ink-0);
                line-height: 1.2;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .user-card-handle {
                font-size: 10.5px;
                color: var(--ink-3);
                line-height: 1.2;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* Email disembunyikan di single-row */
            .user-card-name > span:nth-child(3) {
                display: none !important;
            }

            .user-card .badge-role {
                font-size: 9px !important;
                padding: 3px 6px !important;
                height: auto !important;
                min-width: 0 !important;
                border-radius: 5px !important;
                letter-spacing: 0.04em !important;
                white-space: nowrap;
                flex-shrink: 0;
            }

            /* Status — teks Online/Offline */
            .user-card-status {
                display: inline-flex;
                align-items: center;
                font-size: 10px;
                font-weight: 600;
                color: var(--ink-3);
                text-transform: uppercase;
                letter-spacing: 0.04em;
                flex-shrink: 0;
            }

            /* Dot disembunyikan, hanya teks */
            .user-card-status .status-dot {
                display: none;
            }

            /* Color berdasarkan parent status (online/offline) */
            .user-card-status:has(.status-dot.online) {
                color: var(--mint);
            }

            .user-card-actions {
                display: flex;
                gap: 4px;
                justify-content: flex-end;
                flex-shrink: 0;
            }

            .user-card-actions .btn-icon {
                width: 30px;
                height: 30px;
                font-size: 10.5px;
                border-radius: 8px;
            }

            .user-table {
                display: none;
            }
        }

        /* Page sections spacing */
        .app-content-inner > * + * {
            margin-top: 32px;
        }

        /* Tablet & desktop (≥ 481 px): unify search / segmented / Add User height to 40 px */
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
                padding: 0 16px;
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
            .stat-card .stat-label-sm { font-size: 8.5px; letter-spacing: 0.06em; }
            .stat-card .stat-num-lg { font-size: 24px; margin: 4px 0 2px; }
            .stat-card .stat-sub { font-size: 9.5px; line-height: 1.3; }
            .stat-card .stat-icon { width: 30px; height: 30px; border-radius: 9px; font-size: 12px; }
            .stat-card .accent-bar { top: 12px; bottom: 12px; }
        }

        /* Mobile S (≤ 360 px): shrink header + toolbar so everything fits */
        @media (max-width: 360px) {
            .main-header { gap: 6px; padding-left: 10px; padding-right: 10px; }
            .main-header > .flex.items-center.gap-3 { gap: 6px; }
            .main-header > .flex.items-center.gap-2 { gap: 4px; }
            .main-header .app-header-title h1 { font-size: 13px; line-height: 1.2; }
            .main-header .app-header-title p { font-size: 9.5px; line-height: 1.2; }
            .main-header #systemStatus span:not(.dot) { display: none; }
            .main-header #systemStatus { padding: 4px 6px; }
            .main-header .btn-icon { width: 32px; height: 32px; }

            /* User card — tighter at 320 */
            .user-card { padding: 12px 14px; gap: 8px; }
            .user-card-info { gap: 10px; }
            .user-card .user-avatar-sm {
                width: 36px !important;
                height: 36px !important;
                font-size: 13px !important;
                border-radius: 9px !important;
            }
            .user-card-name-text { font-size: 13px; }
            .user-card-handle { font-size: 10.5px; }
            .user-card .badge-role { font-size: 9px !important; padding: 3px 6px !important; }
            .user-card-actions .btn-icon { width: 32px; height: 32px; font-size: 11px; }

            /* Stats grid: keep 2-col but compact each card so text doesn't wrap */
            .grid.grid-cols-2.lg\:grid-cols-4 { gap: 8px !important; }
            .stat-card { padding: 10px 12px !important; }
            .stat-card .stat-label-sm { font-size: 9px !important; letter-spacing: 0.05em !important; }
            .stat-card .stat-num-lg { font-size: 21px !important; margin: 3px 0 2px !important; line-height: 1.1 !important; }
            .stat-card .stat-sub { font-size: 9px !important; line-height: 1.3 !important; }
            .stat-card .stat-icon { width: 26px !important; height: 26px !important; border-radius: 8px !important; font-size: 11px !important; }
            .stat-card .accent-bar { top: 10px !important; bottom: 10px !important; }

            /* User card: tighter padding, fonts match log card */
            .user-card { padding: 12px 14px; gap: 8px; }

            /* Toolbar at 320 px: stack to 3 rows so segmented + Add User each get full width */
            .tbl-toolbar > div {
                flex-wrap: wrap;
                row-gap: 6px;
            }
            .tbl-toolbar > div > .segmented { flex: 1 1 100%; }
            .tbl-toolbar > div > .btn { flex: 1 1 100%; }
            .tbl-toolbar .segmented .seg { font-size: 10px; padding: 0 4px; }
        }

        /* Compact Add User modal on tablets */
        @media (min-width: 601px) and (max-width: 900px) {
            #modal.modal-backdrop {
                padding: 12px;
            }

            #modal .modal {
                max-width: 390px;
                border-radius: 16px;
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
                line-height: 1.15;
            }

            #modal .modal-header .sub {
                font-size: 10.5px;
                line-height: 1.3;
                margin-top: 5px;
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
                letter-spacing: 0.045em;
            }

            #modal .input {
                min-height: 36px;
                padding: 0 12px;
                border-radius: 10px;
                font-size: 11.5px;
            }

            #modal .field-hint {
                font-size: 10px !important;
                line-height: 1.3;
                margin-top: 2px !important;
            }

            #modal .modal-footer {
                padding: 7px 18px 14px;
                gap: 8px;
            }

            #modal .modal-footer .btn {
                min-height: 34px;
                padding: 0 14px;
                font-size: 11.5px;
            }
        }

        /* Compact Add User modal on mobile S/M/L */
        @media (max-width: 600px) {
            #modal.modal-backdrop {
                padding: 10px;
            }

            #modal .modal {
                max-width: min(330px, calc(100vw - 44px));
                border-radius: 16px;
            }

            #modal .modal-header {
                padding: 12px 14px 4px;
                gap: 6px;
            }

            #modal .eyebrow {
                font-size: 9px;
                margin-bottom: 3px;
            }

            #modal .modal-header h2 {
                font-size: 14px;
                line-height: 1.1;
            }

            #modal .modal-header .sub {
                font-size: 10px;
                line-height: 1.25;
                margin-top: 4px;
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
                font-size: 9px;
                letter-spacing: 0.04em;
            }

            #modal .input {
                min-height: 34px;
                padding: 0 10px;
                border-radius: 10px;
                font-size: 11px;
            }

            #modal .field-hint {
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

        @media (max-width: 430px) {
            #modal .modal {
                max-width: min(310px, calc(100vw - 44px));
            }
        }

        @media (max-width: 360px) {
            #modal.modal-backdrop {
                padding: 8px;
            }

            #modal .modal {
                max-width: min(286px, calc(100vw - 34px));
                border-radius: 15px;
            }

            #modal .modal-header {
                padding: 10px 12px 3px;
            }

            #modal .modal-body {
                padding: 3px 12px 5px;
            }

            #modal .input {
                min-height: 32px;
                font-size: 10.5px;
            }

            #modal .modal-footer {
                padding: 5px 12px 10px;
            }

            #modal .modal-footer .btn {
                min-height: 32px;
                padding: 0 10px;
                font-size: 10.5px;
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
                        <h1>User Management</h1>
                        <p>Manage system users &amp; roles</p>
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

                        {{-- Stats --}}
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                            <div class="stat-card acc-cyan">
                                <span class="accent-bar"></span>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="stat-label-sm">Total Users</p>
                                        <p class="stat-num-lg">{{ $totalUsers }}</p>
                                        <p class="stat-sub">+{{ $newUsersThisWeek ?? 0 }} minggu ini</p>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                                </div>
                            </div>
                            <div class="stat-card acc-mint">
                                <span class="accent-bar"></span>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="stat-label-sm">Online Now</p>
                                        <p class="stat-num-lg" id="onlineUsersCount">{{ $onlineUsers }}</p>
                                        <p class="stat-sub"><span id="onlineUsersPct">{{ $onlinePercentage }}</span>% sedang aktif</p>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-user-check"></i></div>
                                </div>
                            </div>
                            <div class="stat-card acc-coral">
                                <span class="accent-bar"></span>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="stat-label-sm">Offline</p>
                                        <p class="stat-num-lg" id="offlineUsersCount">{{ $offlineUsers }}</p>
                                        <p class="stat-sub"><span id="offlineUsersPct">{{ $offlinePercentage }}</span>% tidak aktif</p>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-user-slash"></i></div>
                                </div>
                            </div>
                            <div class="stat-card acc-lavender">
                                <span class="accent-bar"></span>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="stat-label-sm">Administrators</p>
                                        <p class="stat-num-lg">{{ $adminUsers }}</p>
                                        <p class="stat-sub">System privileges</p>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-shield-halved"></i></div>
                                </div>
                            </div>
                        </div>

                        {{-- User table card --}}
                        <div class="tbl-wrap">
                            <div class="tbl-toolbar">
                                <form method="GET" action="/users" style="flex:1;max-width:none;">
                                    <label class="search-input">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        <input name="search" value="{{ request('search') }}"
                                            placeholder="Search by username…" autocomplete="off">
                                    </label>
                                </form>
                                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                                    <div class="segmented">
                                        <a class="seg {{ !request('role') ? 'active' : '' }}" href="/users">All</a>
                                        <a class="seg {{ request('role') == 'admin' ? 'active' : '' }}"
                                            href="/users?role=admin">Admin</a>
                                        <a class="seg {{ request('role') == 'operator' ? 'active' : '' }}"
                                            href="/users?role=operator">Operator</a>
                                        <a class="seg {{ request('role') == 'user' ? 'active' : '' }}"
                                            href="/users?role=user">User</a>
                                    </div>
                                    <button onclick="openModal()" type="button" class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-user-plus text-[10px]"></i> Add User
                                    </button>
                                </div>
                            </div>

                            {{-- Filter chips --}}
                            @if (request('role') || request('search'))
                                <div class="filter-chips">
                                    <span>Filters:</span>
                                    @if (request('search'))
                                        <div class="filter-chip">
                                            <i class="fa-solid fa-magnifying-glass text-[9px]"></i>
                                            "{{ request('search') }}"
                                            <button onclick="window.location.href='/users'" title="Clear search"><i class="fa-solid fa-xmark"></i></button>
                                        </div>
                                    @endif
                                    @if (request('role'))
                                        @php
                                            $roleLabel = match(request('role')) {
                                                'admin' => 'Administrator',
                                                'operator' => 'Operator',
                                                'user' => 'User',
                                                default => request('role')
                                            };
                                        @endphp
                                        <div class="filter-chip">
                                            <i class="fa-solid fa-filter text-[9px]"></i>
                                            {{ $roleLabel }}
                                            <button onclick="window.location.href='/users'" title="Clear role filter"><i class="fa-solid fa-xmark"></i></button>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Mobile cards view --}}
                            <div class="user-cards">
                                @forelse ($users as $user)
                                    @php
                                        $isOnline = $user->isOnline ?? false;
                                        $initials = strtoupper(substr($user->name, 0, 1));
                                        $handle = '@' . strtolower(str_replace(' ', '', $user->name));
                                        $colors = ['cyan', 'mint', 'lavender', 'coral'];
                                        $colorIndex = ($user->id - 1) % 4;
                                        $colorName = $colors[$colorIndex];
                                        $roleLabel = match($user->role) {
                                            'admin' => 'ADMIN',
                                            'operator' => 'OPERATOR',
                                            default => 'USER'
                                        };
                                    @endphp
                                    <div class="user-card">
                                        <div class="user-card-header">
                                            <div class="user-card-info">
                                                @if ($user->avatar_url)
                                                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                                                         class="user-avatar-sm" style="object-fit:cover;">
                                                @else
                                                    <div class="user-avatar-sm" style="background:var(--{{ $colorName }});">
                                                        {{ $initials }}
                                                    </div>
                                                @endif
                                                <div class="user-card-name">
                                                    <span class="user-card-name-text">{{ $user->name }}</span>
                                                    <span class="user-card-handle">{{ $handle }}</span>
                                                    @if ($user->email)
                                                        <span style="font-size:11px;color:var(--ink-4);margin-top:2px;display:block;">{{ $user->email }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="badge-role {{ $user->role }}" style="font-size:10px;padding:4px 8px;">{{ $roleLabel }}</span>
                                        </div>
                                        <div class="user-card-footer">
                                            <div class="user-card-status">
                                                <span class="status-dot {{ $isOnline ? 'online' : '' }}"></span>
                                                {{ $isOnline ? 'Online' : 'Offline' }}
                                            </div>
                                            @if ($user->id !== Auth::user()->id)
                                                <div class="user-card-actions">
                                                    <button
                                                        onclick="editRole({{ $user->id }}, '{{ $user->role }}')"
                                                        type="button" class="btn-icon lavender" title="Edit role">
                                                        <i class="fa-solid fa-pen text-[10px]"></i>
                                                    </button>
                                                    <button onclick="deleteUser({{ $user->id }})" type="button"
                                                        class="btn-icon danger" title="Delete user">
                                                        <i class="fa-solid fa-trash text-[10px]"></i>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty-state" style="margin: 20px;">
                                        <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
                                        <p class="empty-title">No users found</p>
                                        <p class="empty-sub">{{ (request('search') || request('role')) ? 'Try adjusting your filters or <a href="/users" style="color:var(--cyan);text-decoration:underline;cursor:pointer;">reset all filters</a>' : '<a href="javascript:void(0)" onclick="openModal()" style="color:var(--cyan);text-decoration:underline;cursor:pointer;">Add a new user</a> to get started' }}</p>
                                    </div>
                                @endforelse
                            </div>

                            {{-- Desktop table view --}}
                            <table id="user-list" class="user-table">
                                <thead>
                                    <tr>
                                        <th style="width:30%;" class="sortable" data-sort="name" onclick="handleSort('name')">USER</th>
                                        <th style="width:20%;" class="sortable" data-sort="role" onclick="handleSort('role')">ROLE</th>
                                        <th style="width:20%;" class="sortable" data-sort="last_activity" onclick="handleSort('last_activity')">STATUS</th>
                                        <th style="width:30%;text-align:right;padding-right:24px;">ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($users as $user)
                                        @php
                                            $isOnline = $user->isOnline ?? false;
                                            $initials = strtoupper(substr($user->name, 0, 1));
                                            $handle = '@' . strtolower(str_replace(' ', '', $user->name));
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="user-cell">
                                                    @php
                                        $colors = ['cyan', 'mint', 'lavender', 'coral'];
                                        $colorIndex = ($user->id - 1) % 4;
                                        $colorName = $colors[$colorIndex];
                                    @endphp
                                    @if ($user->avatar_url)
                                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                                             class="user-avatar-sm" style="object-fit:cover;">
                                    @else
                                        <div class="user-avatar-sm" style="background:var(--{{ $colorName }});">
                                            {{ $initials }}
                                        </div>
                                    @endif
                                                    <div class="user-info">
                                                        <p class="user-name">{{ $user->name }}</p>
                                                        <p class="user-handle">{{ $handle }}</p>
                                                        @if ($user->email)
                                                            <p class="user-email">{{ $user->email }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge-role {{ $user->role }}">
                                                    @if ($user->role == 'admin')
                                                        ADMIN
                                                    @elseif ($user->role == 'operator')
                                                        OPERATOR
                                                    @else
                                                        USER
                                                    @endif
                                                </span>
                                            </td>
                                            <td>
                                                <div class="status-cell">
                                                    <span class="status-dot {{ $isOnline ? 'online' : '' }}"></span>
                                                    {{ $isOnline ? 'Online' : 'Offline' }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="actions-cell">
                                                    @if ($user->id !== Auth::user()->id)
                                                        <button
                                                            onclick="editRole({{ $user->id }}, '{{ $user->role }}')"
                                                            type="button" class="btn-icon lavender" title="Edit role">
                                                            <i class="fa-solid fa-pen text-[10px]"></i>
                                                        </button>
                                                        <button onclick="deleteUser({{ $user->id }})" type="button"
                                                            class="btn-icon danger" title="Delete user">
                                                            <i class="fa-solid fa-trash text-[10px]"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5">
                                                <div class="empty-state">
                                                    <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
                                                    <p class="empty-title">No users found</p>
                                                    <p class="empty-sub">{{ (request('search') || request('role')) ? 'Try adjusting your filters or <a href="/users" style="color:var(--cyan);text-decoration:underline;cursor:pointer;">reset all filters</a>' : '<a href="javascript:void(0)" onclick="openModal()" style="color:var(--cyan);text-decoration:underline;cursor:pointer;">Add a new user</a> to get started' }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                            @if ($users->hasPages())
                                <div class="tbl-footer">
                                    <p>
                                        Menampilkan <span class="text-mono"
                                            style="color:var(--ink-1);">{{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}</span>
                                        dari <span class="text-mono"
                                            style="color:var(--ink-1);">{{ $users->total() }}</span> user
                                    </p>
                                    <div class="pager">
                                        @php
                                            $current = $users->currentPage();
                                            $last = $users->lastPage();
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

                                        @if ($users->onFirstPage())
                                            <span class="disabled"><i class="fa-solid fa-chevron-left text-[9px]"></i></span>
                                        @else
                                            <a href="{{ $users->previousPageUrl() }}"><i class="fa-solid fa-chevron-left text-[9px]"></i></a>
                                        @endif

                                        @foreach ($pages as $p)
                                            @if ($p === '...')
                                                <span class="disabled">…</span>
                                            @elseif ($p == $current)
                                                <span class="active text-mono">{{ $p }}</span>
                                            @else
                                                <a class="text-mono" href="{{ $users->url($p) }}">{{ $p }}</a>
                                            @endif
                                        @endforeach

                                        @if ($users->hasMorePages())
                                            <a href="{{ $users->nextPageUrl() }}"><i class="fa-solid fa-chevron-right text-[9px]"></i></a>
                                        @else
                                            <span class="disabled"><i class="fa-solid fa-chevron-right text-[9px]"></i></span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('components.bottom-nav')

    {{-- Modal: add user --}}
    <div id="modal" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <div>
                    <p class="eyebrow"><i class="fa-solid fa-plus"></i> New</p>
                    <h2>Tambah User</h2>
                    <p class="sub">Buat akun baru dengan peran akses yang sesuai</p>
                </div>
            </div>
            <form id="addUserForm" method="POST" action="/users">
                @csrf
                <div class="modal-body space-y-3">
                    <div class="field">
                        <label class="field-label">Username</label>
                        <input class="input" type="text" name="name" id="newUserName"
                            placeholder="contoh: admin"
                            minlength="3" maxlength="20"
                            pattern="[A-Za-z][A-Za-z0-9_]{2,19}"
                            title="Username 3–20 karakter, huruf/angka/underscore, diawali huruf"
                            autocomplete="off" required>
                        <p class="field-hint" style="font-size:11px;color:var(--ink-3);margin-top:4px;">3-20 karakter, awali dengan huruf.</p>
                    </div>
                    <div class="field">
                        <label class="field-label">Password</label>
                        <input class="input" type="password" name="password" placeholder="min. 8 karakter"
                            minlength="8" required>
                        <p class="field-hint" style="font-size:11px;color:var(--ink-3);margin-top:4px;">Min. 8 karakter, huruf besar/kecil + angka.</p>
                    </div>
                    <div class="field">
                        <label class="field-label">Peran</label>
                        <select class="input select" name="role">
                            <option value="admin">Admin</option>
                            <option value="operator">Operator</option>
                            <option value="user" selected>User</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Buat User</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: edit role --}}
    <div id="editRoleModal" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <div>
                    <p class="eyebrow" style="color:var(--lavender);"><i class="fa-solid fa-pen"></i> Edit</p>
                    <h2>Edit user role</h2>
                </div>
            </div>
            <form id="editRoleForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="field">
                        <label class="field-label">Role</label>
                        <select class="input select" name="role" id="edit_user_role">
                            <option value="admin">Admin</option>
                            <option value="operator">Operator</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update role</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modal')?.classList.add('is-open');
        }

        function closeModal() {
            document.getElementById('modal')?.classList.remove('is-open');
            document.querySelector('#modal form')?.reset();
        }

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

        function visibleUserNames() {
            return new Set(
                Array.from(document.querySelectorAll('.user-name, .user-card-name-text'))
                    .map(el => normalizeFormValue(el.textContent))
                    .filter(Boolean)
            );
        }

        async function usernameExists(username) {
            if (visibleUserNames().has(username)) {
                return true;
            }

            try {
                const response = await fetch(`/users?search=${encodeURIComponent(username)}&sort=name&order=asc`, {
                    headers: {
                        'Accept': 'text/html'
                    },
                    cache: 'no-store'
                });

                if (!response.ok) {
                    return false;
                }

                const doc = new DOMParser().parseFromString(await response.text(), 'text/html');

                return Array.from(doc.querySelectorAll('.user-name, .user-card-name-text'))
                    .some(el => normalizeFormValue(el.textContent) === username);
            } catch (error) {
                return false;
            }
        }

        document.getElementById('newUserName')?.addEventListener('input', e => validateNoSpaces(e.currentTarget, 'Username'));

        document.getElementById('addUserForm')?.addEventListener('submit', async e => {
            e.preventDefault();

            const form = e.currentTarget;
            const nameInput = form.querySelector('[name="name"]');
            const submitButton = form.querySelector('[type="submit"]');
            // Preserve original case — only trim whitespace, do not lowercase
            const username = (nameInput.value || '').trim();
            nameInput.value = username;

            if (!validateNoSpaces(nameInput, 'Username')) {
                nameInput.reportValidity();
                return;
            }

            if (!username) {
                form.reportValidity();
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
            }

            // Case-insensitive duplicate check (server enforces this too)
            const exists = await usernameExists(username.toLowerCase());

            if (submitButton) {
                submitButton.disabled = false;
            }

            if (exists) {
                blockDuplicateInput(nameInput, 'Username sudah digunakan');
                return;
            }

            HTMLFormElement.prototype.submit.call(form);
        });

        function openEditModal(userId, currentRole) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_user_role').value = currentRole;
            document.getElementById('editRoleForm').action = `/users/${userId}`;
            document.getElementById('editRoleModal')?.classList.add('is-open');
        }

        function closeEditModal() {
            document.getElementById('editRoleModal')?.classList.remove('is-open');
        }

        function editRole(id, role) {
            openEditModal(id, role);
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeModal();
                closeEditModal();
            }
        });
        document.getElementById('modal')?.addEventListener('click', e => {
            if (e.target === e.currentTarget) closeModal();
        });
        document.getElementById('editRoleModal')?.addEventListener('click', e => {
            if (e.target === e.currentTarget) closeEditModal();
        });

        function deleteUser(id) {
            if (!confirm('Hapus user ini? Tindakan ini tidak dapat dibatalkan.')) return;
            fetch(`/users/${id}`, {
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
                    window.smToast('User deleted', 'success');
                    setTimeout(() => location.reload(), 800);
                })
                .catch(() => window.smToast('Failed to delete user', 'error'));
        }

        let pingInterval = null;

        function startActivityPing() {
            if (pingInterval) clearInterval(pingInterval);
            pingInterval = setInterval(() => {
                if (!document.hidden) {
                    fetch('/update-activity', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    }).catch(() => {});
                }
            }, 60000);
        }

        document.addEventListener('DOMContentLoaded', () => {
            startActivityPing();
            setSystemStatus(navigator.onLine);

            // Real-time: update counter online users tanpa reload halaman
            function refreshUsersOnline() {
                fetch('/users-online', { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
                    .then(r => r.ok ? r.json() : null)
                    .then(data => {
                        if (!data) return;
                        const c = document.getElementById('onlineUsersCount');
                        const p = document.getElementById('onlineUsersPct');
                        const oc = document.getElementById('offlineUsersCount');
                        const op = document.getElementById('offlineUsersPct');
                        if (c && data.online !== undefined) c.textContent = data.online;
                        if (p && data.percentage !== undefined) p.textContent = data.percentage;
                        if (oc && data.offline !== undefined) oc.textContent = data.offline;
                        if (op && data.offlinePercentage !== undefined) op.textContent = data.offlinePercentage;
                    })
                    .catch(() => {});
            }

            // Debounced reload saat ada aksi CRUD user dari admin lain
            let crudReloadTimer = null;
            const crudActivities = ['add_user', 'delete_user', 'update_role', 'change_password'];
            function scheduleCrudReload() {
                if (crudReloadTimer) clearTimeout(crudReloadTimer);
                crudReloadTimer = setTimeout(() => {
                    const modalOpen = document.querySelector('.is-open, .modal.is-open');
                    const activeTag = document.activeElement?.tagName;
                    if (modalOpen || activeTag === 'INPUT' || activeTag === 'TEXTAREA' || document.hidden) return;
                    location.reload();
                }, 1000);
            }

            if (window.Echo) {
                window.Echo.channel('device-status')
                    .listen('.UserLogCreated', (e) => {
                        refreshUsersOnline();
                        if (e && crudActivities.includes(e.activity)) scheduleCrudReload();
                    });
            }

            // Poll juga tiap 30s sebagai fallback (kalau WS putus)
            setInterval(refreshUsersOnline, 30000);
            @if (session('success'))
                window.smToast("{{ session('success') }}", 'success');
            @endif
            @if (session('error'))
                window.smToast("{{ session('error') }}", 'error');
            @endif
            @if ($errors->any())
                window.smToast("{{ $errors->first() }}", 'error');
            @endif
        });
        window.addEventListener('beforeunload', () => {
            if (pingInterval) clearInterval(pingInterval);
        });

        function setSystemStatus(online) {
            const el = document.getElementById('systemStatus');
            if (!el) return;
            el.className = 'pill ' + (online ? 'pill-online' : 'pill-offline');
            el.innerHTML = `<span class="dot"></span><span>${online ? 'Online' : 'Offline'}</span>`;
        }
        window.addEventListener('online', () => setSystemStatus(true));
        window.addEventListener('offline', () => setSystemStatus(false));

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

        document.addEventListener('DOMContentLoaded', () => {
            initializeSortIndicators();
        });
    </script>
    @include('components.sidebar-scripts')
</body>

</html>
