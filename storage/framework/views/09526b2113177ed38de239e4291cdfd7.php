<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Monitoring Suhu Server – SmartAC</title>
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php echo app('Illuminate\Foundation\Vite')('resources/js/app.js'); ?>
    <?php echo $__env->make('components.sidebar-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <style>
        .raspi-card {
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
            border-radius: var(--r-lg);
            padding: 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .raspi-temp {
            font-size: 72px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            line-height: 1;
            transition: color 0.4s ease;
            display: inline-flex;
            align-items: baseline;
            gap: 4px;
        }
        .raspi-unit {
            font-size: 0.45em;
            font-weight: 600;
            letter-spacing: -0.02em;
            opacity: 0.9;
        }

        .temp-cool {
            color: var(--cyan);
        }

        .temp-warm {
            color: var(--amber, #f59e0b);
        }

        .temp-hot {
            color: var(--coral);
        }

        .temp-muted {
            color: var(--ink-3);
        }

        .raspi-label {
            font-size: 12px;
            color: var(--ink-3);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 600;
        }

        .raspi-status {
            font-size: 12px;
            color: var(--ink-3);
            margin-top: 4px;
        }

        .raspi-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        .raspi-indicator.online {
            background: var(--cyan);
            box-shadow: 0 0 6px var(--cyan);
        }

        .raspi-indicator.offline {
            background: var(--ink-3);
        }

        /* Header — keep on one row */
        .main-header { flex-wrap: nowrap; }
        .main-header > .flex.items-center.gap-3 { min-width: 0; flex: 1; }
        .main-header > .flex.items-center.gap-2 { flex-shrink: 0; }

        /* Mobile M / L (≤ 480 px): compact card + smaller temp */
        @media (max-width: 480px) {
            .raspi-card { padding: 22px 18px; }
            .raspi-temp { font-size: 56px; white-space: nowrap; }
            .raspi-label { font-size: 11px; }
            .raspi-status { font-size: 11.5px; }

            .main-header { gap: 8px; padding-left: 12px; padding-right: 12px; }
            .main-header > .flex.items-center.gap-3 { gap: 8px; }
            .main-header > .flex.items-center.gap-2 { gap: 6px; }
            .main-header .app-header-title h1 { font-size: 15px; line-height: 1.2; }
            .main-header .app-header-title p {
                font-size: 10.5px;
                line-height: 1.25;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .main-header #systemStatus { padding: 4px 8px; font-size: 10px; }
            .main-header .btn-icon { width: 34px; height: 34px; }
        }

        /* Mobile S (≤ 360 px): aggressive shrink */
        @media (max-width: 360px) {
            .raspi-card { padding: 18px 14px; gap: 6px; }
            .raspi-temp { font-size: 44px; }
            .raspi-label { font-size: 10px; }
            .raspi-status { font-size: 11px; }

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
                        <i class="fa-solid fa-bars-staggered"></i>
                    </button>
                    <div class="app-header-title">
                        <h1>Monitoring Suhu Server</h1>
                        <p>Suhu CPU server realtime</p>
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

                        <div class="raspi-card">
                            <p class="raspi-label">
                                <i class="fa-solid fa-microchip" style="margin-right:6px;"></i>
                                Suhu CPU Server
                            </p>
                            <div id="raspi-temp" class="raspi-temp temp-muted">--</div>
                            <p id="raspi-status" class="raspi-status">
                                <span class="raspi-indicator offline" id="raspi-dot"></span>
                                Menghubungkan...
                            </p>
                        </div>

                    </div>
                </div>
            </div>

            <?php echo $__env->make('components.bottom-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    <?php echo $__env->make('components.sidebar-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <script>
        function getSuhu() {
            fetch('/suhu-raspi?_=' + Date.now(), {
                    cache: 'no-store'
                })
                .then(res => res.json())
                .then(data => {
                    const el = document.getElementById('raspi-temp');
                    const st = document.getElementById('raspi-status');
                    const dot = document.getElementById('raspi-dot');

                    if (data.value !== null && data.value !== undefined) {
                        el.innerHTML = data.value + '<span class="raspi-unit">°C</span>';
                        el.className = 'raspi-temp ' + (
                            data.value >= 70 ? 'temp-hot' :
                            data.value >= 55 ? 'temp-warm' :
                            'temp-cool'
                        );
                        dot.className = 'raspi-indicator online';
                        st.innerHTML =
                            '<span class="raspi-indicator online" id="raspi-dot"></span>Online · Update tiap 1 menit';
                    } else {
                        el.innerText = '--';
                        el.className = 'raspi-temp temp-muted';
                        dot.className = 'raspi-indicator offline';
                        st.innerHTML = '<span class="raspi-indicator offline" id="raspi-dot"></span>Menunggu data...';
                    }
                })
                .catch(() => {
                    document.getElementById('raspi-status').innerText = 'Gagal mengambil data';
                });
        }

        getSuhu();
        setInterval(getSuhu, 3000);

        // Real-time: Raspi suhu push via Reverb tanpa nunggu polling 3s
        if (window.Echo) {
            window.Echo.channel('device-status')
                .listen('.RaspiTemperatureUpdated', () => getSuhu());
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
        });
    </script>
</body>

</html>


<?php /**PATH C:\laragon\www\tugasakhirremotac\resources\views/server/monitoring.blade.php ENDPATH**/ ?>