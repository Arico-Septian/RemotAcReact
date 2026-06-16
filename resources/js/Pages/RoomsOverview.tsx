import { useEffect, useMemo, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import TemperatureHistoryModal from '@/Components/TemperatureHistoryModal';
import type { OverviewRoom } from '@/types';
import '../../css/rooms-overview.css';

interface RoomsOverviewProps {
    rooms: OverviewRoom[];
}

type StatusFilter = 'all' | 'online' | 'offline';

function tempClass(temp: number | null, offline: boolean): string {
    if (offline || temp === null) return 'idle';
    if (temp > 30) return 'hot';
    if (temp > 25) return 'warm';
    return 'cool';
}

function cap(s: string): string {
    return s.charAt(0).toUpperCase() + s.slice(1);
}

interface RoomCardProps {
    room: OverviewRoom;
    onHistory: (room: OverviewRoom) => void;
}

function RoomCard({ room, onHistory }: RoomCardProps) {
    const temp = room.temperature ?? room.last_temperature ?? null;
    const online = room.device_status === 'online';
    const cls = tempClass(temp, room.temperature_is_offline);

    return (
        <div className="room-card" data-room-id={room.id} data-status={room.device_status}>
            <div className="flex items-center justify-between gap-2">
                <h3 className="font-semibold text-tight" style={{ color: 'var(--ink-0)', lineHeight: 1.3, fontSize: 16 }}>
                    {cap(room.name)}
                </h3>
                <span className={`pill room-status-pill ${online ? 'pill-online' : 'pill-offline'}`} style={{ fontSize: 12 }}>
                    <span className="room-status-text">{online ? 'Online' : 'Offline'}</span>
                </span>
            </div>

            <div className={`temp-chip ${room.temperature_is_offline ? 'idle' : cls}`} style={{ justifyContent: 'space-between', width: '100%' }}>
                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, fontWeight: 500 }}>
                    <i className="fa-solid fa-temperature-half text-[10px]"></i>Temp
                </span>
                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5 }}>
                    {room.temperature_is_offline && (
                        <i className="fa-solid fa-wifi-slash temp-offline-icon" style={{ fontSize: 11, color: 'var(--coral)' }}></i>
                    )}
                    <span className="text-mono">{temp ?? '–'}°C</span>
                </span>
            </div>

            <div className="ac-mini">
                <div>
                    <p className="num" style={{ color: '#fff', fontFamily: 'var(--font-mono)', fontSize: 16, fontWeight: 700, lineHeight: 1, margin: 0 }}>
                        {room.ac_active_count}
                    </p>
                    <p className="lbl" style={{ fontSize: 10, fontWeight: 700, letterSpacing: '0.08em', textTransform: 'uppercase', color: 'var(--ink-3)', marginTop: 4 }}>
                        Active
                    </p>
                </div>
                <div>
                    <p className="num" style={{ color: '#fff', fontFamily: 'var(--font-mono)', fontSize: 16, fontWeight: 700, lineHeight: 1, margin: 0 }}>
                        {room.ac_idle_count}
                    </p>
                    <p className="lbl" style={{ fontSize: 10, fontWeight: 700, letterSpacing: '0.08em', textTransform: 'uppercase', color: 'var(--ink-3)', marginTop: 4 }}>
                        Idle
                    </p>
                </div>
            </div>

            <p className="text-xs text-center" style={{ color: 'var(--ink-4)', marginTop: -2 }}>
                {room.ac_units_count} unit total
            </p>

            <div className="grid grid-cols-2 gap-2 mt-auto">
                <a href={`/rooms/${room.id}/status`} className="btn btn-primary btn-sm" style={{ justifyContent: 'center' }}>
                    Detail
                </a>
                <button type="button" onClick={() => onHistory(room)} className="btn btn-sm room-card-chart-btn" title="24-hour temperature history">
                    Grafik
                </button>
            </div>
        </div>
    );
}

