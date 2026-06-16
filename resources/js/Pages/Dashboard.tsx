import { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import TemperatureChart from '@/Components/TemperatureChart';
import type { ActivityLog, Room } from '@/types';
import '../../css/dashboard.css';

interface DashboardProps {
    rooms: Room[];
    totalRooms: number;
    totalAc: number;
    activeAc: number;
    inactiveAc: number;
    onlineRooms: number;
    offlineRooms: number;
    recentActivities: ActivityLog[];
}

interface StatCardProps {
    accent: string;
    label: string;
    value: number;
    sub: string;
    icon: string;
}

function StatCard({ accent, label, value, sub, icon }: StatCardProps) {
    return (
        <div className={`stat-card acc-${accent}`}>
            <span className="accent-bar"></span>
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="stat-label-sm">{label}</p>
                    <p className="stat-num-lg">{value}</p>
                    <p className="stat-sub">{sub}</p>
                </div>
                <div className="stat-icon">
                    <i className={`fa-solid ${icon}`}></i>
                </div>
            </div>
        </div>
    );
}

function RoomRow({ room }: { room: Room }) {
    const temperature = room.temperature ?? room.last_temperature ?? null;
    const status = room.device_status === 'online' ? 'online' : 'offline';
    return (
        <div className="dashboard-room-row" data-dashboard-room-id={room.id} data-status={status}>
            <div className="dashboard-room-main">
                <h3 className="dashboard-room-name">{room.name.charAt(0).toUpperCase() + room.name.slice(1)}</h3>
                <p className="dashboard-room-meta">
                    {room.ac_units_count} unit &middot; {room.device_id || '-'}
                </p>
            </div>
            <div className="dashboard-room-temp">
                {temperature !== null ? `${temperature.toFixed(1)}°C` : '-- °C'}
            </div>
        </div>
    );
}

function ActivityItem({ log }: { log: ActivityLog }) {
    const hasRoom = !!log.room && log.room !== '-';
    const hasAc = !!log.ac && log.ac !== '-';
    return (
        <div className={`activity-item tone-${log.tone}`}>
            <div className="activity-rail"></div>
            <div className="activity-avatar-wrap">
                {log.user_avatar ? (
                    <img src={log.user_avatar} alt={log.user_name} className="activity-avatar-img" />
                ) : (
                    <div className="activity-avatar-fallback">{log.user_initial}</div>
                )}
                <span className="activity-icon-badge">
                    <i className={log.icon}></i>
                </span>
            </div>
            <div className="activity-body">
                <div className="activity-line">
                    <span className="activity-user">{log.user_name}</span>
                    <span className="activity-time">{log.time}</span>
                </div>
                <div className="activity-desc-row">
                    <p className="activity-desc">{log.description}</p>
                    {(hasRoom || hasAc) && (
                        <span className="activity-chips">
                            {hasRoom && (
                                <span className="chip">
                                    <i className="fa-solid fa-door-open"></i>
                                    {log.room}
                                </span>
                            )}
                            {hasAc && (
                                <span className="chip">
                                    <i className="fa-solid fa-snowflake"></i>
                                    {log.ac}
                                </span>
                            )}
                        </span>
                    )}
                </div>
            </div>
        </div>
    );
}

export default function Dashboard(props: DashboardProps) {
    const [rooms, setRooms] = useState<Room[]>(props.rooms);
    const [activities, setActivities] = useState<ActivityLog[]>(props.recentActivities);
    const [stats, setStats] = useState({
        totalRooms: props.totalRooms,
        totalAc: props.totalAc,
        activeAc: props.activeAc,
        inactiveAc: props.inactiveAc,
    });

    // Sinkronkan daftar room dari props saat Inertia memuat ulang (mis. real-time room add/delete).
    useEffect(() => {
        setRooms(props.rooms);
    }, [props.rooms]);

    // Real-time sync: room ditambah/dihapus user lain -> reload prop rooms.
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

    // Poll stats + recent activities for resilience (mirrors original dashboard behavior)
    useEffect(() => {
        const refresh = async () => {
            try {
                const [statsRes, actsRes] = await Promise.all([
                    window.axios.get('/dashboard/stats'),
                    window.axios.get('/dashboard/recent-activities'),
                ]);
                setStats({
                    totalRooms: statsRes.data.total_rooms,
                    totalAc: statsRes.data.total_ac,
                    activeAc: statsRes.data.active_ac,
                    inactiveAc: statsRes.data.inactive_ac,
                });
                setActivities(actsRes.data ?? []);
            } catch {
                /* transient — keep last good state */
            }
        };
        const id = window.setInterval(refresh, 10000);
        return () => window.clearInterval(id);
    }, []);

    // Live room temperature / device status updates via Reverb
    useEffect(() => {
        const echo = window.Echo;
        if (!echo) return;

        const updateRoom = (patch: Partial<Room> & { id?: number; room_id?: number }) => {
            const id = patch.id ?? patch.room_id;
            if (id == null) return;
            setRooms((prev) => prev.map((r) => (r.id === id ? { ...r, ...patch } : r)));
        };

        const tempChannel = echo.channel('room-temperature');
        tempChannel.listen('.RoomTemperatureUpdated', (e: any) => {
            updateRoom({ id: e.room_id ?? e.id, temperature: e.temperature, last_temperature: e.temperature });
        });

        const deviceChannel = echo.channel('device-status');
        deviceChannel.listen('.DeviceStatusUpdated', (e: any) => {
            updateRoom({ id: e.room_id ?? e.id, device_status: e.status === 'online' ? 'online' : 'offline' });
        });

        return () => {
            try {
                echo.leaveChannel('room-temperature');
                echo.leaveChannel('device-status');
            } catch {
                /* noop */
            }
        };
    }, []);

    const previewRooms = rooms.slice(0, 5);

    return (
        <AppLayout title="Dashboard" subtitle="Overview & live monitoring">
            <Head title="Dashboard" />

            {/* Stat cards */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                <StatCard accent="cyan" label="Rooms" value={stats.totalRooms} sub="Registered" icon="fa-server" />
                <StatCard accent="lavender" label="AC Units" value={stats.totalAc} sub="Total unit" icon="fa-snowflake" />
                <StatCard accent="mint" label="AC Active" value={stats.activeAc} sub="Powered on" icon="fa-bolt" />
                <StatCard accent="coral" label="AC Idle" value={stats.inactiveAc} sub="Not active" icon="fa-power-off" />
            </div>

            {/* Temperature trend chart */}
            <TemperatureChart />

            {/* Bottom row: Server Rooms + Recent Activity */}
            <div className="dashboard-bottom-row">
                <section className="panel dashboard-rooms-panel">
                    <div className="panel-header">
                        <div>
                            <div className="dashboard-rooms-title-group">
                                <div>
                                    <h2 className="dashboard-rooms-title">Server Rooms</h2>
                                    <p className="dashboard-rooms-subtitle">{stats.totalRooms} rooms registered</p>
                                </div>
                            </div>
                        </div>
                        <a href="/rooms/overview" className="dashboard-rooms-action" aria-label="View all server rooms">
                            <span>View all</span>
                            <i className="fa-solid fa-chevron-right text-[10px]"></i>
                        </a>
                    </div>

                    {previewRooms.length > 0 ? (
                        <div className="dashboard-room-list">
                            {previewRooms.map((room) => (
                                <RoomRow key={room.id} room={room} />
                            ))}
                        </div>
                    ) : (
                        <div className="empty-state" style={{ padding: '28px 12px' }}>
                            <div className="empty-icon"><i className="fa-solid fa-server"></i></div>
                            <p className="empty-title">No rooms</p>
                            <p className="empty-sub">Add a room to start monitoring</p>
                        </div>
                    )}
                </section>

                <section className="panel dashboard-activity-panel">
                    <div className="activity-header">
                        <div>
                            <h2 className="activity-title">Recent Activity</h2>
                            <p className="activity-subtitle">{activities.length} recent activities</p>
                        </div>
                        <span className="activity-title-icon">
                            <i className="fa-solid fa-clock-rotate-left"></i>
                        </span>
                    </div>

                    <div className="activity-list">
                        {activities.length > 0 ? (
                            activities.slice(0, 5).map((log) => <ActivityItem key={log.id} log={log} />)
                        ) : (
                            <div className="empty-state" style={{ padding: '24px 12px' }}>
                                <div className="empty-icon"><i className="fa-solid fa-clock-rotate-left"></i></div>
                                <p className="empty-title">No activity</p>
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
