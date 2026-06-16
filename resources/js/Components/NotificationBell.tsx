import { useEffect, useRef, useState } from 'react';

interface NotificationItem {
    id: number;
    type: string;
    severity: string;
    title: string;
    message: string;
    link: string | null;
    is_unread: boolean;
    time_ago: string;
    created_at: string;
}

const severityIcon: Record<string, string> = {
    critical: 'fa-circle-exclamation',
    warning: 'fa-triangle-exclamation',
    success: 'fa-circle-check',
    info: 'fa-circle-info',
};

export default function NotificationBell() {
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState<NotificationItem[]>([]);
    const [unread, setUnread] = useState(0);
    const wrapRef = useRef<HTMLDivElement>(null);

    const load = async () => {
        try {
            const { data } = await window.axios.get('/notifications/recent');
            setItems(data.items ?? []);
            setUnread(data.unread_count ?? 0);
        } catch {
            /* ignore transient errors */
        }
    };

    useEffect(() => {
        load();
        const id = window.setInterval(load, 15000);
        return () => window.clearInterval(id);
    }, []);

    // Live updates via Reverb
    useEffect(() => {
        const echo = window.Echo;
        if (!echo) return;
        const channel = echo.channel('notifications');
        channel.listen('.NotificationCreated', () => load());
        return () => {
            try {
                echo.leaveChannel('notifications');
            } catch {
                /* noop */
            }
        };
    }, []);

    useEffect(() => {
        const onClick = (e: MouseEvent) => {
            if (wrapRef.current && !wrapRef.current.contains(e.target as Node)) setOpen(false);
        };
        document.addEventListener('click', onClick);
        return () => document.removeEventListener('click', onClick);
    }, []);

    const markAllRead = async () => {
        try {
            await window.axios.post('/notifications/read-all');
            setUnread(0);
            setItems((prev) => prev.map((n) => ({ ...n, is_unread: false })));
        } catch {
            /* ignore */
        }
    };

    return (
        <div ref={wrapRef} style={{ position: 'relative' }}>
            <button
                type="button"
                title="Notifications"
                aria-label="Notifications"
                onClick={() => setOpen((v) => !v)}
                style={{
                    position: 'relative',
                    background: 'transparent',
                    border: 'none',
                    boxShadow: 'none',
                    padding: 4,
                    cursor: 'pointer',
                    color: 'var(--ink-2)',
                    fontSize: 18,
                    lineHeight: 1,
                    display: 'inline-flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                }}
            >
                <i className="fa-regular fa-bell"></i>
                {unread > 0 && (
                    <span
                        style={{
                            position: 'absolute',
                            top: 0,
                            right: 0,
                            minWidth: 11,
                            height: 11,
                            padding: '0 2px',
                            borderRadius: 999,
                            background: '#ef4444',
                            color: '#fff',
                            fontSize: 8,
                            fontWeight: 800,
                            display: 'grid',
                            placeItems: 'center',
                            lineHeight: 1,
                        }}
                    >
                        {unread > 99 ? '99+' : unread}
                    </span>
                )}
            </button>

            {open && (
                <div
                    className="notif-dropdown"
                    style={{
                        position: 'absolute',
                        right: 0,
                        top: 'calc(100% + 8px)',
                        width: 340,
                        maxWidth: '90vw',
                        background: 'var(--panel-1, #181b28)',
                        border: '1px solid var(--line, rgba(255,255,255,0.12))',
                        borderRadius: 14,
                        boxShadow: '0 18px 50px rgba(0,0,0,0.45)',
                        zIndex: 80,
                        overflow: 'hidden',
                    }}
                >
                    <div
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            padding: '12px 14px',
                            borderBottom: '1px solid var(--line-soft, rgba(255,255,255,0.08))',
                        }}
                    >
                        <span style={{ fontWeight: 700, color: 'var(--ink-0, #fff)' }}>Notifications</span>
                        <button
                            onClick={markAllRead}
                            style={{ background: 'none', border: 'none', color: '#fff', fontSize: 12, cursor: 'pointer' }}
                        >
                            Mark all read
                        </button>
                    </div>

                    <div style={{ maxHeight: 360, overflowY: 'auto' }}>
                        {items.length === 0 ? (
                            <div style={{ padding: 24, textAlign: 'center', color: 'var(--ink-4, #64748b)', fontSize: 13 }}>
                                No notifications
                            </div>
                        ) : (
                            items.map((n) => (
                                <a
                                    key={n.id}
                                    href={n.link ?? '/notifications'}
                                    style={{
                                        display: 'flex',
                                        gap: 10,
                                        padding: '12px 14px',
                                        borderBottom: '1px solid var(--line-soft, rgba(255,255,255,0.06))',
                                        textDecoration: 'none',
                                        background: n.is_unread ? 'rgba(255,255,255,0.08)' : 'transparent',
                                    }}
                                >
                                    <i
                                        className={`fa-solid ${severityIcon[n.severity] ?? 'fa-circle-info'}`}
                                        style={{ color: 'var(--ink-2, #cbd5e1)', marginTop: 2 }}
                                    ></i>
                                    <div style={{ minWidth: 0 }}>
                                        <p style={{ margin: 0, fontSize: 13, fontWeight: 600, color: 'var(--ink-0, #fff)' }}>{n.title}</p>
                                        <p style={{ margin: '2px 0 0', fontSize: 12, color: 'var(--ink-3, #94a3b8)' }}>{n.message}</p>
                                        <p style={{ margin: '3px 0 0', fontSize: 11, color: 'var(--ink-4, #64748b)' }}>{n.time_ago}</p>
                                    </div>
                                </a>
                            ))
                        )}
                    </div>

                    <a
                        href="/notifications"
                        style={{
                            display: 'block',
                            padding: '10px 14px',
                            textAlign: 'center',
                            fontSize: 12,
                            fontWeight: 600,
                            color: '#fff',
                            textDecoration: 'none',
                            borderTop: '1px solid var(--line-soft, rgba(255,255,255,0.08))',
                        }}
                    >
                        View all
                    </a>
                </div>
            )}
        </div>
    );
}
