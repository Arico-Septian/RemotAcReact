import { FormEvent, useEffect, useMemo, useState } from 'react';
import { createPortal } from 'react-dom';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { ManageRoom, PageProps } from '@/types';
import '../../css/rooms-manage.css';

interface RoomsManageProps {
    rooms: ManageRoom[];
    search: string;
}

type StatusFilter = 'all' | 'online' | 'offline';

function cap(s: string): string {
    return s.charAt(0).toUpperCase() + s.slice(1);
}

function tempClass(temp: number | null, offline: boolean): string {
    if (offline || temp === null) return 'idle';
    if (temp > 30) return 'hot';
    if (temp > 25) return 'warm';
    return 'cool';
}

const decisionClass: Record<string, string> = {
    TURUNKAN: 'keputusan-yellow',
    NAIKKAN: 'keputusan-cool',
    DIAM: 'keputusan-warm',
};

interface RoomCardProps {
    room: ManageRoom;
    canManage: boolean;
    onDelete: (room: ManageRoom) => void;
}

function RoomCard({ room, canManage, onDelete }: RoomCardProps) {
    const online = room.device_status === 'online';
    const temp = room.temperature ?? room.last_temperature ?? null;
    const tcls = tempClass(temp, room.temperature_is_offline);
    const action = (room.decision?.action ?? 'DIAM').toString().toUpperCase();

    return (
        <div className="room-card" data-status={room.device_status}>
            <div className="flex items-center justify-between gap-2">
                <h2 className="font-semibold text-tight truncate" style={{ color: 'var(--ink-0)', fontSize: 16, lineHeight: 1.3 }}>
                    {cap(room.name)}
                </h2>
                <span className={`pill room-status-pill ${online ? 'pill-online' : 'pill-offline'}`} style={{ fontSize: 12, flexShrink: 0 }}>
                    <span className="room-status-text">{online ? 'Online' : 'Offline'}</span>
                </span>
            </div>

            <div className={`temp-chip ${room.temperature_is_offline ? 'idle' : tcls}`} style={{ justifyContent: 'space-between', width: '100%' }}>
                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, fontWeight: 500 }}>
                    <i className="fa-solid fa-temperature-half text-[10px]"></i>Temp
                </span>
                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5 }}>
                    {room.temperature_is_offline && <i className="fa-solid fa-wifi-slash temp-offline-icon" style={{ fontSize: 11, color: 'var(--coral)' }}></i>}
                    <span className="text-mono">{temp ?? '–'}°C</span>
                </span>
            </div>

            {room.temperature !== null && (
                <div className={`temp-chip ${room.temperature_is_offline ? 'idle' : tcls} mt-2`} style={{ justifyContent: 'space-between', width: '100%' }}>
                    <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, fontWeight: 500 }}>ΔT</span>
                    <span className="text-mono">{room.delta_t ?? 0}</span>
                </div>
            )}

            {room.fuzzy && (
                <div className="mt-2" style={{ background: 'var(--panel-1)', border: '1px solid var(--line-soft)', borderRadius: 'var(--r-md)', padding: '8px 10px' }}>
                    <div className="flex items-center justify-between mt-1" style={{ fontSize: 11 }}>
                        <span style={{ color: 'var(--ink-3)', flexShrink: 0 }}>Tingkat Pendinginan</span>
                        <span className="text-mono" style={{ fontWeight: 700, color: 'var(--mint)', whiteSpace: 'nowrap', marginLeft: 6 }}>
                            {room.fuzzy.status_pendinginan ?? '-'}
                        </span>
                    </div>
                    {room.decision && (
                        <div style={{ fontSize: 11, color: 'var(--ink-3)', marginTop: 4 }}>
                            <div className="flex items-center justify-between">
                                <span style={{ flexShrink: 0 }}>Keputusan</span>
                                <span className={`text-mono ${decisionClass[action] ?? 'keputusan-idle'}`} style={{ fontWeight: 700, whiteSpace: 'nowrap', marginLeft: 6 }}>
                                    {action}
                                </span>
                            </div>
                            <div className="flex items-center justify-between" style={{ marginTop: 2, color: 'var(--ink-4)' }}>
                                <span style={{ flexShrink: 0 }}>Setpoint</span>
                                <span className="text-mono" style={{ whiteSpace: 'nowrap', marginLeft: 6 }}>
                                    {room.decision.setpoint_before ?? '-'} &rarr; {room.decision.setpoint_after ?? '-'}
                                </span>
                            </div>
                        </div>
                    )}
                </div>
            )}

            <div className="grid grid-cols-2 gap-2">
                <div style={{ background: 'var(--panel-1)', border: '1px solid var(--line-soft)', borderRadius: 'var(--r-md)', padding: '8px 6px', textAlign: 'center' }}>
                    <p className="text-mono text-base font-bold" style={{ color: '#fff', fontFamily: 'var(--font-mono)', fontSize: 16, fontWeight: 700, lineHeight: 1, margin: 0 }}>
                        {room.ac_active_count}
                    </p>
                    <p className="label-tag mt-1" style={{ fontSize: 10, fontWeight: 700, letterSpacing: '0.08em', textTransform: 'uppercase', color: 'var(--ink-3)', marginTop: 4 }}>
                        Active
                    </p>
                </div>
                <div style={{ background: 'var(--panel-1)', border: '1px solid var(--line-soft)', borderRadius: 'var(--r-md)', padding: '8px 6px', textAlign: 'center' }}>
                    <p className="text-mono text-base font-bold" style={{ color: '#fff', fontFamily: 'var(--font-mono)', fontSize: 16, fontWeight: 700, lineHeight: 1, margin: 0 }}>
                        {room.ac_idle_count}
                    </p>
                    <p className="label-tag mt-1" style={{ fontSize: 10, fontWeight: 700, letterSpacing: '0.08em', textTransform: 'uppercase', color: 'var(--ink-3)', marginTop: 4 }}>
                        Idle
                    </p>
                </div>
            </div>

            <p className="text-xs text-center" style={{ color: 'var(--ink-4)', marginTop: -2 }}>
                {room.ac_units_count} unit total
            </p>

            <div className="grid grid-cols-2 gap-2 mt-auto">
                <a href={`/rooms/${room.id}/ac`} className="btn btn-primary btn-sm" style={{ justifyContent: 'center' }}>
                    Control AC
                </a>
                {canManage && (
                    <button type="button" className="btn btn-danger btn-sm" style={{ justifyContent: 'center' }} onClick={() => onDelete(room)} aria-label={`Delete room ${room.name}`}>
                        Delete
                    </button>
                )}
            </div>
        </div>
    );
}

