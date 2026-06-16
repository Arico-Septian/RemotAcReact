import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

interface HistoryPoint {
    time: string;
    temp: number | null;
}

type Range = '1h' | 'today';

interface Props {
    roomId: number;
    roomName: string;
    onClose: () => void;
}

const rangeText: Record<Range, string> = {
    '1h': 'Last 1 hour',
    today: 'Last 1 day',
};

// Satu warna: biru.
const LINE_COLOR = 'rgba(90,147,236,0.95)';

export default function TemperatureHistoryModal({ roomId, roomName, onClose }: Props) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const xAxisCanvasRef = useRef<HTMLCanvasElement>(null);
    const yAxisCanvasRef = useRef<HTMLCanvasElement>(null);
    const plotVpRef = useRef<HTMLDivElement>(null);
    const chartRef = useRef<any>(null);
    const [range, setRange] = useState<Range>(() => {
        const saved = localStorage.getItem('historyRange');
        return saved === '1h' ? '1h' : 'today';
    });
    const [state, setState] = useState<'loading' | 'empty' | 'ready'>('loading');
    const [points, setPoints] = useState<HistoryPoint[]>([]);

    // Sinkronkan sumbu beku dengan scroll plot: Y ikut geser vertikal, X ikut geser horizontal.
    const syncAxes = () => {
        const vp = plotVpRef.current;
        if (!vp) return;
        if (yAxisCanvasRef.current) yAxisCanvasRef.current.style.transform = `translateY(${-vp.scrollTop}px)`;
        if (xAxisCanvasRef.current) xAxisCanvasRef.current.style.transform = `translateX(${-vp.scrollLeft}px)`;
    };

    const load = async (r: Range) => {
        setState('loading');
        try {
            const { data } = await window.axios.get<HistoryPoint[]>(`/temperature/history/${roomId}?range=${r}`);
            if (!Array.isArray(data) || data.length === 0) {
                setPoints([]);
                setState('empty');
                return;
            }
            setPoints(data);
            setState('ready');
        } catch {
            setPoints([]);
            setState('empty');
        }
    };

    const renderChart = (pts: HistoryPoint[]) => {
        const canvas = canvasRef.current;
        const Chart = window.Chart;
        if (!canvas || !Chart) return;

        const labels = pts.map((p) => p.time);
        const values = pts.map((p) => p.temp);

        // Sumbu jam "beku" (bawah): digambar di canvas terpisah, ikut geser horizontal (sinkron).
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
                const lbls: string[] = chart.data.labels || [];
                let lastX = -Infinity;
                (xScale.ticks || []).forEach((_t: any, i: number) => {
                    const label = lbls[i];
                    if (!label) return;
                    // Tampilkan setiap label (per-menit untuk 1h, per-jam untuk 1d); penjaga jarak 40px menjaga keterbacaan.
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

        // Sumbu suhu (Y) "beku" (kiri): label 16–30 tiap 1°, ikut geser vertikal (sinkron).
        const frozenYAxisPlugin = {
            id: 'frozenYAxis',
            afterDraw(chart: any) {
                const overlay = yAxisCanvasRef.current;
                const yScale = chart.scales.y;
                if (!overlay || !yScale) return;
                const dpr = window.devicePixelRatio || 1;
                const cssW = overlay.clientWidth || 34;
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

        if (chartRef.current) {
            chartRef.current.destroy();
            chartRef.current = null;
        }

        chartRef.current = new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Suhu',
                        data: values,
                        borderColor: LINE_COLOR,
                        backgroundColor: (ctx: any) => {
                            const { chart } = ctx;
                            const { ctx: c, chartArea } = chart;
                            if (!chartArea) return 'rgba(90,147,236,0.12)';
                            // Fill biru dengan gradien pudar ke bawah.
                            const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                            g.addColorStop(0, 'rgba(90,147,236,0.30)');
                            g.addColorStop(0.6, 'rgba(90,147,236,0.10)');
                            g.addColorStop(1, 'rgba(90,147,236,0)');
                            return g;
                        },
                        borderWidth: 2.6,
                        fill: true,
                        tension: 0.4,
                        spanGaps: true,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                    },
                ],
            },
            plugins: [frozenXAxisPlugin, frozenYAxisPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                // Padding agar label suhu teratas (30°) & terbawah (16°) tidak terpotong tepi.
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
                        callbacks: {
                            label: (item: any) => {
                                const v = item.parsed.y;
                                return v === null || Number.isNaN(v) ? '—' : `${v.toFixed(1)}°C`;
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
                        // Skala suhu 16–30°C; label digambar oleh frozenYAxisPlugin (sumbu beku kiri).
                        min: 16,
                        max: 32,
                        ticks: { display: false, stepSize: 1 },
                        grid: { color: 'rgba(255,255,255,0.05)', drawTicks: false },
                        border: { display: false },
                    },
                },
            },
        });

        requestAnimationFrame(syncAxes);
    };

    useEffect(() => {
        load(range);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [range]);

    // Render only after the canvas container is visible & laid out (post-commit),
    // otherwise Chart.js measures a 0-size canvas and draws nothing.
    useEffect(() => {
        if (state === 'ready' && points.length > 0) {
            renderChart(points);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [state, points]);

    // Escape-to-close. Kept separate from chart lifecycle so a changing
    // onClose reference (parent re-renders) never tears down the chart.
    useEffect(() => {
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [onClose]);

    // Destroy the chart only when the modal actually unmounts.
    useEffect(() => {
        return () => {
            if (chartRef.current) {
                chartRef.current.destroy();
                chartRef.current = null;
            }
        };
    }, []);

    return createPortal(
        <div className="modal-backdrop" style={{ display: 'flex' }} onClick={onClose}>
            <div className="modal modal-lg" style={{ maxWidth: 780 }} onClick={(e) => e.stopPropagation()}>
                <div className="modal-header">
                    <div className="history-title-group">
                        <p className="eyebrow" style={{ color: '#5a93ec' }}>
                            <i className="fa-solid fa-chart-line"></i> Temperature History
                        </p>
                        <h2 id="historyTitle">{roomName}</h2>
                        <p className="sub">{rangeText[range]}</p>
                    </div>
                    <div className="history-actions">
                        <select
                            className="history-range-select"
                            value={range}
                            onChange={(e) => {
                                const r = e.target.value as Range;
                                localStorage.setItem('historyRange', r);
                                setRange(r);
                            }}
                            title="Select history range"
                        >
                            <option value="1h">1 Hour</option>
                            <option value="today">1 Day</option>
                        </select>
                        <button type="button" className="modal-close" onClick={onClose} title="Close" aria-label="Close">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
                <div className="modal-body">
                    {state === 'loading' && (
                        <div className="empty-state" style={{ padding: '36px 0' }}>
                            <div className="empty-icon"><i className="fa-solid fa-spinner fa-spin"></i></div>
                            <p className="empty-sub">Memuat data…</p>
                        </div>
                    )}
                    {state === 'empty' && (
                        <div className="empty-state" style={{ padding: '36px 0' }}>
                            <div className="empty-icon"><i className="fa-solid fa-temperature-empty"></i></div>
                            <p className="empty-sub">No temperature data in this range</p>
                        </div>
                    )}
                    {/* Frozen panes: Y beku kiri (geser vertikal), X beku bawah (geser horizontal), plot scroll 2 arah. */}
                    <div style={{ height: 300, position: 'relative', display: state === 'ready' ? 'block' : 'none' }}>
                        {/* Sumbu Y (suhu) beku */}
                        <div style={{ position: 'absolute', left: 0, top: 0, bottom: 28, width: 34, overflow: 'hidden', zIndex: 2, pointerEvents: 'none' }}>
                            <canvas ref={yAxisCanvasRef} style={{ position: 'absolute', top: 0, left: 0, width: 34, height: 340, willChange: 'transform' }}></canvas>
                        </div>
                        {/* Sumbu X (jam) beku */}
                        <div style={{ position: 'absolute', left: 34, right: 0, bottom: 0, height: 28, overflow: 'hidden', pointerEvents: 'none' }}>
                            <canvas ref={xAxisCanvasRef} style={{ position: 'absolute', left: 0, bottom: 0, height: 28, willChange: 'transform' }}></canvas>
                        </div>
                        {/* Plot: scroll atas-bawah (suhu) & kiri-kanan (jam) */}
                        <div
                            ref={plotVpRef}
                            onScroll={syncAxes}
                            style={{ position: 'absolute', left: 34, right: 0, top: 0, bottom: 28, overflowX: 'auto', overflowY: 'auto' }}
                        >
                            <div style={{ position: 'relative', height: 340, width: '100%', minWidth: points.length ? points.length * 50 : undefined }}>
                                <canvas ref={canvasRef}></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>,
        document.body,
    );
}
