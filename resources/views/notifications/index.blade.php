<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Notifikasi — SmartAC</title>
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite('resources/js/app.js')
    @include('components.sidebar-styles')
    <style>
        .nlist-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 16px 18px;
            border-bottom: 1px solid var(--line-soft);
            transition: var(--t-fast);
        }

        .nlist-item:hover {
            background: var(--panel-1);
        }

        .nlist-item.unread {
            background: rgba(77, 212, 255, 0.05);
        }

        .nlist-item:last-child {
            border-bottom: none;
        }

        .nlist-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            background: var(--panel-2);
            color: var(--ink-3);
        }

        .nlist-icon.error {
            background: rgba(248, 113, 113, .16);
            color: var(--coral);
        }

        .nlist-icon.warning {
            background: rgba(251, 191, 36, .16);
            color: var(--amber);
        }

        .nlist-icon.success {
            background: rgba(110, 231, 183, .16);
            color: var(--mint);
        }

        .nlist-icon.info {
            background: rgba(77, 212, 255, .16);
            color: var(--cyan);
        }

        .nlist-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
            opacity: 0;
            transition: var(--t-fast);
        }

        .nlist-item:hover .nlist-actions {
            opacity: 1;
        }

        @media (max-width: 720px) {
            .nlist-actions {
                opacity: 1;
            }
        }

        /* Header — keep on one row */
        .main-header { flex-wrap: nowrap; }
        .main-header > .flex.items-center.gap-3 { min-width: 0; flex: 1; }
        .main-header > .flex.items-center.gap-2 { flex-shrink: 0; }

        /* Mobile M / L (≤ 480 px): compact notification list */
        @media (max-width: 480px) {
            .nlist-item { padding: 11px 12px; gap: 10px; }
            .nlist-icon { width: 30px; height: 30px; font-size: 12px; border-radius: 9px; }
            .nlist-item .text-base, .nlist-item h3 { font-size: 13px !important; }
            .nlist-item .text-sm, .nlist-item p { font-size: 11.5px !important; line-height: 1.35 !important; }
            .nlist-item .text-xs { font-size: 10px !important; }
            /* Force time meta row to stay on one line with smaller fonts */
            .nlist-item .text-mono { white-space: nowrap; font-size: 10px; }

            .main-header .app-header-title h1 { font-size: 16px; line-height: 1.2; }
            .main-header .app-header-title p {
                font-size: 11px;
                line-height: 1.25;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        /* Mobile S (≤ 360 px): aggressive shrink */
        @media (max-width: 360px) {
            .nlist-item { padding: 9px 10px; gap: 8px; }
            .nlist-icon { width: 26px; height: 26px; font-size: 11px; border-radius: 8px; }
            .nlist-item .text-base, .nlist-item h3 { font-size: 12px !important; }
            .nlist-item .text-sm, .nlist-item p { font-size: 11px !important; }
            .nlist-item .text-mono { font-size: 9.5px; }

            .main-header { gap: 6px; padding-left: 10px; padding-right: 10px; }
            .main-header > .flex.items-center.gap-3 { gap: 6px; }
            .main-header > .flex.items-center.gap-2 { gap: 4px; }
            .main-header .app-header-title h1 { font-size: 13px; line-height: 1.2; }
            .main-header .app-header-title p { font-size: 9.5px; }
            .main-header #systemStatus span:not(.dot) { display: none; }
            .main-header #systemStatus { padding: 4px 6px; }
            .main-header .btn-icon { width: 32px; height: 32px; }
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
                        <h1>Notifikasi</h1>
                        <p>{{ $unreadCount }} belum dibaca dari {{ $notifications->total() }} total</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if ($unreadCount > 0)
                        <form action="/notifications/read-all" method="POST" id="markAllForm">
                            @csrf
                            <button type="button" onclick="bulkMarkAllRead()" class="btn btn-soft btn-sm">
                                <i class="fa-solid fa-check-double text-[10px]"></i>
                                <span>Tandai semua dibaca</span>
                            </button>
                        </form>
                    @endif
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
                        <div class="tbl-wrap" id="notifListWrap">
                            @forelse ($notifications as $n)
                                @php
                                    $iconMap = [
                                        'device_offline' => 'fa-plug-circle-exclamation',
                                        'temp_alert' => 'fa-temperature-three-quarters',
                                        'schedule_run' => 'fa-calendar-check',
                                        'system' => 'fa-gear',
                                    ];
                                    $icon = $iconMap[$n->type] ?? 'fa-bell';
                                @endphp
                                <div class="nlist-item {{ $n->isUnreadForUser($userId) ? 'unread' : '' }}"
                                    data-id="{{ $n->id }}">
                                    <span class="nlist-icon {{ $n->severity }}">
                                        <i class="fa-solid {{ $icon }}"></i>
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            @if ($n->isUnreadForUser($userId))
                                                <span
                                                    style="width:7px;height:7px;border-radius:50%;background:var(--cyan);box-shadow:0 0 8px var(--cyan);"></span>
                                            @endif
                                            <p class="text-sm font-semibold" style="color:var(--ink-0);margin:0;">
                                                {{ $n->title }}</p>
                                        </div>
                                        @if ($n->message)
                                            <p class="text-xs mt-1" style="color:var(--ink-2);line-height:1.5;">
                                                {{ $n->message }}</p>
                                        @endif
                                        <div class="flex items-center gap-3 mt-2 text-mono"
                                            style="font-size:10.5px;color:var(--ink-4);">
                                            <span><i class="fa-regular fa-clock text-[9px]"></i>
                                                {{ $n->created_at->diffForHumans() }}</span>
                                            <span>·</span>
                                            <span>{{ $n->created_at->format('d M Y H:i') }}</span>
                                            @if ($n->link)
                                                <span>·</span>
                                                <a href="{{ $n->link }}"
                                                    data-redirect-to="{{ $n->link }}"
                                                    onclick="markNotifReadInline(event, {{ $n->id }}, this.dataset.redirectTo)"
                                                    style="color:var(--cyan);">Buka detail →</a>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="nlist-actions">
                                        @if ($n->isUnreadForUser($userId))
                                            <button onclick="markNotifReadInline(event, {{ $n->id }}, null)"
                                                class="btn-icon" title="Tandai dibaca">
                                                <i class="fa-solid fa-check text-[11px]"></i>
                                            </button>
                                        @endif
                                        @if ($n->user_id)
                                            <button onclick="deleteNotif({{ $n->id }})" class="btn-icon danger"
                                                title="Delete">
                                                <i class="fa-solid fa-trash text-[11px]"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-regular fa-bell-slash"></i></div>
                                    <p class="empty-title">No notifications</p>
                                    <p class="empty-sub">System notifications & alerts will appear here</p>
                                </div>
                            @endforelse

                            @if ($notifications->hasPages())
                                <div class="tbl-footer">
                                    <p>Page {{ $notifications->currentPage() }} of {{ $notifications->lastPage() }}
                                    </p>
                                    <div class="pager">
                                        @if ($notifications->onFirstPage())
                                            <span class="disabled"><i
                                                    class="fa-solid fa-chevron-left text-[9px]"></i></span>
                                        @else
                                            <a href="{{ $notifications->previousPageUrl() }}"><i
                                                    class="fa-solid fa-chevron-left text-[9px]"></i></a>
                                        @endif
                                        <span class="active text-mono">{{ $notifications->currentPage() }}</span>
                                        @if ($notifications->hasMorePages())
                                            <a href="{{ $notifications->nextPageUrl() }}"><i
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

    <script>
        function markNotifReadInline(e, id, redirectTo) {
            if (e) e.preventDefault?.();
            fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            }).then(r => {
                if (!r.ok) throw new Error(r.status);
                if (redirectTo) { window.location.href = redirectTo; return; }
                const item = document.querySelector(`.nlist-item[data-id="${id}"]`);
                if (item) item.classList.remove('unread');
            }).catch(() => {
                if (window.smToast) window.smToast('Failed to mark notification', 'error');
            });
        }

        function deleteNotif(id) {
            if (!confirm('Delete this notification?')) return;
            fetch(`/notifications/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            }).then(r => {
                if (!r.ok) throw new Error(r.status);
                document.querySelector(`.nlist-item[data-id="${id}"]`)?.remove();
                if (window.smToast) window.smToast('Notifikasi dihapus', 'success');
            }).catch(() => {
                if (window.smToast) window.smToast('Failed to delete notification', 'error');
            });
        }

        let _markAllPending = false;
        function bulkMarkAllRead() {
            if (_markAllPending) return;
            _markAllPending = true;
            fetch('/notifications/read-all', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            }).then(r => {
                if (!r.ok) throw new Error(r.status);
                location.reload();
            }).catch(() => {
                _markAllPending = false;
                if (window.smToast) window.smToast('Failed to mark all notifications', 'error');
            });
        }

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

            // Real-time: prepend notifikasi baru tanpa reload
            function escapeHtml(s) {
                return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
            }
            const iconMap = {
                device_offline: 'fa-plug-circle-exclamation',
                temp_alert: 'fa-temperature-three-quarters',
                schedule_run: 'fa-calendar-check',
                system: 'fa-gear',
            };
            const validSeverity = ['info', 'warning', 'error', 'success'];

            function prependNotif(payload) {
                const wrap = document.getElementById('notifListWrap');
                if (!wrap) return;

                // Skip kalau di halaman pagination selain pertama
                const url = new URL(window.location.href);
                const onFirstPage = !url.searchParams.get('page') || url.searchParams.get('page') === '1';
                if (!onFirstPage) return;

                // Hapus empty state kalau ada
                wrap.querySelector('.empty-state')?.remove();

                const id = Number(payload.id);
                if (!id) return;

                // Skip kalau already ada (anti-duplikat dari multi-tab)
                if (wrap.querySelector(`.nlist-item[data-id="${id}"]`)) return;

                const icon = iconMap[payload.type] || 'fa-bell';
                const severity = validSeverity.includes(payload.severity) ? payload.severity : 'info';
                const title = escapeHtml(payload.title || '');
                const message = payload.message ? escapeHtml(payload.message) : '';
                const timeAgo = escapeHtml(payload.time_ago || 'Baru saja');
                const now = new Date();
                const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                const dateStr = `${String(now.getDate()).padStart(2,'0')} ${months[now.getMonth()]} ${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
                const safeLink = payload.link && /^(https?:\/\/|\/)/.test(payload.link) ? escapeHtml(payload.link) : '';
                const linkHtml = safeLink
                    ? `<span>·</span><a href="${safeLink}" data-redirect-to="${safeLink}" onclick="markNotifReadInline(event, ${id}, this.dataset.redirectTo)" style="color:var(--cyan);">Buka detail →</a>`
                    : '';
                const deleteBtn = payload.user_id
                    ? `<button onclick="deleteNotif(${id})" class="btn-icon danger" title="Delete"><i class="fa-solid fa-trash text-[11px]"></i></button>`
                    : '';

                const item = document.createElement('div');
                item.className = 'nlist-item unread';
                item.dataset.id = id;
                item.innerHTML = `
                    <span class="nlist-icon ${severity}">
                        <i class="fa-solid ${icon}"></i>
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span style="width:7px;height:7px;border-radius:50%;background:var(--cyan);box-shadow:0 0 8px var(--cyan);"></span>
                            <p class="text-sm font-semibold" style="color:var(--ink-0);margin:0;">${title}</p>
                        </div>
                        ${message ? `<p class="text-xs mt-1" style="color:var(--ink-2);line-height:1.5;">${message}</p>` : ''}
                        <div class="flex items-center gap-3 mt-2 text-mono" style="font-size:10.5px;color:var(--ink-4);">
                            <span><i class="fa-regular fa-clock text-[9px]"></i> ${timeAgo}</span>
                            <span>·</span>
                            <span>${dateStr}</span>
                            ${linkHtml}
                        </div>
                    </div>
                    <div class="nlist-actions">
                        <button onclick="markNotifReadInline(event, ${id}, null)" class="btn-icon" title="Tandai dibaca">
                            <i class="fa-solid fa-check text-[11px]"></i>
                        </button>
                        ${deleteBtn}
                    </div>`;

                // Sisipkan di paling atas list (sebelum item lama atau footer pagination)
                const firstItem = wrap.querySelector('.nlist-item');
                if (firstItem) firstItem.before(item);
                else {
                    const footer = wrap.querySelector('.tbl-footer');
                    if (footer) footer.before(item);
                    else wrap.appendChild(item);
                }
            }

            if (window.Echo) {
                window.Echo.channel('device-status')
                    .listen('.NotificationCreated', (e) => prependNotif(e));
            }
        });
    </script>

    @include('components.sidebar-scripts')
</body>

</html>