export default function RoomsManage({ rooms }: RoomsManageProps) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user?.role === 'admin' || auth.user?.role === 'operator';

    const [search, setSearch] = useState('');
    const [status, setStatus] = useState<StatusFilter>('all');
    const [modalOpen, setModalOpen] = useState(false);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        name: '',
        device_id: '',
        floor: '',
    });

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
        const map = new Map<string, ManageRoom[]>();
        for (const r of filtered) {
            if (!map.has(r.floor)) map.set(r.floor, []);
            map.get(r.floor)!.push(r);
        }
        return Array.from(map.entries());
    }, [filtered]);

    const closeModal = () => {
        setModalOpen(false);
        reset();
        clearErrors();
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        // Server normalizes (lowercase/trim) and validates no-spaces; send as typed.
        post('/rooms', {
            onSuccess: () => {
                closeModal();
                window.smToast?.('Room berhasil ditambahkan', 'success');
            },
            preserveScroll: true,
        });
    };

    const deleteRoom = (room: ManageRoom) => {
        if (window.confirm('Delete this room and all its AC units?')) {
            router.delete(`/rooms/${room.id}`, {
                preserveScroll: true,
                onSuccess: () => window.smToast?.('Room berhasil dihapus', 'success'),
            });
        }
    };

    return (
        <AppLayout title="Rooms & AC Units" subtitle="Manage server rooms">
            <Head title="Rooms & AC Units" />

            <div className="flex items-center gap-2">
                <label className="search-input flex-1 min-w-0">
                    <i className="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search rooms…" autoComplete="off" value={search} onChange={(e) => setSearch(e.target.value)} />
                </label>
                <div className="flex gap-2 flex-shrink-0 items-center">
                    <div className="segmented">
                        {(['all', 'online', 'offline'] as StatusFilter[]).map((f) => (
                            <button key={f} type="button" className={`seg ${status === f ? 'active' : ''}`} onClick={() => setStatus(f)}>
                                {f === 'all' ? 'All' : cap(f)}
                            </button>
                        ))}
                    </div>
                    {canManage && (
                        <button onClick={() => setModalOpen(true)} className="btn btn-primary btn-sm btn-add" type="button">
                            <i className="fa-solid fa-plus text-[10px]"></i>
                            <span>Add Room</span>
                        </button>
                    )}
                </div>
            </div>

            {rooms.length === 0 ? (
                <div className="empty-state">
                    <div className="empty-icon"><i className="fa-solid fa-server"></i></div>
                    <p className="empty-title">No rooms</p>
                    <p className="empty-sub">Add a room to get started</p>
                </div>
            ) : filtered.length === 0 ? (
                <div className="empty-state">
                    <div className="empty-icon"><i className="fa-solid fa-magnifying-glass"></i></div>
                    <p className="empty-title">Not found</p>
                    <p className="empty-sub">Try a different status filter</p>
                </div>
            ) : (
                <div className="space-y-2">
                    {byFloor.map(([floorName, floorRooms]) => (
                        <section className="floor-section" key={floorName}>
                            <div className="floor-section-header">
                                <i className="fa-solid fa-layer-group text-[10px]" style={{ color: '#fff' }}></i>
                                <span className="floor-label">{cap(floorName)}</span>
                                <div className="floor-divider"></div>
                                <span className="floor-count">{floorRooms.length} rooms</span>
                            </div>
                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 mb-6">
                                {floorRooms.map((room) => (
                                    <RoomCard key={room.id} room={room} canManage={canManage} onDelete={deleteRoom} />
                                ))}
                            </div>
                        </section>
                    ))}
                </div>
            )}

            {canManage && modalOpen && createPortal(
                <div className="modal-backdrop is-open" onClick={(e) => e.target === e.currentTarget && closeModal()}>
                    <div className="modal">
                        <div className="modal-header">
                            <div>
                                <p className="eyebrow"><i className="fa-solid fa-plus"></i> New</p>
                                <h2>Add Room</h2>
                                <p className="sub">Register a new room with its ESP device</p>
                            </div>
                        </div>
                        <form onSubmit={submit}>
                            <div className="modal-body space-y-3">
                                <div className="field">
                                    <label className="field-label">Room Name</label>
                                    <input className="input text-mono" type="text" placeholder="server_1" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                                    <p className="field-help" style={errors.name ? { color: 'var(--coral)' } : undefined}>
                                        {errors.name ?? 'Letters, numbers, and underscores (no spaces)'}
                                    </p>
                                </div>
                                <div className="field">
                                    <label className="field-label">ESP Device ID</label>
                                    <input className="input text-mono" type="text" placeholder="esp32_01" value={data.device_id} onChange={(e) => setData('device_id', e.target.value)} required />
                                    <p className="field-help" style={errors.device_id ? { color: 'var(--coral)' } : undefined}>
                                        {errors.device_id ?? 'Letters, numbers, underscores, and dashes (no spaces)'}
                                    </p>
                                </div>
                                <div className="field">
                                    <label className="field-label">
                                        Floor / Zone <span style={{ color: 'var(--ink-4)', fontWeight: 400 }}>(optional)</span>
                                    </label>
                                    <input className="input text-mono" type="text" placeholder="floor_1" value={data.floor} onChange={(e) => setData('floor', e.target.value)} />
                                    <p className="field-help" style={errors.floor ? { color: 'var(--coral)' } : undefined}>
                                        {errors.floor ?? 'Letters, numbers, and underscores (no spaces)'}
                                    </p>
                                </div>
                            </div>
                            <div className="modal-footer">
                                <button type="button" className="btn btn-ghost" onClick={closeModal}>Cancel</button>
                                <button type="submit" className="btn btn-primary" disabled={processing}>
                                    {processing ? 'Creating…' : 'Create Room'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>,
                document.body,
            )}
        </AppLayout>
    );
}
