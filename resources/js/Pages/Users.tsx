import { FormEvent, useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import DeleteConfirmModal from '@/Components/DeleteConfirmModal';
import Pagination from '@/Components/Pagination';
import type { ManagedUser } from '@/types';
import '../../css/users.css';

interface Stats {
    total: number;
    online: number;
    offline: number;
    admins: number;
    online_pct: number;
    offline_pct: number;
    new_this_week: number;
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

interface UsersProps {
    users: ManagedUser[];
    stats: Stats;
    filters: { search: string; role: string };
    pagination: Pagination;
}

type RoleFilter = '' | 'admin' | 'operator' | 'user';

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

export default function Users({ users, stats: initialStats, filters, pagination }: UsersProps) {
    const [search, setSearch] = useState(filters.search);
    const [stats, setStats] = useState(initialStats);
    const [modalOpen, setModalOpen] = useState(false);
    const [showPw, setShowPw] = useState(false);
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [confirmUser, setConfirmUser] = useState<ManagedUser | null>(null);
    const searchTimer = useRef<number | null>(null);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        name: '',
        password: '',
        role: 'user' as RoleFilter | 'user',
    });

    const applyFilters = (next: { search?: string; role?: string }) => {
        router.get(
            '/users',
            {
                search: next.search ?? search,
                role: next.role ?? filters.role,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const onSearchChange = (value: string) => {
        setSearch(value);
        if (searchTimer.current) window.clearTimeout(searchTimer.current);
        searchTimer.current = window.setTimeout(() => applyFilters({ search: value }), 350);
    };

    // Live online/offline counts
    useEffect(() => {
        const refresh = async () => {
            try {
                const { data: d } = await window.axios.get('/users-online');
                setStats((s) => ({ ...s, online: d.online, offline: d.offline, online_pct: d.percentage, offline_pct: d.offlinePercentage, total: d.total }));
            } catch {
                /* ignore */
            }
        };
        const id = window.setInterval(refresh, 30000);
        return () => window.clearInterval(id);
    }, []);

    // Sinkronkan stats dari props saat Inertia memuat ulang (real-time user add/delete/update).
    useEffect(() => {
        setStats(initialStats);
    }, [initialStats]);

    // Real-time sync: user ditambah/diubah/dihapus user lain -> reload daftar user.
    useEffect(() => {
        const echo = window.Echo;
        if (!echo) return;
        echo.channel('users').listen('.UsersChanged', () => router.reload({ only: ['users', 'stats', 'pagination'] }));
        return () => {
            try {
                echo.leaveChannel('users');
            } catch {
                /* noop */
            }
        };
    }, []);

    const closeModal = () => {
        setModalOpen(false);
        setShowPw(false);
        reset();
        clearErrors();
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/users', {
            onSuccess: () => {
                closeModal();
                window.smToast?.('User berhasil ditambahkan', 'success');
            },
            preserveScroll: true,
        });
    };

    const deleteUser = (u: ManagedUser) => {
        if (deletingId) return;
        setConfirmUser(u);
    };

    const confirmDeleteUser = async () => {
        if (deletingId || !confirmUser) return;
        setDeletingId(confirmUser.id);
        try {
            await window.axios.delete(`/users/${confirmUser.id}`);
            setConfirmUser(null);
            router.reload({ only: ['users', 'stats', 'pagination'] });
            window.smToast?.('User berhasil dihapus', 'success');
        } catch (err: any) {
            const msg = err?.response?.data?.error ?? 'Failed to delete user';
            if (window.smToast) window.smToast(msg, 'error');
            else window.alert(msg);
        } finally {
            setDeletingId(null);
        }
    };

    const roleTabs: { value: RoleFilter; label: string }[] = [
        { value: '', label: 'All' },
        { value: 'admin', label: 'Admin' },
        { value: 'operator', label: 'Operator' },
        { value: 'user', label: 'User' },
    ];

    return (
        <AppLayout title="User Management" subtitle="Manage system users & roles">
            <Head title="User Management" />

            {/* Stats */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                <StatCard accent="cyan" label="Total Users" value={stats.total} sub={`+${stats.new_this_week} this week`} icon="fa-users" />
                <StatCard accent="mint" label="Online Now" value={stats.online} sub={`${stats.online_pct}% currently active`} icon="fa-user-check" />
                <StatCard accent="coral" label="Offline" value={stats.offline} sub={`${stats.offline_pct}% inactive`} icon="fa-user-slash" />
                <StatCard accent="lavender" label="Administrators" value={stats.admins} sub="System privileges" icon="fa-shield-halved" />
            </div>

            {/* Table card */}
            <div className="tbl-wrap">
                <div className="tbl-toolbar">
                    <label className="search-input" style={{ flex: 1, maxWidth: 'none' }}>
                        <i className="fa-solid fa-magnifying-glass"></i>
                        <input value={search} onChange={(e) => onSearchChange(e.target.value)} placeholder="Search by username…" autoComplete="off" />
                    </label>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexShrink: 0 }}>
                        <div className="segmented">
                            {roleTabs.map((t) => (
                                <button key={t.value} type="button" className={`seg ${filters.role === t.value ? 'active' : ''}`} onClick={() => applyFilters({ role: t.value })}>
                                    {t.label}
                                </button>
                            ))}
                        </div>
                        <button onClick={() => setModalOpen(true)} type="button" className="btn btn-primary btn-sm btn-add">
                            <i className="fa-solid fa-user-plus text-[10px]"></i> Add User
                        </button>
                    </div>
                </div>

                {/* Desktop table */}
                <table className="user-table">
                    <thead>
                        <tr>
                            <th style={{ width: '30%' }}>USER</th>
                            <th style={{ width: '20%' }}>ROLE</th>
                            <th style={{ width: '20%' }}>STATUS</th>
                            <th style={{ width: '30%', textAlign: 'right', paddingRight: 24 }}>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.length === 0 ? (
                            <tr>
                                <td colSpan={4}>
                                    <div className="empty-state">
                                        <div className="empty-icon"><i className="fa-solid fa-users"></i></div>
                                        <p className="empty-title">No users found</p>
                                        <p className="empty-sub">Try adjusting the filter</p>
                                    </div>
                                </td>
                            </tr>
                        ) : (
                            users.map((u) => (
                                <tr key={u.id}>
                                    <td>
                                        <div className="user-cell">
                                            {u.avatar_url ? (
                                                <img src={u.avatar_url} alt={u.name} className="user-avatar-sm" style={{ objectFit: 'cover' }} />
                                            ) : (
                                                <div className="user-avatar-sm" style={{ background: 'linear-gradient(135deg,#5a93ec,#335fc2)', color: '#fff' }}>
                                                    {u.name.charAt(0).toUpperCase()}
                                                </div>
                                            )}
                                            <div className="user-info">
                                                <p className="user-name">{u.name}</p>
                                                {u.email && <p className="user-email">{u.email}</p>}
                                            </div>
                                        </div>
                                    </td>
                                    <td><span className={`badge-role ${u.role}`}>{u.role.toUpperCase()}</span></td>
                                    <td><div className="status-cell">{u.is_online ? 'Online' : 'Offline'}</div></td>
                                    <td>
                                        <div className="actions-cell">
                                            {!u.is_self && (
                                                <button onClick={() => deleteUser(u)} type="button" disabled={deletingId === u.id} className="btn-icon danger" title="Delete user">
                                                    <i className="fa-solid fa-trash text-[10px]"></i>
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>

                {/* Mobile cards */}
                <div className="user-cards">
                    {users.map((u) => (
                        <div className="user-card" key={u.id}>
                            <div className="flex items-center gap-3">
                                {u.avatar_url ? (
                                    <img src={u.avatar_url} alt={u.name} style={{ width: 34, height: 34, borderRadius: 9, objectFit: 'cover', flexShrink: 0 }} />
                                ) : (
                                    <div style={{ width: 34, height: 34, borderRadius: 9, background: 'linear-gradient(135deg,#5a93ec,#335fc2)', color: '#fff', fontSize: 14, fontWeight: 700, display: 'inline-flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                                        {u.name.charAt(0).toUpperCase()}
                                    </div>
                                )}
                                <div className="flex-1 min-w-0">
                                    <span className="user-card-name-text">{u.name}</span>
                                </div>
                                <div className="flex items-center gap-2" style={{ flexShrink: 0 }}>
                                    <span className={`badge-role ${u.role}`} style={{ minWidth: 62 }}>{u.role.toUpperCase()}</span>
                                    <span style={{ minWidth: 48, textAlign: 'center', display: 'inline-block', fontSize: 12, fontWeight: 600, lineHeight: 1, whiteSpace: 'nowrap', color: '#fff' }}>
                                        {u.is_online ? 'Online' : 'Offline'}
                                    </span>
                                    <div className="user-card-actions">
                                        {!u.is_self && (
                                            <button onClick={() => deleteUser(u)} type="button" disabled={deletingId === u.id} className="btn-icon danger" title="Delete user">
                                                <i className="fa-solid fa-trash text-[10px]"></i>
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                <Pagination pagination={pagination} label="user" />
            </div>

            {/* Add User modal */}
            {modalOpen && createPortal(
                <div className="modal-backdrop is-open" onClick={(e) => e.target === e.currentTarget && closeModal()}>
                    <div className="modal">
                        <div className="modal-header">
                            <div>
                                <p className="eyebrow"><i className="fa-solid fa-plus"></i> New</p>
                                <h2>Add User</h2>
                                <p className="sub">Create a new account with the appropriate access role</p>
                            </div>
                        </div>
                        <form id="addUserForm" onSubmit={submit}>
                            <div className="modal-body space-y-3">
                                <div className="field">
                                    <label className="field-label">Username</label>
                                    <input className="input" type="text" placeholder="e.g. john_doe" autoComplete="off" minLength={3} maxLength={20} pattern="[A-Za-z][A-Za-z0-9_]{2,19}" title="3-20 characters, start with a letter, use letters, numbers, or underscore." value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                                    <p className="field-hint" style={errors.name ? { fontSize: 11, color: 'var(--coral)', marginTop: 4 } : { fontSize: 11, color: '#94a3b8', marginTop: 4 }}>
                                        {errors.name ?? '3-20 karakter, diawali huruf, tanpa spasi.'}
                                    </p>
                                </div>
                                <div className="field">
                                    <label className="field-label">Password</label>
                                    <div className="pwd-field">
                                        <input className="input" type={showPw ? 'text' : 'password'} placeholder="min. 8 characters" minLength={8} value={data.password} onChange={(e) => setData('password', e.target.value)} required />
                                        <button type="button" className="pwd-eye" onClick={() => setShowPw((v) => !v)} aria-label="Toggle password">
                                            <i className={`fa-solid ${showPw ? 'fa-eye-slash' : 'fa-eye'}`}></i>
                                        </button>
                                    </div>
                                    <p className="field-hint" style={errors.password ? { fontSize: 11, color: 'var(--coral)', marginTop: 4 } : { fontSize: 11, color: '#94a3b8', marginTop: 4 }}>
                                        {errors.password ?? 'Minimal 8 karakter, gunakan huruf besar, angka, dan simbol.'}
                                    </p>
                                </div>
                                <div className="field">
                                    <label className="field-label">Role</label>
                                    <select className="input select" aria-label="User role" title="User role" value={data.role} onChange={(e) => setData('role', e.target.value as RoleFilter)}>
                                        <option value="admin">Admin</option>
                                        <option value="operator">Operator</option>
                                        <option value="user">User</option>
                                    </select>
                                </div>
                            </div>
                            <div className="modal-footer">
                                <button type="button" className="btn btn-ghost" onClick={closeModal}>Cancel</button>
                                <button type="submit" className="btn btn-primary" disabled={processing}>{processing ? 'Creating…' : 'Create User'}</button>
                            </div>
                        </form>
                    </div>
                </div>,
                document.body,
            )}
            <DeleteConfirmModal
                open={confirmUser !== null}
                busy={deletingId !== null}
                onCancel={() => !deletingId && setConfirmUser(null)}
                onConfirm={confirmDeleteUser}
            />
        </AppLayout>
    );
}
