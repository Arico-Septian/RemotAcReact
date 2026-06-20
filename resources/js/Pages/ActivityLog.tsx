import { useEffect, useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import type { ActivityLogRow, PageProps } from '@/types';
import '../../css/activity-log.css';

interface Stats {
    total: number;
    add_room: number;
    add_room24: number;
    delete_room: number;
    delete_room24: number;
    ac: number;
}

interface Pagination {
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    prev_url: string | null;
    next_url: string | null;
}

interface ActivityLogProps {
    logs: ActivityLogRow[];
    stats: Stats;
    filters: { search: string; activity: string; range: string };
    pagination: Pagination;
}

const quickCats: { value: string; label: string }[] = [
    { value: '', label: 'All' },
    { value: 'auth', label: 'Auth' },
    { value: 'ac', label: 'AC' },
    { value: 'room', label: 'Room' },
    { value: 'user', label: 'User' },
];

function StatCard({ accent, label, value, sub, icon }: { accent: string; label: string; value: number; sub: string; icon: string }) {
    return (
        <div className={`stat-card acc-${accent}`}>
            <span className="accent-bar"></span>
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="stat-label-sm">{label}</p>
                    <p className="stat-num-lg">{value}</p>
                    <p className="stat-sub">{sub}</p>
                </div>
                <div className="stat-icon"><i className={`fa-solid ${icon}`}></i></div>
            </div>
        </div>
    );
}

function Avatar({ name, src }: { name: string; src: string | null }) {
    if (src) {
        return <img src={src} alt={name} className="avatar" style={{ width: 34, height: 34, borderRadius: 10, flexShrink: 0, objectFit: 'cover' }} />;
    }
    return (
        <span className="avatar" style={{ width: 34, height: 34, fontSize: 13, borderRadius: 10, flexShrink: 0 }}>
            {(name || '?').charAt(0).toUpperCase()}
        </span>
    );
}

export default function ActivityLog({ logs, stats, filters, pagination }: ActivityLogProps) {
    const { auth } = usePage<PageProps>().props;
    const isAdmin = auth.user?.role === 'admin';
    const [search, setSearch] = useState(filters.search);
    const [deletingAll, setDeletingAll] = useState(false);
    const cat = quickCats.some((c) => c.value === filters.activity) ? filters.activity : '';
    const searchTimer = useRef<number | null>(null);

    const applyFilters = (next: { search?: string; activity?: string }) => {
        const params: Record<string, string> = {
            search: next.search ?? search,
            activity: next.activity ?? filters.activity,
            range: filters.range,
        };
        Object.keys(params).forEach((k) => params[k] === '' && delete params[k]);
        router.get('/logs', params, { preserveState: true, preserveScroll: true, replace: true });
    };

    const onSearchChange = (value: string) => {
        setSearch(value);
        if (searchTimer.current) window.clearTimeout(searchTimer.current);
        searchTimer.current = window.setTimeout(() => applyFilters({ search: value }), 350);
    };

    const deleteAll = async () => {
        if (deletingAll || !window.confirm('Delete ALL activity logs? This cannot be undone.')) return;
        setDeletingAll(true);
        try {
            await window.axios.delete('/logs/delete-all');
            router.reload();
            window.smToast?.('Semua log berhasil dihapus', 'success');
        } catch (err: any) {
            const msg = err?.response?.data?.message ?? 'Failed to delete logs';
            if (window.smToast) window.smToast(msg, 'error');
            else window.alert(msg);
        } finally {
            setDeletingAll(false);
        }
    };

    // Real-time: log baru dibuat / semua log dihapus -> muat ulang (channel device-status).
    useEffect(() => {
        const echo = window.Echo;
        if (!echo) return;
        const reload = () => router.reload({ only: ['logs', 'stats', 'pagination'] });
        const channel = echo.channel('device-status');
        channel.listen('.UserLogCreated', reload).listen('.UserLogsCleared', reload);
        return () => {
            try {
                echo.leaveChannel('device-status');
            } catch {
                /* noop */
            }
        };
    }, []);

    return (
        <AppLayout title="Activity Log" subtitle="System & user activity">
            <Head title="Activity Log" />

            {/* Stats */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                <StatCard accent="cyan" label="Total Activity" value={stats.total} sub={`Page ${pagination.current_page} / ${pagination.last_page}`} icon="fa-clock-rotate-left" />
                <StatCard accent="mint" label="Add Room" value={stats.add_room} sub={`+${stats.add_room24} dalam 24 jam`} icon="fa-square-plus" />
                <StatCard accent="coral" label="Delete Room" value={stats.delete_room} sub={`+${stats.delete_room24} dalam 24 jam`} icon="fa-trash" />
                <StatCard accent="lavender" label="AC Control" value={stats.ac} sub="on/off · mode · temp" icon="fa-snowflake" />
            </div>

            <div className="tbl-wrap">
                {/* Toolbar */}
                <div className="tbl-toolbar">
                    <label className="search-input" style={{ flex: 1, maxWidth: 'none' }}>
                        <i className="fa-solid fa-magnifying-glass"></i>
                        <input value={search} onChange={(e) => onSearchChange(e.target.value)} placeholder="Search user / room / activity…" autoComplete="off" />
                        {search && (
                            <button type="button" className="clear" title="Clear" onClick={() => { setSearch(''); applyFilters({ search: '' }); }}>
                                <i className="fa-solid fa-xmark text-[10px]"></i>
                            </button>
                        )}
                    </label>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexShrink: 0 }}>
                        <div className="segmented">
                            {quickCats.map((c) => (
                                <button key={c.value} type="button" className={`seg ${cat === c.value ? 'active' : ''}`} onClick={() => applyFilters({ activity: c.value })}>
                                    {c.label}
                                </button>
                            ))}
                        </div>
                        {isAdmin && (
                            <button type="button" onClick={deleteAll} disabled={deletingAll} className="btn btn-danger btn-sm" title="Delete All Logs">
                                <i className={`fa-solid ${deletingAll ? 'fa-spinner fa-spin' : 'fa-trash'} text-[15px]`}></i>
                            </button>
                        )}
                    </div>
                </div>

                {/* Mobile cards */}
                <div className="md:hidden">
                    {logs.length === 0 ? (
                        <div className="empty-state">
                            <div className="empty-icon"><i className="fa-solid fa-magnifying-glass"></i></div>
                            <p className="empty-title">No activities found</p>
                            <p className="empty-sub">Try adjusting your filters</p>
                        </div>
                    ) : (
                        logs.map((log) => {
                            const roomAc = [log.room, log.ac].filter(Boolean).join(' · ');
                            return (
                                <div key={log.id} style={{ padding: '14px 16px', borderBottom: '1px solid rgba(255,255,255,0.15)' }}>
                                    <div className="flex items-center gap-3">
                                        <Avatar name={log.user_name} src={log.user_avatar} />
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center justify-between gap-2">
                                                <div className="flex items-center gap-2 min-w-0">
                                                    <span className="truncate" style={{ fontSize: 14, fontWeight: 600, color: 'var(--ink-0)' }}>{log.user_name}</span>
                                                    <span className={`act-badge ${log.badge_class}`} style={{ flexShrink: 0 }}>{log.badge_label}</span>
                                                </div>
                                                <span className="text-mono" style={{ fontSize: 12, color: 'var(--ink-2)', whiteSpace: 'nowrap', flexShrink: 0, fontWeight: 600 }}>{log.time}</span>
                                            </div>
                                            <div className="flex items-center justify-between gap-2" style={{ marginTop: 5 }}>
                                                <span className="truncate" style={{ fontSize: 12, color: 'var(--ink-3)' }}>{roomAc || '—'}</span>
                                                <span className="text-mono" style={{ fontSize: 12, color: 'var(--ink-4)', whiteSpace: 'nowrap', flexShrink: 0 }}>{log.date}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>

                {/* Desktop table */}
                <div className="hidden md:block" style={{ overflowX: 'auto' }}>
                    <table className="tbl tbl-log">
                        <thead>
                            <tr>
                                <th style={{ width: '20%' }}>USER</th>
                                <th style={{ width: '20%' }}>ROOM</th>
                                <th style={{ width: '20%' }}>DETAIL</th>
                                <th style={{ width: '20%' }}>ACTIVITY</th>
                                <th style={{ width: '20%' }} className="whitespace-nowrap">TIME</th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.length === 0 ? (
                                <tr>
                                    <td colSpan={5}>
                                        <div className="empty-state">
                                            <div className="empty-icon"><i className="fa-solid fa-magnifying-glass"></i></div>
                                            <p className="empty-title">No activities found</p>
                                            <p className="empty-sub">Try adjusting your filters</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                logs.map((log) => (
                                    <tr key={log.id}>
                                        <td>
                                            <div className="log-user">
                                                <Avatar name={log.user_name} src={log.user_avatar} />
                                                <span className="name">{log.user_name}</span>
                                            </div>
                                        </td>
                                        <td>{log.room ? <span className="log-room">{log.room}</span> : <span className="log-empty">—</span>}</td>
                                        <td>{log.ac ? <span className="log-detail" title={log.ac}>{log.ac}</span> : <span className="log-empty">—</span>}</td>
                                        <td><span className={`act-badge ${log.badge_class}`}>{log.badge_label}</span></td>
                                        <td>
                                            <div className="log-time">
                                                <span className="t">{log.time}</span>
                                                <span className="d">{log.date}</span>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination pagination={pagination} label="aktivitas" />
            </div>
        </AppLayout>
    );
}