export default function RoomsOverview({ rooms: initialRooms }: RoomsOverviewProps) {
    const [rooms, setRooms] = useState<OverviewRoom[]>(initialRooms);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState<StatusFilter>('all');
    const [history, setHistory] = useState<{ id: number; name: string } | null>(null);

    // Sinkronkan daftar room dari props saat Inertia memuat ulang (real-time room add/delete).
    useEffect(() => {
        setRooms(initialRooms);
    }, [initialRooms]);

    // Live status (5s) + temperature updates
    useEffect(() => {
        const refresh = async () => {
            try {
                const [statusRes, tempRes] = await Promise.all([
                    window.axios.get('/device-status'),
                    window.axios.get('/temperature'),
                ]);
                const statusMap = new Map<number, boolean>();
                (statusRes.data ?? []).forEach((d: any) => statusMap.set(Number(d.room_id), d.is_online === true || d.status === 'online'));
                const tempMap = new Map<number, { temp: number | null; offline: boolean }>();
                (tempRes.data ?? []).forEach((t: any) => tempMap.set(Number(t.id), { temp: t.temperature, offline: t.is_offline === true }));

                setRooms((prev) =>
                    prev.map((r) => {
                        const online = statusMap.get(r.id);
                        const t = tempMap.get(r.id);
                        return {
                            ...r,
                            device_status: online === undefined ? r.device_status : online ? 'online' : 'offline',
                            temperature: t ? t.temp : r.temperature,
                            temperature_is_offline: t ? t.offline : r.temperature_is_offline,
                        };
                    }),
                );
            } catch {
                /* keep last good state */
            }
        };
        const id = window.setInterval(refresh, 5000);
        return () => window.clearInterval(id);
    }, []);

    // Real-time sync: muat ulang daftar room saat user lain menambah/menghapus room.
    useEffect(() => {
        const echo = window.Echo;
        if (!echo) return;
        echo.channel('rooms').listen('.RoomsChanged', () => router.reload({ only: ['rooms'] }));
        return () => {
            try {
                echo.leaveChannel('rooms');
            } catch {
                /* noop */
            }
        };
    }, []);

    const filtered = useMemo(() => {
        const q = search.toLowerCase().trim();
        return rooms.filter((r) => {
            const matchSearch = !q || r.name.toLowerCase().includes(q);
            const matchStatus = status === 'all' || r.device_status === status;
            return matchSearch && matchStatus;
        });
    }, [rooms, search, status]);

    const byFloor = useMemo(() => {
        const map = new Map<string, OverviewRoom[]>();
        for (const r of filtered) {
            if (!map.has(r.floor)) map.set(r.floor, []);
            map.get(r.floor)!.push(r);
        }
        return Array.from(map.entries());
    }, [filtered]);

    return (
        <AppLayout title="Room Status" subtitle={`${rooms.length} rooms · AC monitoring`}>
            <Head title="Room Status" />

            {/* Toolbar */}
            <div className="flex flex-row items-center gap-2">
                <label className="search-input flex-1 min-w-0">
                    <i className="fa-solid fa-magnifying-glass"></i>
                    <input
                        type="text"
                        placeholder="Search room name…"
                        autoComplete="off"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </label>
                <div className="segmented flex-shrink-0">
                    {(['all', 'online', 'offline'] as StatusFilter[]).map((f) => (
                        <button key={f} className={`seg ${status === f ? 'active' : ''}`} onClick={() => setStatus(f)}>
                            {f === 'all' ? 'All' : cap(f)}
                        </button>
                    ))}
                </div>
            </div>

            {rooms.length === 0 ? (
                <div className="empty-state">
                    <div className="empty-icon"><i className="fa-solid fa-server"></i></div>
                    <p className="empty-title">No rooms</p>
                    <p className="empty-sub">Contact an administrator to add rooms</p>
                </div>
            ) : filtered.length === 0 ? (
                <div className="empty-state">
                    <div className="empty-icon"><i className="fa-solid fa-magnifying-glass"></i></div>
                    <p className="empty-title">Not found</p>
                    <p className="empty-sub">Try a different keyword or filter</p>
                </div>
            ) : (
                <div>
                    {byFloor.map(([floorName, floorRooms]) => (
                        <div className="floor-section" key={floorName}>
                            <div className="floor-section-header">
                                <i className="fa-solid fa-layer-group text-[10px]" style={{ color: '#fff' }}></i>
                                <span className="floor-label">{cap(floorName)}</span>
                                <div className="floor-divider"></div>
                                <span className="floor-count">{floorRooms.length} rooms</span>
                            </div>
                            <div className="floor-grid grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3 mb-6">
                                {floorRooms.map((room) => (
                                    <RoomCard key={room.id} room={room} onHistory={(r) => setHistory({ id: r.id, name: cap(r.name) })} />
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {history && <TemperatureHistoryModal roomId={history.id} roomName={history.name} onClose={() => setHistory(null)} />}
        </AppLayout>
    );
}
