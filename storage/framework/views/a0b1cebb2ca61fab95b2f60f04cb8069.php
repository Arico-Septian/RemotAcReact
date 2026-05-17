<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Notifikasi — SmartAC</title>
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php echo app('Illuminate\Foundation\Vite')('resources/js/app.js'); ?>
    <?php echo $__env->make('components.sidebar-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
        <?php echo $__env->make('components.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="main-content">
            <header class="main-header">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="lg:hidden btn-icon" title="Menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="app-header-title">
                        <h1>Notifikasi</h1>
                        <p><?php echo e($unreadCount); ?> belum dibaca dari <?php echo e($notifications->total()); ?> total</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <?php if($unreadCount > 0): ?>
                        <form action="/notifications/read-all" method="POST" id="markAllForm">
                            <?php echo csrf_field(); ?>
                            <button type="button" onclick="bulkMarkAllRead()" class="btn btn-soft btn-sm">
                                <i class="fa-solid fa-check-double text-[10px]"></i>
                                <span>Tandai semua dibaca</span>
                            </button>
                        </form>
                    <?php endif; ?>
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
                        <div class="tbl-wrap" id="notifListWrap">
                            <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $iconMap = [
                                        'device_offline' => 'fa-plug-circle-exclamation',
                                        'temp_alert' => 'fa-temperature-three-quarters',
                                        'schedule_run' => 'fa-calendar-check',
                                        'system' => 'fa-gear',
                                    ];
                                    $icon = $iconMap[$n->type] ?? 'fa-bell';
                                ?>
                                <div class="nlist-item <?php echo e($n->isUnread() ? 'unread' : ''); ?>"
                                    data-id="<?php echo e($n->id); ?>">
                                    <span class="nlist-icon <?php echo e($n->severity); ?>">
                                        <i class="fa-solid <?php echo e($icon); ?>"></i>
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <?php if($n->isUnread()): ?>
                                                <span
                                                    style="width:7px;height:7px;border-radius:50%;background:var(--cyan);box-shadow:0 0 8px var(--cyan);"></span>
                                            <?php endif; ?>
                                            <p class="text-sm font-semibold" style="color:var(--ink-0);margin:0;">
                                                <?php echo e($n->title); ?></p>
                                        </div>
                                        <?php if($n->message): ?>
                                            <p class="text-xs mt-1" style="color:var(--ink-2);line-height:1.5;">
                                                <?php echo e($n->message); ?></p>
                                        <?php endif; ?>
                                        <div class="flex items-center gap-3 mt-2 text-mono"
                                            style="font-size:10.5px;color:var(--ink-4);">
                                            <span><i class="fa-regular fa-clock text-[9px]"></i>
                                                <?php echo e($n->created_at->diffForHumans()); ?></span>
                                            <span>·</span>
                                            <span><?php echo e($n->created_at->format('d M Y H:i')); ?></span>
                                            <?php if($n->link): ?>
                                                <span>·</span>
                                                <a href="<?php echo e($n->link); ?>"
                                                    onclick="markNotifReadInline(event, <?php echo e($n->id); ?>, '<?php echo e($n->link); ?>')"
                                                    style="color:var(--cyan);">Buka detail →</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="nlist-actions">
                                        <?php if($n->isUnread()): ?>
                                            <button onclick="markNotifReadInline(event, <?php echo e($n->id); ?>, null)"
                                                class="btn-icon" title="Tandai dibaca">
                                                <i class="fa-solid fa-check text-[11px]"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if($n->user_id): ?>
                                            <button onclick="deleteNotif(<?php echo e($n->id); ?>)" class="btn-icon danger"
                                                title="Hapus">
                                                <i class="fa-solid fa-trash text-[11px]"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-regular fa-bell-slash"></i></div>
                                    <p class="empty-title">Belum ada notifikasi</p>
                                    <p class="empty-sub">Notifikasi sistem & alert akan muncul di sini</p>
                                </div>
                            <?php endif; ?>

                            <?php if($notifications->hasPages()): ?>
                                <div class="tbl-footer">
                                    <p>Page <?php echo e($notifications->currentPage()); ?> of <?php echo e($notifications->lastPage()); ?>

                                    </p>
                                    <div class="pager">
                                        <?php if($notifications->onFirstPage()): ?>
                                            <span class="disabled"><i
                                                    class="fa-solid fa-chevron-left text-[9px]"></i></span>
                                        <?php else: ?>
                                            <a href="<?php echo e($notifications->previousPageUrl()); ?>"><i
                                                    class="fa-solid fa-chevron-left text-[9px]"></i></a>
                                        <?php endif; ?>
                                        <span class="active text-mono"><?php echo e($notifications->currentPage()); ?></span>
                                        <?php if($notifications->hasMorePages()): ?>
                                            <a href="<?php echo e($notifications->nextPageUrl()); ?>"><i
                                                    class="fa-solid fa-chevron-right text-[9px]"></i></a>
                                        <?php else: ?>
                                            <span class="disabled"><i
                                                    class="fa-solid fa-chevron-right text-[9px]"></i></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php echo $__env->make('components.bottom-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <script>
        function markNotifReadInline(e, id, redirectTo) {
            if (e) e.preventDefault?.();
            fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            }).then(() => {
                if (redirectTo) {
                    window.location.href = redirectTo;
                    return;
                }
                const item = document.querySelector(`.nlist-item[data-id="${id}"]`);
                if (item) item.classList.remove('unread');
            });
        }

        function deleteNotif(id) {
            if (!confirm('Hapus notifikasi ini?')) return;
            fetch(`/notifications/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            }).then(() => {
                document.querySelector(`.nlist-item[data-id="${id}"]`)?.remove();
                if (window.smToast) window.smToast('Notifikasi dihapus', 'success');
            });
        }

        function bulkMarkAllRead() {
            fetch('/notifications/read-all', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            }).then(() => location.reload());
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
                const linkHtml = payload.link
                    ? `<span>·</span><a href="${escapeHtml(payload.link)}" onclick="markNotifReadInline(event, ${id}, '${escapeHtml(payload.link)}')" style="color:var(--cyan);">Buka detail →</a>`
                    : '';
                const deleteBtn = payload.user_id
                    ? `<button onclick="deleteNotif(${id})" class="btn-icon danger" title="Hapus"><i class="fa-solid fa-trash text-[11px]"></i></button>`
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

    <?php echo $__env->make('components.sidebar-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>

</html>

<?php /**PATH C:\laragon\www\tugasakhirremotac\resources\views/notifications/index.blade.php ENDPATH**/ ?>