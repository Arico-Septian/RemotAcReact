import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { NotificationListItem } from '@/types';
import '../../css/notifications.css';

interface Pagination {
    current_page: number;
    last_page: number;
    prev_url: string | null;
    next_url: string | null;
}

interface NotificationsProps {
    notifications: NotificationListItem[];
    unreadCount: number;
    total: number;
    pagination: Pagination;
}

export default function Notifications({ notifications: initial, unreadCount: initialUnread, total, pagination }: NotificationsProps) {
    const [items, setItems] = useState<NotificationListItem[]>(initial);
    const [unread, setUnread] = useState(initialUnread);
    const [markingAll, setMarkingAll] = useState(false);

    const markRead = async (id: number) => {
        try {
            await window.axios.post(`/notifications/${id}/read`);
            setItems((prev) => prev.map((n) => (n.id === id ? { ...n, is_unread: false } : n)));
            setUnread((u) => Math.max(0, u - 1));
        } catch {
            /* ignore */
        }
    };

    const markReadAndGo = async (e: React.MouseEvent, id: number, link: string) => {
        e.preventDefault();
        await markRead(id);
        router.visit(link);
    };

    const markAllRead = async () => {
        if (unread <= 0 || markingAll) return;

        setMarkingAll(true);
        try {
            await window.axios.post('/notifications/read-all');
            setUnread(0);
            setItems((prev) => prev.map((n) => ({ ...n, is_unread: false })));
            window.smToast?.('Semua notifikasi ditandai dibaca', 'success');
        } catch {
            window.smToast?.('Gagal menandai semua notifikasi', 'error');
        } finally {
            setMarkingAll(false);
        }
    };

    const remove = async (id: number) => {
        if (!window.confirm('Delete this notification?')) return;
        try {
            await window.axios.delete(`/notifications/${id}`);
            const wasUnread = items.find((n) => n.id === id)?.is_unread;
            setItems((prev) => prev.filter((n) => n.id !== id));
            if (wasUnread) setUnread((u) => Math.max(0, u - 1));
        } catch {
            /* ignore */
        }
    };

    return (
        <AppLayout
            title="Notifications"
            subtitle={`${unread} unread of ${total} total`}
            headerActions={
                unread > 0 ? (
                    <button type="button" className="btn btn-sm notification-mark-all" onClick={markAllRead} disabled={markingAll}>
                        <i className="fa-solid fa-check-double"></i>
                        <span>{markingAll ? 'Marking...' : 'Mark all read'}</span>
                    </button>
                ) : null
            }
        >
            <Head title="Notifications" />

            <div className="tbl-wrap">
                {items.length === 0 ? (
                    <div className="empty-state">
                        <div className="empty-icon"><i className="fa-regular fa-bell-slash"></i></div>
                        <p className="empty-title">No notifications</p>
                        <p className="empty-sub">System notifications & alerts will appear here</p>
                    </div>
                ) : (
                    items.map((n, idx) => (
                        <div
                            key={n.id}
                            className={`nlist-item ${n.is_unread ? 'unread' : ''}`}
                            style={{ margin: 0, border: 0, borderRadius: 0, borderBottom: idx === items.length - 1 ? undefined : '1px solid var(--line-soft)' }}
                        >
                            <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-2">
                                    <p className="text-sm font-semibold" style={{ color: 'var(--ink-0)', margin: 0 }}>
                                        {n.title}
                                    </p>
                                </div>
                                {n.message && (
                                    <p className="text-xs mt-1" style={{ color: 'var(--ink-2)', lineHeight: 1.5 }}>
                                        {n.message}
                                    </p>
                                )}
                                <div className="flex items-center gap-3 mt-2 text-mono" style={{ fontSize: 11, color: 'var(--ink-4)' }}>
                                    <span><i className="fa-regular fa-clock text-[9px]"></i> {n.time_ago}</span>
                                    <span>·</span>
                                    <span>{n.time_full}</span>
                                    {n.link && (
                                        <>
                                            <span>·</span>
                                            <a href={n.link} onClick={(e) => markReadAndGo(e, n.id, n.link!)} style={{ color: '#5a93ec' }}>
                                                View details →
                                            </a>
                                        </>
                                    )}
                                </div>
                            </div>
                            <div className="nlist-actions">
                                {n.is_unread && (
                                    <button onClick={() => markRead(n.id)} className="btn-icon" title="Tandai dibaca">
                                        <i className="fa-solid fa-check text-[11px]"></i>
                                    </button>
                                )}
                                {n.is_deletable && (
                                    <button onClick={() => remove(n.id)} className="btn-icon danger" title="Delete">
                                        <i className="fa-solid fa-trash text-[11px]"></i>
                                    </button>
                                )}
                            </div>
                        </div>
                    ))
                )}

                {pagination.last_page > 1 && (
                    <div className="tbl-footer">
                        <p>Page {pagination.current_page} of {pagination.last_page}</p>
                        <div className="pager">
                            {pagination.prev_url ? (
                                <Link href={pagination.prev_url} preserveScroll>
                                    <i className="fa-solid fa-chevron-left text-[9px]"></i>
                                </Link>
                            ) : (
                                <span className="disabled"><i className="fa-solid fa-chevron-left text-[9px]"></i></span>
                            )}
                            <span className="active text-mono">{pagination.current_page}</span>
                            {pagination.next_url ? (
                                <Link href={pagination.next_url} preserveScroll>
                                    <i className="fa-solid fa-chevron-right text-[9px]"></i>
                                </Link>
                            ) : (
                                <span className="disabled"><i className="fa-solid fa-chevron-right text-[9px]"></i></span>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
