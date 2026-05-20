<?php if(auth()->guard()->check()): ?>

<div id="idleWarnModal" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(7,16,31,0.72);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:16px;">
    <div style="max-width:380px;width:100%;background:var(--panel-1);border:1px solid var(--line);border-radius:18px;padding:22px 22px 18px;box-shadow:0 20px 60px -20px rgba(0,0,0,0.6);">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
            <span style="width:36px;height:36px;border-radius:10px;background:rgba(251,191,36,0.14);border:1px solid rgba(251,191,36,0.34);display:inline-flex;align-items:center;justify-content:center;color:#fbbf24;">
                <i class="fa-solid fa-clock"></i>
            </span>
            <div>
                <h3 style="margin:0;font-size:15px;font-weight:700;color:var(--ink-0);">Sesi akan berakhir</h3>
            </div>
        </div>
        <p style="margin:0 0 14px;font-size:13px;line-height:1.5;color:var(--ink-2);">
            Anda tidak aktif untuk beberapa waktu. Sesi akan otomatis berakhir dalam
            <strong id="idleCountdown" style="color:var(--coral);font-family:'JetBrains Mono',monospace;">60</strong> detik.
        </p>
        <div style="display:flex;gap:8px;">
            <button type="button" id="idleStayBtn" class="btn btn-primary" style="flex:1;">Tetap login</button>
            <button type="button" id="idleLogoutBtn" class="btn btn-ghost" style="flex:1;">Logout sekarang</button>
        </div>
    </div>
</div>

<script>
/* ===== Idle auto-logout (role-based) ===== */
(function () {
    const ROLE = <?php echo json_encode(auth()->user()->role ?? 'user', 15, 512) ?>;
    const IDLE_MINUTES = { admin: 15, operator: 30, user: 60 };
    const WARN_SECONDS = 60;
    const IDLE_MS = (IDLE_MINUTES[ROLE] ?? 60) * 60 * 1000;
    const PING_THROTTLE_MS = 60 * 1000;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const modal = document.getElementById('idleWarnModal');
    const countdownEl = document.getElementById('idleCountdown');
    const stayBtn = document.getElementById('idleStayBtn');
    const logoutBtn = document.getElementById('idleLogoutBtn');

    let lastActivity = Date.now();
    let lastPing = 0;
    let warnTimer = null;
    let logoutTimer = null;
    let countdownTimer = null;
    let warning = false;

    function showModal() {
        if (!modal) return;
        modal.style.display = 'flex';
        warning = true;
        let remaining = WARN_SECONDS;
        countdownEl.textContent = remaining;
        countdownTimer = setInterval(() => {
            remaining--;
            countdownEl.textContent = remaining;
            if (remaining <= 0) clearInterval(countdownTimer);
        }, 1000);
        logoutTimer = setTimeout(doLogout, WARN_SECONDS * 1000);
    }

    function hideModal() {
        if (!modal) return;
        modal.style.display = 'none';
        warning = false;
        clearTimeout(logoutTimer);
        clearInterval(countdownTimer);
    }

    function doLogout() {
        hideModal();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/logout';
        const t = document.createElement('input');
        t.type = 'hidden'; t.name = '_token'; t.value = csrf;
        form.appendChild(t);
        document.body.appendChild(form);
        form.submit();
    }

    function pingServer() {
        const now = Date.now();
        if (now - lastPing < PING_THROTTLE_MS) return;
        lastPing = now;
        fetch('/session/ping', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).catch(() => {});
    }

    function resetIdle() {
        lastActivity = Date.now();
        if (warning) {
            hideModal();
            pingServer();
        }
        clearTimeout(warnTimer);
        warnTimer = setTimeout(showModal, Math.max(IDLE_MS - WARN_SECONDS * 1000, 1000));
    }

    function onUserInput() {
        const now = Date.now();
        if (now - lastActivity < 2000) return;
        resetIdle();
        pingServer();
    }

    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(evt => {
        window.addEventListener(evt, onUserInput, { passive: true });
    });

    stayBtn?.addEventListener('click', () => {
        resetIdle();
        pingServer();
    });
    logoutBtn?.addEventListener('click', doLogout);

    resetIdle();
})();
</script>
<?php endif; ?>

<script>
/* ===== SIDEBAR TOGGLE ===== */
window.toggleSidebar = function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    if (!sidebar) {
        console.warn('[Sidebar] #sidebar element not found');
        return;
    }

    if (window.innerWidth <= 1024) {
        const isOpen = sidebar.classList.toggle('open');
        overlay?.classList.toggle('active', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    } else {
        // collapse on desktop
        sidebar.classList.toggle('collapsed');
        try {
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
        } catch (e) {}
    }
};


