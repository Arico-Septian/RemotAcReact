<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Status AC — <?php echo e($room->name); ?></title>
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="/js/chart.umd.js"></script>
    <?php echo app('Illuminate\Foundation\Vite')('resources/js/app.js'); ?>
    <?php echo $__env->make('components.sidebar-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <style>
        .ac-card {
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
            border-radius: var(--r-xl);
            box-shadow: var(--inset-hi);
            padding: 14px;
            display: flex; flex-direction: column; gap: 0;
            transition: var(--t-base);
        }
        .ac-card:hover { background: var(--panel-2); border-color: var(--line); transform: translateY(-1px); box-shadow: var(--shadow); }
        .ac-card > .ac-stat { padding: 9px 0; min-height: 32px; }
        .ac-card > .ac-stat + .ac-stat { border-top: 1px solid rgba(255, 255, 255, 0.22); }
        .ac-card > .ac-stat:last-child { padding-bottom: 2px; }
        .ac-card-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding-bottom: 12px; margin-bottom: 2px; border-bottom: 1px solid rgba(255, 255, 255, 0.22); }
        .ac-card-head__text { min-width: 0; }
        .ac-card-head__tag { font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--ink-2); margin: 0; line-height: 1.2; }
        .ac-card-head__name { font-size: 15px; font-weight: 700; color: var(--ink-0); margin: 3px 0 0; line-height: 1.25; text-transform: capitalize; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ac-card-head__icon { width: 32px; height: 32px; border-radius: 10px; background: var(--cyan-soft); color: var(--cyan); border: 1px solid var(--cyan-soft-2); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 13px; }
        .ac-card .ac-stat { display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: 13px; background: transparent; border-radius: 0; padding-left: 0; padding-right: 0; }
        .ac-card .ac-stat .label { display: inline-flex; align-items: center; gap: 8px; color: var(--ink-2); font-weight: 500; }
        .ac-card .ac-stat .label i { color: var(--icon-color, var(--ink-3)); width: 14px; text-align: center; font-size: 12px; }
        .ac-card .ac-stat .value { color: var(--icon-color, var(--ink-0)); font-weight: 700; letter-spacing: .02em; }
        .ac-card .ac-stat.timer-empty .value { color: var(--ink-3); font-weight: 500; letter-spacing: 0; }
        .ac-card .ac-stat .timer-times { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; font-family: var(--font-mono, ui-monospace, monospace); font-size: 11px; line-height: 1.2; color: var(--amber); }
        .ic-power  { --icon-color: var(--mint); }
        .ic-temp   { --icon-color: var(--cyan); }
        .ic-mode   { --icon-color: var(--lavender); }
        .ic-fan    { --icon-color: var(--cyan); }
        .ic-swing  { --icon-color: var(--lavender); }
        .ic-timer  { --icon-color: var(--amber); }

        @media (max-width: 640px) {
            .ac-card {
                padding: 12px;
            }
        }

        .temp-offline-badge {
            display: inline-flex;
            align-items: center;
            margin-left: 6px;
            padding: 3px 8px;
            background-color: var(--coral);
            color: white;
            font-size: 9px;
            font-weight: 600;
            border-radius: 4px;
            letter-spacing: 0.05em;
            vertical-align: middle;
            line-height: 1;
            white-space: nowrap;
        }

        /* Grid optimization — order matters: broader → narrower */
        /* Small phones / portrait phones (≤600px) — 2 columns */
        @media (max-width: 600px) {
            .grid[class*="grid-cols"] {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 8px !important;
            }

            .ac-card {
                padding: 12px;
            }
        }

        /* Very small phones (≤480px) — keep 2 col but tighter; switch to 1 col under 360px */
        @media (max-width: 480px) {
            .grid[class*="grid-cols"] {
                gap: 6px !important;
            }

            .ac-card .label-tag {
                font-size: 10px;
            }

            .ac-stat {
                font-size: 12px;
            }

            .ac-stat .label {
                font-size: 11px;
            }

            .ac-stat .value {
                font-size: 13px;
            }
        }

        /* Tiny phones (≤360px) — single column */
        @media (max-width: 360px) {
            .grid[class*="grid-cols"] {
                grid-template-columns: 1fr !important;
            }
        }

        /* Tablet portrait (769-1023px) — 3 columns instead of cramped 4 */
        @media (min-width: 769px) and (max-width: 1023px) {
            .grid[class*="grid-cols"] {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 12px !important;
            }
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

        /* Landscape mode (rotated phones) */
        @media (max-height: 600px) and (orientation: landscape) {
            .ac-card {
                padding: 10px;
            }

            .ac-stat .label {
                font-size: 10px;
            }

            .ac-stat .value {
                font-size: 12px;
            }
        }

        /* Header — keep on one row across breakpoints */
        .main-header {
            gap: 8px;
            flex-wrap: nowrap;
        }
        .main-header > .flex.items-center.gap-3 { min-width: 0; flex: 1; }
        .main-header > .flex.items-center.gap-2 { flex-shrink: 0; }

        @media (max-width: 480px) {
            .main-header .pill {
                padding: 4px 8px;
                font-size: 10px;
            }

            .main-header .btn-icon {
                width: 36px;
                height: 36px;
            }
        }

        @media (max-width: 360px) {
            .main-header { gap: 6px; padding-left: 10px; padding-right: 10px; }
            .main-header > .flex.items-center.gap-3 { gap: 6px; }
            .main-header > .flex.items-center.gap-2 { gap: 4px; }

            .main-header .app-header-title h1 {
                font-size: 13px;
                line-height: 1.2;
            }

            .main-header .app-header-title p {
                font-size: 9.5px;
                line-height: 1.2;
            }

            /* Shrink Online pill to dot-only — saves ~50px */
            .main-header #systemStatus span:not(.dot) { display: none; }
            .main-header #systemStatus { padding: 4px 6px; }

            .main-header .btn-icon { width: 32px; height: 32px; }
        }

        /* Ultra-wide (>1600px) — cap content width */
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
                    <h1 style="text-transform:capitalize;"><?php echo e($room->name); ?></h1>
                    <p>AC status snapshot</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <?php echo $__env->make('components.notification-bell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <span id="systemStatus" class="pill pill-offline">
                    <span class="dot"></span><span>Offline</span>
                </span>
            </div>
        </header>

        <div class="page-body">
            <div class="app-content">
                <div class="app-content-inner space-y-4">

                    <?php if($acs->count() > 0): ?>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                            <?php $__currentLoopData = $acs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ac): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="ac-card">
                                    <div class="ac-card-head">
                                        <div class="ac-card-head__text">
                                            <p class="ac-card-head__tag">AC <?php echo e($ac->ac_number); ?></p>
                                            <p class="ac-card-head__name" title="<?php echo e($ac->name ?: $ac->brand); ?>"><?php echo e($ac->name ?: $ac->brand); ?></p>
                                        </div>
                                        <div class="ac-card-head__icon">
                                            <i class="fa-solid fa-snowflake"></i>
                                        </div>
                                    </div>

                                    <div class="ac-stat ic-power">
                                        <span class="label"><i class="fa-solid fa-power-off"></i>Power</span>
                                        <span id="power-<?php echo e($ac->id); ?>" class="value"><?php echo e($ac->status?->power ?? 'OFF'); ?></span>
                                    </div>
                                    <div class="ac-stat ic-temp">
                                        <span class="label"><i class="fa-solid fa-temperature-half"></i>Temp</span>
                                        <span id="temp-<?php echo e($ac->id); ?>" class="value"><?php echo e($ac->status?->set_temperature ?? 24); ?>°C</span>
                                    </div>
                                    <div class="ac-stat ic-mode">
                                        <span class="label"><i class="fa-solid fa-fan"></i>Mode</span>
                                        <span id="mode-<?php echo e($ac->id); ?>" class="value"><?php echo e(strtoupper($ac->status?->mode ?? 'AUTO')); ?></span>
                                    </div>
                                    <div class="ac-stat ic-fan">
                                        <span class="label"><i class="fa-solid fa-wind"></i>Fan</span>
                                        <span id="fan-<?php echo e($ac->id); ?>" class="value"><?php echo e(strtoupper($ac->status?->fan_speed ?? 'AUTO')); ?></span>
                                    </div>
                                    <div class="ac-stat ic-swing">
                                        <span class="label"><i class="fa-solid fa-arrows-up-down"></i>Swing</span>
                                        <span id="swing-<?php echo e($ac->id); ?>" class="value"><?php echo e(strtoupper($ac->status?->swing ?? 'OFF')); ?></span>
                                    </div>
                                    <?php $hasTimer = $ac->timer_on || $ac->timer_off; ?>
                                    <div class="ac-stat ic-timer <?php echo e($hasTimer ? '' : 'timer-empty'); ?>">
                                        <span class="label"><i class="fa-solid fa-clock"></i>Timer</span>
                                        <span id="timer-<?php echo e($ac->id); ?>" class="value">
                                            <?php if($hasTimer): ?>
                                                <span class="timer-times">
                                                    <?php if($ac->timer_on): ?><span>ON <?php echo e(\Carbon\Carbon::parse($ac->timer_on)->setTimezone('Asia/Jakarta')->format('H:i')); ?></span><?php endif; ?>
                                                    <?php if($ac->timer_off): ?><span>OFF <?php echo e(\Carbon\Carbon::parse($ac->timer_off)->setTimezone('Asia/Jakarta')->format('H:i')); ?></span><?php endif; ?>
                                                </span>
                                            <?php else: ?>
                                                OFF
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-snowflake"></i></div>
                            <p class="empty-title">Belum ada AC unit</p>
                            <p class="empty-sub">Tambahkan AC unit di halaman manajemen ruangan</p>
                        </div>
                    <?php endif; ?>


                </div>
            </div>
        </div>
    </div>
</div>

<?php echo $__env->make('components.bottom-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<script>
function updateElement(id, val) {
    const el = document.getElementById(id);
    if (el && el.textContent.trim() !== String(val).trim()) {
        el.style.transition = 'color .25s, transform .25s';
        const original = el.style.color;
        el.style.color = '#4dd4ff';
        el.style.transform = 'scale(1.05)';
        el.textContent = val;
        setTimeout(() => { el.style.color = original; el.style.transform = ''; }, 460);
    }
}
function formatTime(t) {
    if (!t || t === '0000-00-00 00:00:00' || t === '00:00:00') return '';
    const s = String(t).trim();
    const timeOnly = s.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/);
    if (timeOnly) {
        return timeOnly[1].padStart(2, '0') + ':' + timeOnly[2];
    }
    try {
        const d = new Date(s.replace(/-/g, '/').replace('T', ' ').replace(/\..*$/, ''));
        return isNaN(d.getTime()) ? '' : d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false });
    } catch { return ''; }
}
function loadStatus() {
    fetch('/api/ac-status', { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' } })
        .then(r => r.ok ? r.json() : null)
        .then(data => {
            if (!Array.isArray(data)) return;
            data.forEach(item => {
                // Laravel serialize relationship sebagai snake_case (ac_unit), bukan acUnit
                const ac = item.ac_unit || item.acUnit;
                const id = ac?.id ?? item.ac_unit_id;
                if (!id) return;
                updateElement('power-' + id, (item.power || 'OFF').toUpperCase());
                updateElement('temp-'  + id, (item.set_temperature ?? 24) + '°C');
                updateElement('mode-'  + id, (item.mode || 'AUTO').toUpperCase());
                updateElement('fan-'   + id, (item.fan_speed || 'AUTO').toUpperCase());
                updateElement('swing-' + id, (item.swing || 'OFF').toUpperCase());
                const timerEl = document.getElementById('timer-' + id);
                if (timerEl && ac) {
                    const on  = formatTime(ac.timer_on);
                    const off = formatTime(ac.timer_off);
                    const row = timerEl.closest('.ac-stat');
                    if (on || off) {
                        row?.classList.remove('timer-empty');
                        timerEl.innerHTML = '<span class="timer-times">'
                            + (on ? `<span>ON ${on}</span>` : '')
                            + (off ? `<span>OFF ${off}</span>` : '')
                            + '</span>';
                    } else {
                        row?.classList.add('timer-empty');
                        timerEl.textContent = 'OFF';
                    }
                }
            });
        }).catch(() => {});
}
setInterval(loadStatus, 5000);

function setSystemStatus(online) {
    const el = document.getElementById('systemStatus');
    if (!el) return;
    el.className = 'pill ' + (online ? 'pill-online' : 'pill-offline');
    el.innerHTML = `<span class="dot"></span><span>${online ? 'Online' : 'Offline'}</span>`;
}
window.addEventListener('online',  () => setSystemStatus(true));
window.addEventListener('offline', () => setSystemStatus(false));

document.addEventListener('DOMContentLoaded', () => {
    loadStatus();
    setSystemStatus(navigator.onLine);

    // Real-time via Reverb: refresh segera saat AC/device berubah dari user/tab lain
    if (window.Echo) {
        window.Echo.channel('device-status')
            .listen('.AcStatusUpdated', () => loadStatus())
            .listen('.DeviceStatusUpdated', () => loadStatus());
    }
});
</script>
<?php echo $__env->make('components.sidebar-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>
</html>


<?php /**PATH C:\laragon\www\tugasakhirremotac\resources\views/rooms/status.blade.php ENDPATH**/ ?>