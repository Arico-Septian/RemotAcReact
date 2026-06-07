<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Server Temperature Monitor – SmartAC</title>
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite('resources/js/app.js')
    @include('components.sidebar-styles')
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
            font-family: var(--font-mono);
            line-height: 1;
            transition: color 0.4s var(--ease);
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
            color: var(--amber, var(--amber-d));
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

        /* Mobile M / L (≤ 480 px): compact card + smaller temp */
        @media (max-width: 480px) {
            .raspi-card {
                padding: 22px 18px;
            }

            .raspi-temp {
                font-size: 56px;
                white-space: nowrap;
            }

            .raspi-label {
                font-size: 11px;
            }

            .raspi-status {
                font-size: 12px;
            }

            .main-header {
                gap: 8px;
                padding-left: 12px;
                padding-right: 12px;
            }

            .main-header>.flex.items-center.gap-3 {
                gap: 8px;
            }

            .main-header>.flex.items-center.gap-2 {
                gap: 6px;
            }

            .main-header .app-header-title h1 {
                font-size: 16px;
                line-height: 1.2;
            }

            .main-header .app-header-title p {
                font-size: 11px;
                line-height: 1.3;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .main-header .btn-icon {
                width: 34px;
                height: 34px;
            }
        }

        /* Mobile S (≤ 480px): aggressive shrink */
        @media (max-width: 480px) {
            .raspi-card {
                padding: 18px 14px;
                gap: 6px;
            }

            .raspi-temp {
                font-size: 44px;
            }

            .raspi-label {
                font-size: 10px;
            }

            .raspi-status {
                font-size: 11px;
            }

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
            }

            .main-header .btn-icon {
                width: 32px;
                height: 32px;
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
                        <h1>Server Temperature Monitor</h1>
                        <p>Realtime server CPU temperature</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @include('components.notification-bell')
                </div>
            </header>
            <div class="page-body">
                <div class="app-content">
                    <div class="app-content-inner space-y-4">
                        <div class="raspi-card">
                            <p class="raspi-label">
                                <i class="fa-solid fa-microchip" style="margin-right:6px;"></i>
                                Server CPU Temperature
                            </p>
                            <div id="raspi-temp" class="raspi-temp temp-muted">--</div>
                            <p id="raspi-status" class="raspi-status">
                                <span class="raspi-indicator offline" id="raspi-dot"></span>
                                Connecting...
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @include('components.bottom-nav')
        </div>
    </div>
    @include('components.sidebar-scripts')
    <script>
        function formatAge(sec) {
            if (sec === null || sec === undefined) return '–';
            sec = Math.max(0, Math.floor(sec));
            if (sec < 60) return sec + 's';
            const m = Math.floor(sec / 60);
            if (m < 60) return m + ' min';
            const h = Math.floor(m / 60);
            return h + ' h ' + (m % 60) + ' min';
        }

        function getSuhu() {
            fetch('/suhu-raspi?_=' + Date.now(), {
                    cache: 'no-store'
                })
                .then(res => {
                    if (!res.ok) throw new Error(res.status);
                    return res.json();
                })
                .then(data => {
                    const el = document.getElementById('raspi-temp');
                    const st = document.getElementById('raspi-status');
                    const hasValue = data.value !== null && data.value !== undefined;
                    if (data.online && hasValue) {
                        // ONLINE — suhu live
                        el.innerHTML = data.value + '<span class="raspi-unit">°C</span>';
                        el.className = 'raspi-temp ' + (
                            data.value >= 70 ? 'temp-hot' :
                            data.value >= 55 ? 'temp-warm' :
                            'temp-cool'
                        );
                        st.innerHTML =
                            '<span class="raspi-indicator online" id="raspi-dot"></span>Online · updated ' +
                            formatAge(data.age) + ' ago';
                    } else if (hasValue) {
                        // OFFLINE tapi ada pembacaan terakhir — tampilkan redup + last seen
                        el.innerHTML = data.value + '<span class="raspi-unit">°C</span>';
                        el.className = 'raspi-temp temp-muted';
                        st.innerHTML =
                            '<span class="raspi-indicator offline" id="raspi-dot"></span>Offline · last seen ' +
                            formatAge(data.age) + ' ago';
                    } else {
                        // Belum pernah ada data sama sekali
                        el.innerText = '--';
                        el.className = 'raspi-temp temp-muted';
                        st.innerHTML =
                            '<span class="raspi-indicator offline" id="raspi-dot"></span>Waiting for data...';
                    }
                })
                .catch(err => {
                    const el = document.getElementById('raspi-temp');
                    const st = document.getElementById('raspi-status');
                    const now = new Date().toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    if (el) {
                        el.innerText = '—';
                        el.className = 'raspi-temp temp-muted';
                    }
                    if (st) {
                        const code = err?.message && /^\d+$/.test(err.message) ? ` (${err.message})` : '';
                        st.innerHTML =
                            `<span class="raspi-indicator offline" id="raspi-dot"></span>Connection failed${code} · ${now} · Retrying...`;
                    }
                });
        }
        getSuhu();
        setInterval(getSuhu, 30000);
        // Real-time: Raspi suhu push via Reverb tanpa nunggu polling 30s
        if (window.Echo) {
            window.Echo.channel('device-status')
                .listen('.RaspiTemperatureUpdated', () => getSuhu());
        }
        document.addEventListener('DOMContentLoaded', () => {});
    </script>
</body>

</html>
