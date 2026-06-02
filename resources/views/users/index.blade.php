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

        .stat-card .stat-icon {
            box-sizing: border-box;
            flex-shrink: 0;
            line-height: 1;
            overflow: hidden;
        }

        .stat-card .stat-icon>i {
            font-size: inherit;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1em;
            height: 1em;
        }

        /* Angka utama selalu putih untuk hierarki yang kuat */
        .stat-card .stat-num-lg {
            color: var(--ink-0);
        }

        /* Label kecil di atas mengambil warna accent per kartu */
        .stat-card.acc-cyan .stat-label-sm,
        .stat-card.acc-mint .stat-label-sm,
        .stat-card.acc-lavender .stat-label-sm,
        .stat-card.acc-coral .stat-label-sm {
            color: var(--ink-0);
        }

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
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--ink-3);
        }

        tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            transition: background var(--t-fast);
            height: auto;
            min-height: 56px;
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
            font-size: 14px;
            font-weight: 600;
            color: var(--ink-0);
            margin: 0;
            line-height: 1.2;
        }

        .user-handle {
            font-size: 12px;
            color: var(--ink-3);
            margin: 2px 0 0;
            line-height: 1.2;
        }

        .user-email {
            font-size: 12px;
            color: var(--ink-4);
            margin: 1px 0 0;
            line-height: 1.2;
        }

        /* Unify user-table row padding with logs table (14 × 18) */
        .user-table td {
            padding: 14px 18px;
            vertical-align: middle;
        }

        /* Role — teks saja, tanpa kotak */
        .badge-role {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            white-space: nowrap;
            vertical-align: middle;
        }

        .badge-role.admin,
        .badge-role.operator,
        .badge-role.user {
            background: transparent;
            color: #ffffff;
            border: none;
        }

        .user-avatar-sm {
            width: 34px;
            height: 34px;
            border-radius: var(--r-md);
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
            font-size: 12px;
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
            font-size: 12px;
            letter-spacing: 0.12em;
            color: var(--ink-0);
        }

        @media (max-width: 768px) {

            th,
            td {
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

            .tbl-toolbar>form {
                flex: 1;
                min-width: 0;
            }

            .tbl-toolbar>div {
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
                font-size: 11px;
                padding: 5px 8px;
            }

            .tbl-toolbar .btn {
                padding: 5px 8px;
                font-size: 11px;
                white-space: nowrap;
            }

            .search-input input {
                font-size: 11px;
                padding: 6px 10px 6px 36px;
            }

            .search-input i {
                font-size: 12px;
                left: 10px;
                transform: translateY(2px);
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

            .tbl-toolbar>form {
                flex: 1 1 100%;
                min-width: 0;
            }

            .tbl-toolbar>div {
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

            .tbl-toolbar .btn span {
                display: inline;
            }

            .tbl-toolbar .btn i {
                margin-right: 4px;
                font-size: 11px;
            }

            .search-input input::placeholder {
                color: var(--ink-3);
            }

            .search-input i {
                font-size: 12px;
                left: 12px;
                transform: translateY(2px);
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
            background: rgb(var(--cyan-rgb) / 0.1);
            border: 1px solid rgb(var(--cyan-rgb) / 0.25);
            border-radius: var(--r-full);
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

        /* Pagination — tombol konsisten, rapi */
        .pager {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .pager a,
        .pager span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 11px;
            border-radius: var(--r-md);
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-2);
            background: var(--panel-2);
            border: 1px solid var(--line-soft);
            text-decoration: none;
            transition: all var(--t-fast);
        }

        .pager a {
            cursor: pointer;
        }

        .pager a:hover {
            background: var(--panel-3);
            color: var(--ink-0);
            border-color: var(--line);
        }

        .pager .active {
            background: var(--cyan-soft);
            color: var(--cyan);
            border-color: var(--cyan-soft-2);
            font-weight: 700;
        }

        /* Item nonaktif (panah disabled & "...") tanpa kotak tombol */
        .pager .disabled {
            opacity: 0.4;
            pointer-events: none;
            cursor: not-allowed;
            background: transparent;
            border-color: transparent;
        }

        .pager i {
            opacity: 0.7;
        }

        .pager a:hover i {
            opacity: 1;
        }

        @media (max-width: 600px) {
            .tbl-footer {
                flex-direction: row;
                flex-wrap: nowrap;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
            }

            .tbl-footer>p {
                margin: 0;
                font-size: 11px;
                flex: 1;
                min-width: 0;
                line-height: 1.35;
            }

            .pager {
                flex-wrap: nowrap;
                flex-shrink: 0;
                gap: 4px;
            }

            .pager a,
            .pager span {
                min-width: 30px;
                height: 30px;
                padding: 0 7px;
                font-size: 12px;
            }

            /* Ringkas: sembunyikan nomor selain aktif & yang tepat setelahnya */
            .pager a.text-mono {
                display: none;
            }

            .pager .active+a.text-mono {
                display: inline-flex;
            }
        }

        /* Sortable table headers */
        .user-table th {
            cursor: pointer;
            user-select: none;
            position: relative;
            transition: background var(--t-fast);
        }



        /* Mobile user list — clean simple layout */
        .user-cards {
            display: none;
        }

        @media (max-width: 768px) {
            .user-cards {
                display: flex;
                flex-direction: column;
                width: 100%;
            }

            /* Kartu user — satu baris padat */
            .user-card {
                padding: 10px 14px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                width: 100%;
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
                border-radius: var(--r-md) !important;
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
                font-size: 14px;
                font-weight: 600;
                color: var(--ink-0);
                line-height: 1;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .user-card-handle {
                font-size: 12px;
                color: var(--ink-3);
                line-height: 1.2;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* Email disembunyikan di single-row */
            .user-card-name>span:nth-child(3) {
                display: none !important;
            }

            .user-card .badge-role {
                font-size: 12px !important;
                padding: 0 !important;
                letter-spacing: 0.05em !important;
                white-space: nowrap;
                flex-shrink: 0;
                position: relative;
                top: 1px;
            }

            /* Status — teks Online/Offline */
            .user-card-status {
                display: inline-flex;
                align-items: center;
                justify-content: flex-end;
                min-width: 56px;
                font-size: 12px;
                font-weight: 600;
                color: var(--ink-3);
                text-transform: uppercase;
                letter-spacing: 0.05em;
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
                font-size: 11px;
                border-radius: var(--r-sm);
            }

            .user-table {
                display: none;
            }
        }

        /* Page sections spacing */
        .app-content-inner>*+* {
            margin-top: 32px;
        }

        /* Tablet & desktop (≥ 481 px): unify search / segmented / Add User height to 40 px */
        @media (min-width: 481px) {
            .tbl-toolbar .search-input {
                height: 40px;
            }

            .tbl-toolbar .search-input input {
                height: 40px;
                box-sizing: border-box;
            }

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

        /* Stat cards — Tablet (≤ 768px) */
        @media (max-width: 768px) {
            .grid.grid-cols-2.lg\:grid-cols-4 {
                gap: 12px;
            }

            .stat-card {
                padding: 14px 16px;
            }

            .stat-card .stat-label-sm {
                font-size: 10px;
                letter-spacing: 0.08em;
            }

            .stat-card .stat-num-lg {
                font-size: 28px;
                margin: 6px 0 4px;
            }

            .stat-card .stat-sub {
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

        /* Stat cards — Mobile M (≤ 480px) */
        @media (max-width: 480px) {
            .grid.grid-cols-2.lg\:grid-cols-4 {
                gap: 10px;
            }

            .stat-card {
                padding: 12px 14px;
            }

            .stat-card .stat-label-sm {
                font-size: 8px;
                letter-spacing: 0.05em;
            }

            .stat-card .stat-num-lg {
                font-size: 24px;
                margin: 4px 0 2px;
            }

            .stat-card .stat-sub {
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
        }

        /* Mobile (≤ 480px): shrink header + toolbar so everything fits */
        @media (max-width: 480px) {
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
                line-height: 1.2;
            }

            .main-header .btn-icon {
                width: 32px;
                height: 32px;
            }

            /* User card — selaras dgn log activity */
            .user-card {
                padding: 10px 14px;
            }

            .user-card-info {
                gap: 10px;
            }

            .user-card .user-avatar-sm {
                width: 36px !important;
                height: 36px !important;
                font-size: 13px !important;
                border-radius: var(--r-md) !important;
            }

            .user-card-name-text {
                font-size: 14px;
            }

            .user-card-handle {
                font-size: 12px;
            }

            .user-card .badge-role {
                font-size: 12px !important;
                padding: 0 !important;
            }

            .user-card-actions .btn-icon {
                width: 32px;
                height: 32px;
                font-size: 11px;
            }

            /* Stats grid: keep 2-col but compact each card so text doesn't wrap */
            .grid.grid-cols-2.lg\:grid-cols-4 {
                gap: 8px !important;
            }

            .stat-card {
                padding: 10px 12px !important;
            }

            .stat-card .stat-label-sm {
                font-size: 10px !important;
                letter-spacing: 0.05em !important;
            }

            .stat-card .stat-num-lg {
                font-size: 20px !important;
                margin: 3px 0 2px !important;
                line-height: 1.1 !important;
            }

            .stat-card .stat-sub {
                font-size: 10px !important;
                line-height: 1.3 !important;
            }

            .stat-card .stat-icon {
                width: 26px !important;
                height: 26px !important;
                border-radius: var(--r-sm) !important;
                font-size: 11px !important;
            }

            .stat-card .accent-bar {
                top: 10px !important;
                bottom: 10px !important;
            }

            /* User card: tighter padding, fonts match log card */
            .user-card {
                padding: 10px 14px;
            }

            /* Toolbar at 320 px: stack to 3 rows so segmented + Add User each get full width */
            .tbl-toolbar>div {
                flex-wrap: wrap;
                row-gap: 6px;
            }

            .tbl-toolbar>div>.segmented {
                flex: 1 1 100%;
            }

            .tbl-toolbar>div>.btn {
                flex: 1 1 100%;
            }

            .tbl-toolbar .segmented .seg {
                font-size: 10px;
                padding: 0 4px;
            }
        }

        /* Compact Add User modal on tablets */
        @media (min-width: 601px) and (max-width: 900px) {
            #modal.modal-backdrop {
                padding: 12px;
            }

            #modal .modal {
                max-width: 440px;
                border-radius: var(--r-xl);
            }

            #modal .modal-header {
                padding: 18px 20px 8px;
                gap: 8px;
            }

            #modal .eyebrow {
                font-size: 11px;
                margin-bottom: 4px;
            }

            #modal .modal-header h2 {
                font-size: 16px;
                line-height: 1.2;
            }

            #modal .modal-header .sub {
                font-size: 12px;
                line-height: 1.4;
                margin-top: 5px;
            }

            #modal .modal-body {
                padding: 10px 20px 10px;
            }

            #modal .modal-body.space-y-3> :not([hidden])~ :not([hidden]) {
                margin-top: 14px !important;
            }

            #modal .field {
                gap: 6px;
            }

            #modal .field-label {
                font-size: 11px;
                letter-spacing: 0.05em;
            }

            #modal .input {
                min-height: 44px;
                padding: 0 14px;
                border-radius: var(--r-md);
                font-size: 13px;
            }

            #modal .field-hint {
                font-size: 11px !important;
                line-height: 1.4;
                margin-top: 3px !important;
            }

            #modal .modal-footer {
                padding: 10px 20px 16px;
                gap: 10px;
            }

            #modal .modal-footer .btn {
                min-height: 44px;
                padding: 0 18px;
                font-size: 13px;
            }
        }

        /* Compact Add User modal on mobile */
        @media (max-width: 480px) {
            #modal.modal-backdrop {
                padding: 10px;
            }

            #modal .modal {
                max-width: calc(100vw - 28px);
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
                font-size: 17px;
                line-height: 1.15;
            }

            #modal .modal-header .sub {
                font-size: 10px;
                line-height: 1.3;
                margin-top: 4px;
            }

            #modal .modal-body {
                padding: 4px 14px 6px;
            }

            #modal .modal-body.space-y-3> :not([hidden])~ :not([hidden]) {
                margin-top: 14px !important;
            }

            #modal .field {
                gap: 6px;
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

            #modal .field-hint {
                display: block;
                font-size: 11px;
                line-height: 1.35;
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
                max-width: calc(100vw - 28px);
            }
        }

        @media (max-width: 480px) {
            #modal.modal-backdrop {
                padding: 14px;
            }

            #modal .modal {
                max-width: calc(100vw - 28px);
                border-radius: var(--r-xl);
            }

            #modal .modal-header {
                padding: 16px 18px 6px;
            }

            #modal .modal-body {
                padding: 10px 18px 10px;
            }

            #modal .input {
                min-height: 44px;
                padding: 0 14px;
                font-size: 13px;
            }

            #modal .modal-footer {
                padding: 10px 18px 16px;
            }

            #modal .modal-footer .btn {
                min-height: 44px;
                padding: 0 18px;
                font-size: 13px;
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
                                        <p class="stat-sub">+{{ $newUsersThisWeek ?? 0 }} this week</p>
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
                                        <p class="stat-sub"><span id="onlineUsersPct">{{ $onlinePercentage }}</span>%
                                            currently active</p>
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
                                        <p class="stat-sub"><span id="offlineUsersPct">{{ $offlinePercentage }}</span>%
                                            inactive</p>
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
                                        $roleLabel = match ($user->role) {
                                            'admin' => 'ADMIN',
                                            'operator' => 'OPERATOR',
                                            default => 'USER',
                                        };
                                    @endphp
                                    <div class="user-card">
                                        <div class="flex items-center gap-3">
                                            @if ($user->avatar_url)
                                                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                                                    style="width:34px;height:34px;border-radius:9px;object-fit:cover;flex-shrink:0;">
                                            @else
                                                <div
                                                    style="width:34px;height:34px;border-radius:9px;background:var(--{{ $colorName }});color:#0c1726;font-size:14px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                    {{ $initials }}
                                                </div>
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                <span class="user-card-name-text">{{ $user->name }}</span>
                                            </div>
                                            <div class="flex items-center gap-2" style="flex-shrink:0;">
                                                <span class="badge-role {{ $user->role }}"
                                                    style="min-width:62px;">{{ $roleLabel }}</span>
                                                <span
                                                    style="min-width:48px;text-align:center;display:inline-block;font-size:12px;font-weight:600;line-height:1;white-space:nowrap;color:#ffffff;">{{ $isOnline ? 'Online' : 'Offline' }}</span>
                                                @if ($user->id !== Auth::user()->id)
                                                    <div class="user-card-actions">
                                                        <button onclick="deleteUser({{ $user->id }})" type="button"
                                                            class="btn-icon danger" title="Delete user">
                                                            <i class="fa-solid fa-trash text-[10px]"></i>
                                                        </button>
                                                    </div>
                                                @else
                                                    <div class="user-card-actions" aria-hidden="true"></div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty-state" style="margin: 20px;">
                                        <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
                                        <p class="empty-title">No users found</p>
                                        <p class="empty-sub">
                                            {{ request('search') || request('role') ? 'Try adjusting the filter or <a href="/users" style="color:var(--cyan);text-decoration:underline;cursor:pointer;">reset filter</a>' : '<a href="javascript:void(0)" onclick="openModal()" style="color:var(--cyan);text-decoration:underline;cursor:pointer;">Add a new user</a> to get started' }}
                                        </p>
                                    </div>
                                @endforelse
                            </div>
                            {{-- Desktop table view --}}
                            <table id="user-list" class="user-table">
                                <thead>
                                    <tr>
                                        <th style="width:30%;">USER</th>
                                        <th style="width:20%;">ROLE</th>
                                        <th style="width:20%;">STATUS</th>
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
                                                        <div class="user-avatar-sm"
                                                            style="background:var(--{{ $colorName }});">
                                                            {{ $initials }}
                                                        </div>
                                                    @endif
                                                    <div class="user-info">
                                                        <p class="user-name">{{ $user->name }}</p>
                                                        @if ($user->email)
                                                            <p class="user-email">{{ $user->email }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge-role {{ $user->role }}">{{ strtoupper($user->role) }}</span>
                                            </td>
                                            <td>
                                                <div class="status-cell">
                                                    {{ $isOnline ? 'Online' : 'Offline' }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="actions-cell">
                                                    @if ($user->id !== Auth::user()->id)
                                                        <button onclick="deleteUser({{ $user->id }})"
                                                            type="button" class="btn-icon danger"
                                                            title="Delete user">
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
                                                    <p class="empty-sub">
                                                        {{ request('search') || request('role') ? 'Try adjusting the filter or <a href="/users" style="color:var(--cyan);text-decoration:underline;cursor:pointer;">reset filter</a>' : '<a href="javascript:void(0)" onclick="openModal()" style="color:var(--cyan);text-decoration:underline;cursor:pointer;">Add a new user</a> to get started' }}
                                                    </p>
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
                                                if ($current > 3) {
                                                    $pages[] = '...';
                                                }
                                                for (
                                                    $i = max(2, $current - 1);
                                                    $i <= min($last - 1, $current + 1);
                                                    $i++
                                                ) {
                                                    $pages[] = $i;
                                                }
                                                if ($current < $last - 2) {
                                                    $pages[] = '...';
                                                }
                                                $pages[] = $last;
                                            }
                                        @endphp
                                        @if ($users->onFirstPage())
                                            <span class="disabled"><i
                                                    class="fa-solid fa-chevron-left text-[9px]"></i></span>
                                        @else
                                            <a href="{{ $users->previousPageUrl() }}"><i
                                                    class="fa-solid fa-chevron-left text-[9px]"></i></a>
                                        @endif
                                        @foreach ($pages as $p)
                                            @if ($p === '...')
                                                <span class="disabled">…</span>
                                            @elseif ($p == $current)
                                                <span class="active text-mono">{{ $p }}</span>
                                            @else
                                                <a class="text-mono"
                                                    href="{{ $users->url($p) }}">{{ $p }}</a>
                                            @endif
                                        @endforeach
                                        @if ($users->hasMorePages())
                                            <a href="{{ $users->nextPageUrl() }}"><i
                                                    class="fa-solid fa-chevron-right text-[9px]"></i></a>
                                        @else
                                            <span class="disabled"><i
                                                    class="fa-solid fa-chevron-right text-[9px]"></i></span>
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
                    <h2>Add User</h2>
                    <p class="sub">Create a new account with the appropriate access role</p>
                </div>
            </div>
            <form id="addUserForm" method="POST" action="/users">
                @csrf
                <div class="modal-body space-y-3">
                    <div class="field">
                        <label class="field-label">Username</label>
                        <input class="input" type="text" name="name" id="newUserName"
                            placeholder="e.g. admin" minlength="3" maxlength="20"
                            pattern="[A-Za-z][A-Za-z0-9_]{2,19}"
                            title="Username 3–20 characters, letters/numbers/underscore, must start with a letter"
                            autocomplete="off" required>
                        <p class="field-hint" style="font-size:11px;color:var(--ink-3);margin-top:4px;">3–20
                            characters, start with a letter.</p>
                    </div>
                    <div class="field">
                        <label class="field-label">Password</label>
                        <input class="input" type="password" name="password" placeholder="min. 8 characters"
                            minlength="8" required>
                        <ul class="pwd-checklist" id="addUserPwdChecklist">
                            <li data-rule="len"><i class="fa-regular fa-circle"></i><span>At least 8 characters</span>
                            </li>
                            <li data-rule="case"><i class="fa-regular fa-circle"></i><span>One uppercase & one lowercase
                                    letter</span></li>
                            <li data-rule="num"><i class="fa-regular fa-circle"></i><span>At least one number</span></li>
                        </ul>
                    </div>
                    <div class="field">
                        <label class="field-label">Role</label>
                        <select class="input select" name="role">
                            <option value="admin">Admin</option>
                            <option value="operator">Operator</option>
                            <option value="user" selected>User</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
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
            if (typeof updateAddUserChecklist === 'function') updateAddUserChecklist();
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
                input.setCustomValidity(`${label} must not contain spaces`);
                setFieldFeedback(input, `${label} must not contain spaces`, true);
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
            if (username.length < 3) return false;
            try {
                // Gunakan endpoint khusus (jika ada) atau pastikan response minimalis
                const response = await fetch(`/users?search=${encodeURIComponent(username)}&check_only=1`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    cache: 'no-store'
                });
                if (response.ok) {
                    const data = await response.json();
                    // Asumsi server mengembalikan { exists: true/false } atau list user
                    return data.exists || (Array.isArray(data) && data.length > 0);
                }
                return false;
            } catch (error) {
                return false;
            }
        }
        let usernameTimeout;
        document.getElementById('newUserName')?.addEventListener('input', e => {
            const input = e.currentTarget;
            validateNoSpaces(input, 'Username');
            // Debounce check to avoid flooding the server
            clearTimeout(usernameTimeout);
            usernameTimeout = setTimeout(() => {
                if (input.value.length >= 3) usernameExists(input.value.toLowerCase());
            }, 500);
        });
        // Validasi password sesuai aturan server (min 8, ada huruf besar, kecil, angka)
        function validatePassword(pw) {
            if (!pw || pw.length < 8) return 'Password must be at least 8 characters.';
            if (!/[a-z]/.test(pw)) return 'Password must contain a lowercase letter.';
            if (!/[A-Z]/.test(pw)) return 'Password must contain an uppercase letter.';
            if (!/[0-9]/.test(pw)) return 'Password must contain at least 1 number.';
            return null;
        }
        // Live password requirements checklist (Add User)
        const addUserPasswordInput = document.querySelector('#addUserForm [name="password"]');
        const addUserPwdChecklist = document.getElementById('addUserPwdChecklist');

        function setAddUserRule(rule, ok) {
            const li = addUserPwdChecklist && addUserPwdChecklist.querySelector('[data-rule="' + rule + '"]');
            if (!li) return;
            li.classList.toggle('met', ok);
            const icon = li.querySelector('i');
            if (icon) icon.className = ok ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle';
        }

        function updateAddUserChecklist() {
            const v = addUserPasswordInput ? addUserPasswordInput.value : '';
            setAddUserRule('len', v.length >= 8);
            setAddUserRule('case', /[a-z]/.test(v) && /[A-Z]/.test(v));
            setAddUserRule('num', /[0-9]/.test(v));
        }
        if (addUserPasswordInput) {
            addUserPasswordInput.addEventListener('input', updateAddUserChecklist);
        }
        document.getElementById('addUserForm')?.addEventListener('submit', async e => {
            e.preventDefault();
            const form = e.currentTarget;
            const nameInput = form.querySelector('[name="name"]');
            const passwordInput = form.querySelector('[name="password"]');
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
            // Validasi password — warning tampil di dalam modal (tidak perlu submit dulu)
            const pwError = validatePassword(passwordInput.value);
            if (pwError) {
                updateAddUserChecklist();
                (window.smToast || alert)(pwError, 'error');
                passwordInput.focus();
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
                blockDuplicateInput(nameInput, 'Username already taken');
                return;
            }
            HTMLFormElement.prototype.submit.call(form);
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        document.getElementById('modal')?.addEventListener('click', e => {
            if (e.target === e.currentTarget) closeModal();
        });

        function deleteUser(id) {
            if (!confirm('Delete this user? This action cannot be undone.')) return;
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
            // Real-time: update counter online users tanpa reload halaman
            function refreshUsersOnline() {
                fetch('/users-online', {
                        headers: {
                            'Accept': 'application/json'
                        },
                        cache: 'no-store'
                    })
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
                    if (modalOpen || activeTag === 'INPUT' || activeTag === 'TEXTAREA' || document.hidden)
                        return;
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
                window.smToast(@js(session('success')), 'success');
            @endif
            @if (session('error'))
                window.smToast(@js(session('error')), 'error');
            @endif
            @if ($errors->any())
                window.smToast(@js($errors->first()), 'error');
            @endif
        });
        window.addEventListener('beforeunload', () => {
            if (pingInterval) clearInterval(pingInterval);
        });
        document.addEventListener('DOMContentLoaded', () => {});
    </script>
    @include('components.sidebar-scripts')
</body>

</html>
