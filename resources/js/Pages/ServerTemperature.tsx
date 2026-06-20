import { useEffect, useRef, useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import '../../css/server-temperature.css';

interface SuhuData {
    value: number | null;
    online: boolean;
    age: number | null;
}

type State =
    | { kind: 'connecting' }
    | { kind: 'online'; value: number; age: number | null }
    | { kind: 'offline'; value: number; age: number | null }
    | { kind: 'waiting' }
    | { kind: 'error'; code: string; at: string };

interface Point {
    t: string;
    raspi: number | null;
    server: number | null;
}

const GAUGE_MIN = 0;
const GAUGE_MAX = 90;
const MAX_POINTS = 60;

function formatAge(sec: number | null): string {
    if (sec === null || sec === undefined) return '–';
    sec = Math.max(0, Math.floor(sec));
    if (sec < 60) return `${sec}s`;
    const m = Math.floor(sec / 60);
    if (m < 60) return `${m} min`;
    const h = Math.floor(m / 60);
    return `${h} h ${m % 60} min`;
}

// Format suhu: maks 1 desimal, tanpa nol di belakang titik (33.0 -> "33", 47.2 -> "47.2").
const fmtTemp = (v: number) => String(Number(v.toFixed(1)));

function zoneColor(value: number | null): string {
    if (value === null) return '#475569';
    if (value >= 70) return '#fb7185';
    if (value >= 55) return '#fbbf24';
    return '#22d3ee';
}

function stateValue(s: State): number | null {
    return s.kind === 'online' || s.kind === 'offline' ? s.value : null;
}

function renderStatus(state: State) {
    switch (state.kind) {
        case 'online':
            return (
                <>
                    <span className="raspi-indicator online"></span>Online · updated {formatAge(state.age)} ago
                </>
            );
        case 'offline':
            return (
                <>
                    <span className="raspi-indicator offline"></span>Offline · last seen {formatAge(state.age)} ago
                </>
            );
        case 'waiting':
            return (
                <>
                    <span className="raspi-indicator offline"></span>Waiting for data...
                </>
            );
        case 'error':
            return (
                <>
                    <span className="raspi-indicator offline"></span>Connection failed{state.code} · {state.at} · Retrying...
                </>
            );
        default:
            return (
                <>
                    <span className="raspi-indicator offline"></span>Connecting...
                </>
            );
    }
}

function Gauge({ value }: { value: number | null }) {
    const size = 180;
    const stroke = 14;
    const r = (size - stroke) / 2;
    const circ = 2 * Math.PI * r;
    const pct = value === null ? 0 : Math.min(1, Math.max(0, (value - GAUGE_MIN) / (GAUGE_MAX - GAUGE_MIN)));
    const color = zoneColor(value);

    return (
        <div style={{ position: 'relative', width: size, height: size, margin: '6px auto 2px' }}>
            <svg width={size} height={size} style={{ transform: 'rotate(-90deg)' }}>
                <circle cx={size / 2} cy={size / 2} r={r} fill="none" stroke="rgba(255,255,255,0.07)" strokeWidth={stroke} />
                <circle
                    cx={size / 2}
                    cy={size / 2}
                    r={r}
                    fill="none"
                    stroke={color}
                    strokeWidth={stroke}
                    strokeLinecap={pct > 0 ? 'round' : 'butt'}
                    strokeDasharray={`${circ * pct} ${circ}`}
                    style={{ transition: 'stroke-dasharray 0.7s ease, stroke 0.3s ease', filter: `drop-shadow(0 0 6px ${color}55)` }}
                />
            </svg>
            <div
                style={{
                    position: 'absolute',
                    inset: 0,
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                }}
            >
                <div style={{ fontSize: 44, fontWeight: 800, lineHeight: 1, color, letterSpacing: '-0.02em' }}>
                    {value === null ? '--' : fmtTemp(value)}
                    <span style={{ fontSize: 18, fontWeight: 700, marginLeft: 2, color: 'var(--ink-3)' }}>°C</span>
                </div>
                <div style={{ fontSize: 11, color: 'var(--ink-4)', marginTop: 4, letterSpacing: '0.04em' }}>
                    {GAUGE_MIN}° – {GAUGE_MAX}°
                </div>
            </div>
        </div>
    );
}

function GaugeCard({ label, state }: { label: string; state: State }) {
    return (
        <div className="raspi-card" style={{ flex: 1, minWidth: 280 }}>
            <p className="raspi-label">
                <i className="fa-solid fa-microchip" style={{ marginRight: 6 }}></i>
                {label}
            </p>
            <Gauge value={stateValue(state)} />
            <p className="raspi-status">{renderStatus(state)}</p>
        </div>
    );
}

function CpuTrendChart({ history }: { history: Point[] }) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const chartRef = useRef<any>(null);

    useEffect(() => {
        const Chart = window.Chart;
        if (!canvasRef.current || !Chart) return;

        const ds = (label: string, color: string) => ({
            label,
            data: [] as (number | null)[],
            borderColor: color,
            backgroundColor: (ctx: any) => {
                const { chart } = ctx;
                const { ctx: c, chartArea } = chart;
                if (!chartArea) return `${color}14`;
                const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                g.addColorStop(0, `${color}33`);
                g.addColorStop(1, `${color}00`);
                return g;
            },
            borderWidth: 2.4,
            tension: 0.4,
            spanGaps: true,
            pointRadius: 0,
            pointHoverRadius: 4,
            fill: true,
        });

        chartRef.current = new Chart(canvasRef.current, {
            type: 'line',
            data: { labels: [], datasets: [ds('Raspi', '#22d3ee'), ds('Server', '#a78bfa')] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 250 },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: true, labels: { color: '#94a3b8', usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16 } },
                    tooltip: {
                        displayColors: true,
                        backgroundColor: 'rgba(22,26,36,0.97)',
                        borderColor: 'rgba(255,255,255,0.12)',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 10,
                        callbacks: { label: (it: any) => `${it.dataset.label}: ${it.parsed.y == null ? '—' : fmtTemp(it.parsed.y) + '°C'}` },
                    },
                },
                scales: {
                    x: { ticks: { color: '#64748b', font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }, grid: { display: false }, border: { display: false } },
                    y: {
                        min: 0,
                        max: 90,
                        ticks: { color: '#94a3b8', font: { size: 10 }, padding: 8, callback: (v: any) => `${v}°` },
                        grid: { color: 'rgba(255,255,255,0.05)', drawTicks: false },
                        border: { display: false },
                    },
                },
            },
        });

        return () => {
            chartRef.current?.destroy();
            chartRef.current = null;
        };
    }, []);

    useEffect(() => {
        const ch = chartRef.current;
        if (!ch) return;
        ch.data.labels = history.map((h) => h.t);
        ch.data.datasets[0].data = history.map((h) => h.raspi);
        ch.data.datasets[1].data = history.map((h) => h.server);
        ch.update('none');
    }, [history]);

    return <canvas ref={canvasRef}></canvas>;
}

