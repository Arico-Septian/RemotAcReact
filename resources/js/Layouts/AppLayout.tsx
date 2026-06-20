import { PropsWithChildren, ReactNode, useEffect, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import type { PageProps } from '@/types';
import NotificationBell from '@/Components/NotificationBell';
import ToastContainer from '@/Components/Toast';
import '../../css/sidebar.css';

interface AppLayoutProps {
    title: string;
    subtitle?: string;
    headerActions?: ReactNode;
    hideHeaderUser?: boolean;
}

interface NavItem {
    href: string;
    icon: string;
    label: string;
    match: (path: string) => boolean;
}

export default function AppLayout({
    title,
    subtitle,
    headerActions,
    hideHeaderUser,
    children,
}: PropsWithChildren<AppLayoutProps>) {
    const { auth, url } = usePage<PageProps>().props as PageProps & { url?: string };
    const user = auth.user;
    const path = typeof window !== 'undefined' ? window.location.pathname : (url ?? '');
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const role = user?.role ?? 'user';
    const isAdminOp = role === 'admin' || role === 'operator';

    const overview: NavItem[] = [
        { href: '/dashboard', icon: 'fa-gauge-high', label: 'Dashboard', match: (p) => p.startsWith('/dashboard') },
        { href: '/rooms/overview', icon: 'fa-building', label: 'Room Status', match: (p) => p === '/rooms/overview' || /^\/rooms\/\d+\/status/.test(p) },
        { href: '/raspi-monitor', icon: 'fa-temperature-half', label: 'Server Temperature', match: (p) => p.startsWith('/raspi-monitor') },
    ];

    const logout = () => router.post('/logout');

    const NavLink = ({ item }: { item: NavItem }) => (
        <a href={item.href} className={`nav-link menu-link ${item.match(path) ? 'active' : ''}`}>
            <i className={`fa-solid ${item.icon}`}></i>
            <span className="menu-text">{item.label}</span>
        </a>
    );

    return (
        <div className="app-layout">
            <ToastContainer />
            <div className="app-bg"></div>
            <div id="overlay" className={sidebarOpen ? 'active' : ''} onClick={() => setSidebarOpen(false)}></div>

            <div className="layout">
                <aside id="sidebar" className={`app-sidebar ${sidebarOpen ? 'open' : ''}`}>
                    <div className="brand">
                        <div className="brand-mark">
                            <div className="brand-logo">
                                <i className="fa-solid fa-snowflake"></i>
                            </div>
                            <div className="brand-text menu-text">
                                <span className="name">Control SmartAC</span>
                                <span className="sub">System</span>
                            </div>
                        </div>
                    </div>

                    <nav className="nav-scroll">
                        <p className="nav-section-label">OVERVIEW</p>
                        <div className="nav-list">
                            {overview.map((item) => (
                                <NavLink key={item.href} item={item} />
                            ))}
                        </div>

                        {isAdminOp && (
                            <>
                                <p className="nav-section-label">MANAGEMENT</p>
                                <div className="nav-list">
                                    <a
                                        href="/rooms"
                                        className={`nav-link menu-link ${path.startsWith('/rooms') && path !== '/rooms/overview' && !/^\/rooms\/\d+\/status/.test(path) ? 'active' : ''}`}
                                    >
                                        <i className="fa-solid fa-screwdriver-wrench"></i>
                                        <span className="menu-text">Manage Rooms &amp; AC</span>
                                    </a>
                                </div>
                            </>
                        )}

                        {role === 'admin' && (
                            <>
                                <p className="nav-section-label">ADMINISTRATION</p>
                                <div className="nav-list">
                                    <a href="/users" className={`nav-link menu-link ${path.startsWith('/users') ? 'active' : ''}`}>
                                        <i className="fa-solid fa-users-gear"></i>
                                        <span className="menu-text">Users</span>
                                    </a>
                                    <a href="/logs" className={`nav-link menu-link ${path.startsWith('/logs') ? 'active' : ''}`}>
                                        <i className="fa-solid fa-clock-rotate-left"></i>
                                        <span className="menu-text">Activity Log</span>
                                    </a>
                                </div>
                            </>
                        )}
                    </nav>

                    <div className="sidebar-footer">
                        <div className="profile-full">
                            <button type="button" onClick={logout} className="logout-btn menu-text" title="Logout">
                                <i className="fa-solid fa-right-from-bracket"></i>
                                <span>Logout</span>
                            </button>
                        </div>
                        <div className="profile-mini">
                            <button type="button" onClick={logout} className="icon-btn danger" title="Logout">
                                <i className="fa-solid fa-right-from-bracket text-[11px]"></i>
                            </button>
                        </div>
                    </div>
                </aside>

                <div className="app-main">
                    <header className="main-header">
                        <div className="flex items-center gap-3">
                            <button
                                type="button"
                                onClick={() => setSidebarOpen((v) => !v)}
                                className="lg:hidden btn-icon sidebar-toggle"
                                title="Menu"
                            >
                                <i className="fa-solid fa-bars-staggered"></i>
                            </button>
                            <div className="app-header-title">
                                <h1>{title}</h1>
                                {subtitle && <p>{subtitle}</p>}
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            {headerActions}
                            {!hideHeaderUser && <NotificationBell />}
                            {!hideHeaderUser && user && (
                                <a
                                    href="/profile"
                                    title="View profile"
                                    style={{ display: 'inline-flex', alignItems: 'center', gap: 9, textDecoration: 'none', padding: '4px 8px', borderRadius: 12 }}
                                >
                                    <span className="header-user-text" style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-start', lineHeight: 1.2, minWidth: 0 }}>
                                        <span style={{ fontSize: 13, fontWeight: 700, color: 'var(--ink-0)', whiteSpace: 'nowrap' }}>{user.name}</span>
                                        <span style={{ fontSize: 11, color: 'var(--ink-3)', textTransform: 'capitalize' }}>{role}</span>
                                    </span>
                                    <span
                                        style={{
                                            width: 36,
                                            height: 36,
                                            borderRadius: '50%',
                                            overflow: 'hidden',
                                            flexShrink: 0,
                                            display: 'inline-flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            background: 'linear-gradient(135deg,#5a93ec,#335fc2)',
                                            color: '#fff',
                                            fontWeight: 700,
                                            fontSize: 14,
                                        }}
                                    >
                                        {user.avatar_url ? (
                                            <img src={user.avatar_url} alt={user.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                                        ) : (
                                            (user.name ?? '?').charAt(0).toUpperCase()
                                        )}
                                    </span>
                                </a>
                            )}
                        </div>
                    </header>

                    <div className="page-body">
                        <div className="app-content">
                            <div className="app-content-inner space-y-4">{children}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
