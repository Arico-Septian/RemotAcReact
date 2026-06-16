import { useEffect, useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { AcStatusCard } from '@/types';
import '../../css/room-detail.css';

interface RoomDetailProps {
    room: { id: number; name: string; device_status: 'online' | 'offline' };
    acs: AcStatusCard[];
}

function formatTime(t: string | null | undefined): string {
    if (!t || t === '0000-00-00 00:00:00' || t === '00:00:00') return '';
    const s = String(t).trim();
    const timeOnly = s.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/);
    if (timeOnly) return `${timeOnly[1].padStart(2, '0')}:${timeOnly[2]}`;
    try {
        const d = new Date(s.replace(/-/g, '/').replace('T', ' ').replace(/\..*$/, ''));
        return isNaN(d.getTime())
            ? ''
            : d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false });
    } catch {
        return '';
    }
}

function AcStat({ icon, label, value, extraClass }: { icon: string; label: string; value: React.ReactNode; extraClass: string }) {
    return (
        <div className={`ac-stat ${extraClass}`}>
            <span className="label">
                <i className={`fa-solid ${icon}`}></i>
                {label}
            </span>
            <span className="value">{value}</span>
        </div>
    );
}

function AcCard({ ac }: { ac: AcStatusCard }) {
    const on = formatTime(ac.timer_on);
    const off = formatTime(ac.timer_off);
    const hasTimer = !!on || !!off;

    return (
        <div className="ac-card">
            <div className="ac-card-head">
                <div className="ac-card-head__text">
                    <p className="ac-card-head__tag">AC {ac.ac_number}</p>
                    <p className="ac-card-head__name" title={ac.label ?? ''}>
                        {ac.label}
                    </p>
                </div>
                <div className="ac-card-head__icon">
                    <i className="fa-solid fa-snowflake"></i>
                </div>
            </div>
            <AcStat icon="fa-power-off" label="Power" value={ac.power} extraClass="ic-power" />
            <AcStat icon="fa-temperature-half" label="Temp" value={`${ac.set_temperature}°C`} extraClass="ic-temp" />
            <AcStat icon="fa-fan" label="Mode" value={ac.mode} extraClass="ic-mode" />
            <AcStat icon="fa-wind" label="Fan" value={ac.fan_speed} extraClass="ic-fan" />
            <AcStat icon="fa-arrows-up-down" label="Swing" value={ac.swing} extraClass="ic-swing" />
            <AcStat
                icon="fa-clock"
                label="Timer"
                extraClass={`ic-timer ${hasTimer ? '' : 'timer-empty'}`}
                value={
                    hasTimer ? (
                        <span className="timer-times">
                            {on && <span>ON {on}</span>}
                            {off && <span>OFF {off}</span>}
                        </span>
                    ) : (
                        'OFF'
                    )
                }
            />
        </div>
    );
}

export default function RoomDetail({ room, acs: initialAcs }: RoomDetailProps) {
    const [acs, setAcs] = useState<AcStatusCard[]>(initialAcs);

    useEffect(() => {
        const ids = new Set(initialAcs.map((a) => a.id));

        const loadStatus = async () => {
            try {
                const { data } = await window.axios.get('/api/ac-status');
                if (!Array.isArray(data)) return;
                const byId = new Map<number, Partial<AcStatusCard>>();
                data.forEach((item: any) => {
                    const ac = item.ac_unit || item.acUnit;
                    const id = ac?.id ?? item.ac_unit_id;
                    if (!id || !ids.has(id)) return;
                    byId.set(id, {
                        power: (item.power || 'OFF').toUpperCase(),
                        set_temperature: item.set_temperature ?? 24,
                        mode: (item.mode || 'COOL').toUpperCase(),
                        fan_speed: (item.fan_speed || 'AUTO').toUpperCase(),
                        swing: (item.swing || 'OFF').toUpperCase(),
                        timer_on: ac?.timer_on ?? null,
                        timer_off: ac?.timer_off ?? null,
                    });
                });
                setAcs((prev) => prev.map((a) => (byId.has(a.id) ? { ...a, ...byId.get(a.id) } : a)));
            } catch {
                /* keep last good state */
            }
        };

        loadStatus();
        const intervalId = window.setInterval(loadStatus, 5000);

        const echo = window.Echo;
        if (echo) {
            const channel = echo.channel('device-status');
            channel.listen('.AcStatusUpdated', loadStatus);
            channel.listen('.DeviceStatusUpdated', loadStatus);
        }

        return () => {
            window.clearInterval(intervalId);
            try {
                echo?.leaveChannel('device-status');
            } catch {
                /* noop */
            }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <AppLayout title={room.name} subtitle="AC status snapshot">
            <Head title={`${room.name} — AC Status`} />

            {acs.length > 0 ? (
                <div className="ac-cards grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                    {acs.map((ac) => (
                        <AcCard key={ac.id} ac={ac} />
                    ))}
                </div>
            ) : (
                <div className="empty-state">
                    <div className="empty-icon"><i className="fa-solid fa-snowflake"></i></div>
                    <p className="empty-title">No AC units</p>
                    <p className="empty-sub">Add AC units from the room management page</p>
                </div>
            )}
        </AppLayout>
    );
}
