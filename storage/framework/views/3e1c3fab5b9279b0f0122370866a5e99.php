
<?php $role = Auth::user()->role; ?>
<nav class="bottom-nav" style="display: none !important;">
    <a href="<?php echo e(route('dashboard')); ?>"
       class="bnav-item <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
        <i class="fa-solid fa-gauge-high"></i>
        <span>Dashboard</span>
    </a>

    <?php if($role === 'user'): ?>
        <a href="<?php echo e(route('rooms.overview')); ?>"
           class="bnav-item <?php echo e(request()->routeIs('rooms.overview') || request()->is('rooms/*/status') ? 'active' : ''); ?>">
            <i class="fa-solid fa-grip"></i>
            <span>Rooms</span>
        </a>
    <?php endif; ?>

    <?php if(in_array($role, ['admin','operator'])): ?>
        <a href="/rooms"
           class="bnav-item <?php echo e(request()->is('rooms*') && !request()->routeIs('rooms.overview') ? 'active' : ''); ?>">
            <i class="fa-solid fa-server"></i>
            <span>Rooms</span>
        </a>
    <?php endif; ?>

    <?php if($role === 'admin'): ?>
        <a href="/logs"
           class="bnav-item <?php echo e(request()->is('logs*') ? 'active' : ''); ?>">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Activity</span>
        </a>
    <?php endif; ?>

    <a href="<?php echo e(route('monitoring')); ?>"
       class="bnav-item <?php echo e(request()->routeIs('monitoring') ? 'active' : ''); ?>">
        <i class="fa-solid fa-temperature-half"></i>
        <span>Server Temp</span>
    </a>

    <a href="/profile"
       class="bnav-item <?php echo e(request()->is('profile*') ? 'active' : ''); ?>">
        <i class="fa-regular fa-circle-user"></i>
        <span>Profile</span>
    </a>
</nav>
<?php /**PATH C:\laragon\www\tugasakhirremotac\resources\views/components/bottom-nav.blade.php ENDPATH**/ ?>