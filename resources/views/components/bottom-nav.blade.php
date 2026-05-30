{{-- Mobile Bottom Navigation - DISABLED, using sidebar toggle instead --}}
@php $role = Auth::user()->role; @endphp
<nav class="bottom-nav" style="display: none !important;">
    <a href="{{ route('dashboard') }}"
       class="bnav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <i class="fa-solid fa-gauge-high"></i>
        <span>Dashboard</span>
    </a>

    @if ($role === 'user')
        <a href="{{ route('rooms.overview') }}"
           class="bnav-item {{ request()->routeIs('rooms.overview') || request()->is('rooms/*/status') ? 'active' : '' }}">
            <i class="fa-solid fa-grip"></i>
            <span>Rooms</span>
        </a>
    @endif

    @if (in_array($role, ['admin','operator']))
        <a href="/rooms"
           class="bnav-item {{ request()->is('rooms*') && !request()->routeIs('rooms.overview') ? 'active' : '' }}">
            <i class="fa-solid fa-server"></i>
            <span>Rooms</span>
        </a>
    @endif

    @if ($role === 'admin')
        <a href="/logs"
           class="bnav-item {{ request()->is('logs*') ? 'active' : '' }}">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Activity</span>
        </a>
    @endif

    <a href="{{ route('monitoring') }}"
       class="bnav-item {{ request()->routeIs('monitoring') ? 'active' : '' }}">
        <i class="fa-solid fa-temperature-half"></i>
        <span>Server Temp</span>
    </a>

    <a href="/profile"
       class="bnav-item {{ request()->is('profile*') ? 'active' : '' }}">
        <i class="fa-regular fa-circle-user"></i>
        <span>Profile</span>
    </a>
</nav>