export default function ServerTemperature() {
    const [raspi, setRaspi] = useState<State>({ kind: 'connecting' });
    const [server, setServer] = useState<State>({ kind: 'connecting' });
    const [history, setHistory] = useState<Point[]>([]);

    useEffect(() => {
        let active = true;

        const fetchOne = async (endpoint: string): Promise<{ state: State; value: number | null }> => {
            try {
                const res = await fetch(`${endpoint}?_=${Date.now()}`, { cache: 'no-store' });
                if (!res.ok) throw new Error(String(res.status));
                const data: SuhuData = await res.json();
                const hasValue = data.value !== null && data.value !== undefined;
                if (data.online && hasValue) return { state: { kind: 'online', value: data.value as number, age: data.age }, value: data.value as number };
                if (hasValue) return { state: { kind: 'offline', value: data.value as number, age: data.age }, value: data.value as number };
                return { state: { kind: 'waiting' }, value: null };
            } catch (err: any) {
                const now = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                const code = err?.message && /^\d+$/.test(err.message) ? ` (${err.message})` : '';
                return { state: { kind: 'error', code, at: now }, value: null };
            }
        };

        const refresh = async () => {
            const [r, s] = await Promise.all([fetchOne('/suhu-raspi'), fetchOne('/suhu-server')]);
            if (!active) return;
            setRaspi(r.state);
            setServer(s.state);
            // Hanya catat titik tren jika minimal satu sumber punya nilai.
            if (r.value !== null || s.value !== null) {
                const now = new Date();
                const p = (n: number) => String(n).padStart(2, '0');
                const label = `${p(now.getHours())}:${p(now.getMinutes())}:${p(now.getSeconds())}`;
                setHistory((prev) => [...prev, { t: label, raspi: r.value, server: s.value }].slice(-MAX_POINTS));
            }
        };

        refresh();
        const id = window.setInterval(refresh, 30000);

        const echo = window.Echo;
        if (echo) {
            echo.channel('device-status').listen('.RaspiTemperatureUpdated', () => refresh());
        }

        return () => {
            active = false;
            window.clearInterval(id);
            try {
                echo?.leaveChannel('device-status');
            } catch {
                /* noop */
            }
        };
    }, []);

    return (
        <AppLayout title="Server Temperature Monitor" subtitle="Realtime CPU temperature (Raspi & Server)">
            <Head title="Server Temperature" />

            <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
                <div style={{ display: 'flex', gap: 16, flexWrap: 'wrap' }}>
                    <GaugeCard label="Raspi CPU Temperature" state={raspi} />
                    <GaugeCard label="Server CPU Temperature" state={server} />
                </div>

                <div className="raspi-card" style={{ padding: 20 }}>
                    <div style={{ width: '100%', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 8, marginBottom: 6 }}>
                        <p className="raspi-label" style={{ margin: 0 }}>
                            <i className="fa-solid fa-chart-line" style={{ marginRight: 6 }}></i>
                            Tren Suhu CPU (real-time)
                        </p>
                        <span style={{ fontSize: 11, color: 'var(--ink-4)' }}>
                            <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5 }}>
                                <i style={{ width: 16, height: 3, borderRadius: 2, background: '#22d3ee', display: 'inline-block' }}></i> ≤55° normal
                                <i style={{ width: 16, height: 3, borderRadius: 2, background: '#fbbf24', display: 'inline-block', marginLeft: 10 }}></i> 55–70° hangat
                                <i style={{ width: 16, height: 3, borderRadius: 2, background: '#fb7185', display: 'inline-block', marginLeft: 10 }}></i> &gt;70° panas
                            </span>
                        </span>
                    </div>
                    <div style={{ width: '100%', height: 300, position: 'relative' }}>
                        <CpuTrendChart history={history} />
                        {history.length < 2 && (
                            <div
                                style={{
                                    position: 'absolute',
                                    inset: 0,
                                    display: 'flex',
                                    flexDirection: 'column',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    color: 'var(--ink-4)',
                                    fontSize: 13,
                                    gap: 8,
                                    pointerEvents: 'none',
                                }}
                            >
                                <i className="fa-solid fa-wave-square" style={{ fontSize: 22, opacity: 0.5 }}></i>
                                <span>Mengumpulkan data… grafik terisi seiring pembacaan masuk.</span>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
