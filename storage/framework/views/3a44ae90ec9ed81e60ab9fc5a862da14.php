
<?php
    $role = Auth::user()->role;
    $isAdminOp = in_array($role, ['admin', 'operator']);
?>

<aside id="sidebar" class="app-sidebar">
    
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

    
    <nav class="nav-scroll">
        <p class="nav-section-label">OVERVIEW</p>
        <div class="nav-list">
            <a href="<?php echo e(route('dashboard')); ?>"
               class="nav-link menu-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                <i class="fa-solid fa-gauge-high"></i>
                <span class="menu-text">Dashboard</span>
            </a>
            <a href="<?php echo e(route('rooms.overview')); ?>"
               class="nav-link menu-link <?php echo e(request()->routeIs('rooms.overview') || request()->is('rooms/*/status') ? 'active' : ''); ?>">
                <i class="fa-solid fa-grip"></i>
                <span class="menu-text">Server Rooms</span>
            </a>
            <a href="<?php echo e(route('monitoring')); ?>"
               class="nav-link menu-link <?php echo e(request()->routeIs('monitoring') ? 'active' : ''); ?>">
                <i class="fa-solid fa-temperature-half"></i>
                <span class="menu-text">Server Temperature</span>
            </a>
        </div>

        <?php if($isAdminOp): ?>
            <p class="nav-section-label">MANAGEMENT</p>
            <div class="nav-list">
                <a href="/rooms"
                   class="nav-link menu-link <?php echo e(request()->is('rooms*') && !request()->routeIs('rooms.overview') && !request()->is('rooms/*/status') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-server"></i>
                    <span class="menu-text">Rooms &amp; AC</span>
                </a>
            </div>
        <?php endif; ?>

        <?php if($role === 'admin'): ?>
            <p class="nav-section-label">ADMINISTRATION</p>
            <div class="nav-list">
                <a href="/users"
                   class="nav-link menu-link <?php echo e(request()->is('users*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-users-gear"></i>
                    <span class="menu-text">Users</span>
                </a>
                <a href="/logs"
                   class="nav-link menu-link <?php echo e(request()->is('logs*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span class="menu-text">Activity Log</span>
                </a>
            </div>
        <?php endif; ?>


    </nav>

    
    <div class="sidebar-footer">
        <div class="profile-full">
            <a href="/profile" class="avatar" title="View profile" style="padding:0;overflow:hidden;">
                <?php if(Auth::user()->avatar_url): ?>
                    <img src="<?php echo e(Auth::user()->avatar_url); ?>" alt="<?php echo e(Auth::user()->name); ?>"
                         style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
                <?php else: ?>
                    <?php echo e(strtoupper(substr(Auth::user()->name, 0, 1))); ?>

                <?php endif; ?>
            </a>
            <a href="/profile" class="profile-info menu-text" style="text-decoration:none;">
                <p class="name"><?php echo e(Auth::user()->name); ?></p>
                <p class="role"><?php echo e(ucfirst(Auth::user()->role)); ?></p>
            </a>
            <form action="/logout" method="POST" class="menu-text" style="margin:0;">
                <?php echo csrf_field(); ?>
                <button type="submit" class="icon-btn danger" title="Logout">
                    <i class="fa-solid fa-right-from-bracket text-[11px]"></i>
                </button>
            </form>
        </div>
        <div class="profile-mini">
            <form action="/logout" method="POST" style="margin:0;">
                <?php echo csrf_field(); ?>
                <button type="submit" class="icon-btn danger" title="Logout">
                    <i class="fa-solid fa-right-from-bracket text-[11px]"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
<?php /**PATH C:\laragon\www\tugasakhirremotac\resources\views/components/sidebar.blade.php ENDPATH**/ ?>