document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && window.innerWidth > 1024) {
        try {
            if (localStorage.getItem('sidebarCollapsed') === '1') sidebar.classList.add('collapsed');
        } catch (e) {}
    }
});

/* ===== SIDEBAR NOTIF BADGE (synced with bell poll) ===== */
function syncSidebarNotifBadge(count) {
    const b = document.getElementById('sidebarNotifBadge');
    if (!b) return;
    if (count > 0) {
        b.textContent = count > 99 ? '99+' : String(count);
        b.style.display = 'inline-flex';
    } else {
        b.style.display = 'none';
    }
}
function pollSidebarNotifBadge() {
    fetch('/notifications/unread-count', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(d => syncSidebarNotifBadge(d.count))
        .catch(() => {});
}
document.addEventListener('DOMContentLoaded', () => {
    pollSidebarNotifBadge();
    setInterval(() => { if (!document.hidden) pollSidebarNotifBadge(); }, 30000);

    // Real-time: notif baru langsung update badge sidebar
    if (window.Echo) {
        window.Echo.channel('device-status')
            .listen('.NotificationCreated', () => pollSidebarNotifBadge());
    }
});

/* Close sidebar when clicking outside (overlay or main content area) */
function closeMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    if (sidebar?.classList.contains('open')) {
        sidebar.classList.remove('open');
        overlay?.classList.remove('active');
        document.body.style.overflow = '';
    }
}

document.getElementById('overlay')?.addEventListener('click', closeMobileSidebar);

/* Close sidebar when clicking anywhere outside sidebar */
document.addEventListener('click', function (e) {
    if (window.innerWidth <= 1024) {
        const sidebar = document.getElementById('sidebar');
        // Find ALL toggle buttons (sidebar-toggle class OR onclick=toggleSidebar)
        const isToggleBtn = e.target.closest('button[onclick*="toggleSidebar"], .sidebar-toggle');

        if (sidebar?.classList.contains('open') &&
            !sidebar?.contains(e.target) &&
            !isToggleBtn) {
            closeMobileSidebar();
        }
    }
});

/* Close mobile sidebar after nav (with brief delay for visual close) */
document.querySelectorAll('.menu-link').forEach(link => {
    link.addEventListener('click', function (e) {
        if (window.innerWidth <= 1024) {
            const sidebar = document.getElementById('sidebar');
            if (sidebar?.classList.contains('open')) {
                e.preventDefault();
                closeMobileSidebar();
                setTimeout(() => { window.location.href = this.href; }, 200);
            }
        }
    });
});

/* ===== IDLE TIMEOUT ===== */
(function () {
    const role  = "<?php echo e(Auth::check() ? Auth::user()->role : ''); ?>";
    const idleMs = role === 'admin' ? 600000 : role === 'operator' ? 300000 : 120000;
    let timer;

    function resetTimer() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const form = document.createElement('form');
            form.method = 'POST'; form.action = '/logout'; form.style.display = 'none';
            const csrf = document.createElement('input');
            csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '<?php echo e(csrf_token()); ?>';
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }, idleMs);
    }
    ['mousemove','keypress','click','scroll','touchstart'].forEach(ev => {
        document.addEventListener(ev, resetTimer, { passive: true });
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) resetTimer(); });
    resetTimer();
})();

/* ===== TOAST helper (used across pages) ===== */
window.smToast = function (msg, type = 'info') {
    document.querySelectorAll('.toast').forEach(t => t.remove());
    const icons = { success: 'fa-circle-check', error: 'fa-circle-exclamation', info: 'fa-circle-info', warn: 'fa-triangle-exclamation' };
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<span class="icon"><i class="fa-solid ${icons[type] || icons.info}"></i></span><span>${msg}</span>`;
    document.body.appendChild(t);
    setTimeout(() => { t.classList.add('toast-out'); setTimeout(() => t.remove(), 240); }, 2800);
};

/* ===== Auto-fire toast from Laravel session flash ===== */
<?php
    $smFlash = [
        'success' => session('success'),
        'error'   => session('error'),
        'warning' => session('warning'),
        'info'    => session('info'),
    ];
?>
(function () {
    const flash = <?php echo json_encode($smFlash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const typeMap = { success: 'success', error: 'error', warning: 'warn', info: 'info' };
    for (const key of ['success', 'error', 'warning', 'info']) {
        if (flash[key]) {
            setTimeout(() => window.smToast(flash[key], typeMap[key]), 100);
            break;
        }
    }
})();
</script>
<?php /**PATH C:\laragon\www\tugasakhirremotac\resources\views/components/sidebar-scripts.blade.php ENDPATH**/ ?>