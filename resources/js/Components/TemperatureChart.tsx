import { useEffect, useRef, useState } from 'react';

interface TrendDataset {
    room: string;
    data: (number | null)[];
    color: string;
    current_temp: number | null;
    is_offline: boolean;
}

interface TrendPayload {
    labels: string[];
    datasets: TrendDataset[];
    rooms_online: number;
    rooms_offline: number;
    range: string;
}

type Range = '1d' | '1h';

function hexToRgba(hex: string, a = 1): string {
    const h = hex.replace('#', '').trim();
    const full = h.length === 3 ? h.split('').map((c) => c + c).join('') : h;
    const r = parseInt(full.slice(0, 2), 16);
    const g = parseInt(full.slice(2, 4), 16);
    const b = parseInt(full.slice(4, 6), 16);
    return `rgba(${r},${g},${b},${a})`;
}

export default function TemperatureChart() {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const xAxisCanvasRef = useRef<HTMLCanvasElement>(null);
    const yAxisCanvasRef = useRef<HTMLCanvasElement>(null);
    const plotVpRef = useRef<HTMLDivElement>(null);
    const chartRef = useRef<any>(null);

    // Sinkronkan sumbu beku dengan scroll plot: Y ikut geser vertikal, X ikut geser horizontal.
    const syncAxes = () => {
        const vp = plotVpRef.current;
        if (!vp) return;
        if (yAxisCanvasRef.current) yAxisCanvasRef.current.style.transform = `translateY(${-vp.scrollTop}px)`;
        if (xAxisCanvasRef.current) xAxisCanvasRef.current.style.transform = `translateX(${-vp.scrollLeft}px)`;
    };
    const [range, setRange] = useState<Range>('1d');
    const [info, setInfo] = useState<string>('');
    const [empty, setEmpty] = useState(false);
    const [loading, setLoading] = useState(true);
    const [pointCount, setPointCount] = useState(0);

    const load = async (r: Range) => {
        try {
            const { data } = await window.axios.get<TrendPayload>(`/temperature/trend?range=${r}`);
            const hasData = data.datasets.some((ds) => ds.data.some((v) => v !== null));
            setEmpty(!hasData);
            setInfo(`${data.rooms_online} online · ${data.rooms_offline} offline`);
            setPointCount(data.labels.length);
            renderChart(data);
        } catch {
            setEmpty(true);
        } finally {
            setLoading(false);
        }
    };

    const renderChart = (payload: TrendPayload) => {
        const canvas = canvasRef.current;
        const Chart = window.Chart;
        if (!canvas || !Chart) return;

        // Sumbu jam "beku": digambar di canvas terpisah (tidak ikut scroll vertikal).
        const frozenXAxisPlugin = {
            id: 'frozenXAxis',
            afterDraw(chart: any) {
                const overlay = xAxisCanvasRef.current;
                const xScale = chart.scales.x;
                if (!overlay || !xScale) return;
                const dpr = window.devicePixelRatio || 1;
                const cssW = chart.width;
                const cssH = 28;
                overlay.style.width = `${cssW}px`;
                if (overlay.width !== Math.round(cssW * dpr) || overlay.height !== Math.round(cssH * dpr)) {
                    overlay.width = Math.round(cssW * dpr);
                    overlay.height = Math.round(cssH * dpr);
                }
                const octx = overlay.getContext('2d');
                if (!octx) return;
                octx.setTransform(dpr, 0, 0, dpr, 0, 0);
                octx.clearRect(0, 0, cssW, cssH);
                octx.font = '10px Inter, system-ui, sans-serif';
                octx.fillStyle = '#94a3b8';
                octx.textBaseline = 'top';
                const labels: string[] = chart.data.labels || [];
                let lastX = -Infinity;
                (xScale.ticks || []).forEach((_t: any, i: number) => {
                    const label = labels[i];
                    if (!label) return;
                    // Tampilkan setiap label (per-menit untuk 1h, per-jam untuk 1d).
                    // Penjaga jarak 40px di bawah menjaga agar tidak tumpang tindih.
                    const px = xScale.getPixelForTick(i);
                    if (px - lastX < 40) return;
                    const halfW = octx.measureText(label).width / 2;
                    let drawX = px;
                    octx.textAlign = 'center';
                    if (px - halfW < 1) {
                        octx.textAlign = 'left';
                        drawX = 1;
                    } else if (px + halfW > cssW - 1) {
                        octx.textAlign = 'right';
                        drawX = cssW - 1;
                    }
                    octx.fillText(label, drawX, 8);
                    lastX = px;
                });
            },
        };

        // Sumbu Y (suhu) "beku": digambar di canvas terpisah di kiri (tidak ikut scroll horizontal).
        const frozenYAxisPlugin = {
            id: 'frozenYAxis',
            afterDraw(chart: any) {
                const overlay = yAxisCanvasRef.current;
                const yScale = chart.scales.y;
                if (!overlay || !yScale) return;
                const dpr = window.devicePixelRatio || 1;
                const cssW = overlay.clientWidth || 38;
                const cssH = chart.height;
                if (overlay.width !== Math.round(cssW * dpr) || overlay.height !== Math.round(cssH * dpr)) {
                    overlay.width = Math.round(cssW * dpr);
                    overlay.height = Math.round(cssH * dpr);
                }
                const octx = overlay.getContext('2d');
                if (!octx) return;
                octx.setTransform(dpr, 0, 0, dpr, 0, 0);
                octx.clearRect(0, 0, cssW, cssH);
                octx.font = '10px Inter, system-ui, sans-serif';
                octx.fillStyle = '#94a3b8';
                octx.textAlign = 'right';
                octx.textBaseline = 'middle';
                for (let v = 16; v <= 32; v += 1) {
                    const y = yScale.getPixelForValue(v);
                    octx.fillText(`${v}°`, cssW - 4, y);
                }
            },
        };

        // Satu warna: biru (tidak lagi per-zona suhu).
        const LINE_COLOR = '#5a93ec';

        const datasets = payload.datasets.map((ds) => {
            return {
                label: ds.room === 'Rata-rata Ruangan' ? 'Average Room' : ds.room,
                data: ds.data,
                borderColor: LINE_COLOR,
                backgroundColor: (ctx: any) => {
                    const { chart } = ctx;
                    const { ctx: c, chartArea } = chart;
                    if (!chartArea) return hexToRgba(LINE_COLOR, 0.12);
                    // Fill biru dengan gradien pudar ke bawah.
                    const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    g.addColorStop(0, hexToRgba(LINE_COLOR, 0.28));
                    g.addColorStop(0.6, hexToRgba(LINE_COLOR, 0.1));
                    g.addColorStop(1, hexToRgba(LINE_COLOR, 0));
                    return g;
                },
                borderWidth: 3,
                fill: true,
                tension: 0.45,
                spanGaps: true,
                pointRadius: 0,
                pointHoverRadius: 5,
            };
        });

        if (chartRef.current) {
            chartRef.current.data.labels = payload.labels;
            chartRef.current.data.datasets = datasets;
            chartRef.current.update();
            return;
        }

        chartRef.current = new Chart(canvas, {
            type: 'line',
            data: { labels: payload.labels, datasets },
            plugins: [frozenXAxisPlugin, frozenYAxisPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                // Padding atas-bawah agar label suhu teratas (30°) & terbawah (16°) tidak terpotong tepi.
                layout: { padding: { top: 10, bottom: 8 } },
                interaction: { mode: 'nearest', intersect: false, axis: 'x' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        displayColors: false,
                        backgroundColor: 'rgba(22,26,36,0.97)',
                        borderColor: 'rgba(255,255,255,0.12)',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 10,
                        titleColor: '#e2e8f0',
                        bodyColor: '#f8fafc',
                        callbacks: {
                            label: (item: any) => {
                                const v = item.parsed.y;
                                if (v === null || Number.isNaN(v)) return `${item.dataset.label}: —`;
                                return `${item.dataset.label}: ${v.toFixed(1)}°C`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        // Tick disembunyikan di canvas utama; digambar di sumbu beku (frozenXAxisPlugin).
                        ticks: { display: false, autoSkip: false },
                        grid: { display: false },
                        border: { display: false },
                    },
                    y: {
                        // Skala suhu 16–30°C. Label digambar oleh frozenYAxisPlugin (sumbu beku kiri),
                        // jadi label native disembunyikan; grid tiap 1°.
                        min: 16,
                        max: 32,
                        ticks: { display: false, stepSize: 1 },
                        grid: { color: 'rgba(255,255,255,0.05)', drawTicks: false },
                        border: { display: false },
                    },
                },
            },
        });
    };

    useEffect(() => {
        load(range);
        const id = window.setInterval(() => load(range), 30000);
        return () => window.clearInterval(id);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [range]);

    useEffect(() => {
        return () => {
            if (chartRef.current) {
                chartRef.current.destroy();
                chartRef.current = null;
            }
        };
    }, []);

    // Saat lebar canvas berubah (jumlah titik / range), minta Chart.js menyesuaikan ukuran + sinkron sumbu.
    useEffect(() => {
        const id = requestAnimationFrame(() => {
            chartRef.current?.resize();
            syncAxes();
        });
        return () => cancelAnimationFrame(id);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [pointCount, range]);

    return (
        <div className="panel temp-chart-panel">
            <div className="panel-header">
                <div>
                    <p className="eyebrow">
                        <i className="fa-solid fa-chart-line"></i> <span>{range === '1d' ? 'Trend last 24 hours' : 'Trend last 1 hour'}</span>
                    </p>
                    <h2 className="panel-title">Average Room Temperature</h2>
                </div>
                <div className="flex items-center gap-2 flex-wrap">
                    <span className="trend-filter">
                        <select
                            className="trend-filter-select"
                            value={range}
                            onChange={(e) => {
                                if (chartRef.current) {
                                    chartRef.current.destroy();
                                    chartRef.current = null;
                                }
                                setRange(e.target.value as Range);
                            }}
                            title="Select time range"
                        >
                            <option value="1d">1 Day</option>
                            <option value="1h">1 Hour</option>
                        </select>
                    </span>
                </div>
            </div>
            <div className="temp-chart-wrap" style={{ height: 300, position: 'relative' }}>
                {/* Sumbu Y (suhu) beku — diam horizontal, ikut geser vertikal (sinkron) */}
                <div className="tc-yaxis-vp" style={{ display: empty ? 'none' : undefined }}>
                    <canvas ref={yAxisCanvasRef} className="tc-yaxis-canvas"></canvas>
                </div>

                {/* Sumbu X (jam) beku — diam vertikal, ikut geser horizontal (sinkron) */}
                <div className="tc-xaxis-vp" style={{ display: empty ? 'none' : undefined }}>
                    <canvas ref={xAxisCanvasRef} id="tempChartXAxis" className="tc-xaxis-canvas"></canvas>
                </div>

                {/* Plot: bisa digeser ATAS-BAWAH (suhu) & KIRI-KANAN (jam) */}
                <div
                    ref={plotVpRef}
                    className="tc-plot-vp"
                    onScroll={syncAxes}
                    style={{ display: empty ? 'none' : undefined, overflowX: 'auto' }}
                >
                    <div className="tc-plot-inner" style={{ width: '100%', minWidth: pointCount ? pointCount * 50 : undefined }}>
                        <canvas ref={canvasRef}></canvas>
                    </div>
                </div>
                {empty && (
                    <div className="empty-state" style={{ position: 'absolute', inset: 0, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                        <div style={{ textAlign: 'center' }}>
                            <div className="empty-icon"><i className="fa-solid fa-temperature-empty"></i></div>
                            <p className="empty-sub">No temperature data for this range</p>
                        </div>
                    </div>
                )}
                {loading && (
                    <div style={{ position: 'absolute', inset: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', pointerEvents: 'none' }}>
                        <i className="fa-solid fa-spinner fa-spin" style={{ fontSize: 20, color: 'var(--ink-4)', opacity: 0.5 }}></i>
                    </div>
                )}
            </div>
            <p className="panel-meta" style={{ marginTop: 8, fontSize: 11, color: '#94a3b8' }}>{info}</p>
        </div>
    );
}
