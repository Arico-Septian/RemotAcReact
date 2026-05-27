{{-- Shared Sidebar Component --}}
@php
    $role = Auth::user()->role;
    $isAdminOp = in_array($role, ['admin', 'operator']);
@endphp

<aside id="sidebar" class="app-sidebar">
    {{-- BRAND --}}
    <div class="brand">
        <div class="brand-mark">
            <div class="brand-logo">
                <i class="fa-solid fa-snowflake"></i>
            </div>
            <div class="brand-text menu-text">
                <span class="name">SmartAC</span>
                <span class="sub">Control System</span>
            </div>
        </div>
    </div>

    {{-- NAV --}}
    <nav class="nav-scroll">
        <p class="nav-section-label">OVERVIEW</p>
        <div class="nav-list">
            <a href="{{ route('dashboard') }}"
               class="nav-link menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-gauge-high"></i>
                <span class="menu-text">Dashboard</span>
            </a>
            <a href="{{ route('rooms.overview') }}"
               class="nav-link menu-link {{ request()->routeIs('rooms.overview') || request()->is('rooms/*/status') ? 'active' : '' }}">
                <i class="fa-solid fa-grip"></i>
                <span class="menu-text">Server Rooms</span>
            </a>
            <a href="{{ route('monitoring') }}"
               class="nav-link menu-link {{ request()->routeIs('monitoring') ? 'active' : '' }}">
                <i class="fa-solid fa-temperature-half"></i>
                <span class="menu-text">Monitoring Suhu Server</span>
            </a>
        </div>

        @if ($isAdminOp)
            <p class="nav-section-label">MANAGEMENT</p>
            <div class="nav-list">
                <a href="/rooms"
                   class="nav-link menu-link {{ request()->is('rooms*') && !request()->routeIs('rooms.overview') && !request()->is('rooms/*/status') ? 'active' : '' }}">
                    <i class="fa-solid fa-server"></i>
                    <span class="menu-text">Rooms &amp; AC</span>
                </a>
            </div>
        @endif

        @if ($role === 'admin')
            <p class="nav-section-label">ADMINISTRATION</p>
            <div class="nav-list">
                <a href="/users"
                   class="nav-link menu-link {{ request()->is('users*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users-gear"></i>
                    <span class="menu-text">Users</span>
                </a>
                <a href="/logs"
                   class="nav-link menu-link {{ request()->is('logs*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span class="menu-text">Activity Log</span>
                </a>
            </div>
        @endif

        @if ($role === 'admin')
            <p class="nav-section-label">SETTING</p>
            <div class="nav-list">
                <a href="/settings"
                   class="nav-link menu-link {{ request()->is('settings*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear"></i>
                    <span class="menu-text">Pengaturan</span>
                </a>
            </div>
        @endif

    </nav>

    {{-- FOOTER --}}
    <div class="sidebar-footer">
        <div class="profile-full">
            <a href="/profile" class="avatar" title="View profile" style="padding:0;overflow:hidden;">
                @if (Auth::user()->avatar_url)
                    <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}"
                         style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
                @else
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                @endif
            </a>
            <a href="/profile" class="profile-info menu-text" style="text-decoration:none;">
                <p class="name">{{ Auth::user()->name }}</p>
                <p class="role">{{ ucfirst(Auth::user()->role) }}</p>
            </a>
            <form action="/logout" method="POST" class="menu-text" style="margin:0;">
                @csrf
                <button type="submit" class="icon-btn danger" title="Logout">
                    <i class="fa-solid fa-right-from-bracket text-[11px]"></i>
                </button>
            </form>
        </div>
        <div class="profile-mini">
            <form action="/logout" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="icon-btn danger" title="Logout">
                    <i class="fa-solid fa-right-from-bracket text-[11px]"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
