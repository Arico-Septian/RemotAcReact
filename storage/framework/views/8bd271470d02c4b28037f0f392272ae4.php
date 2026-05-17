<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>My Profile — SmartAC</title>
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php echo app('Illuminate\Foundation\Vite')('resources/js/app.js'); ?>


    <?php echo $__env->make('components.sidebar-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <style>
        /* Profile page polish */
        .profile-shell { max-width: 680px; margin: 0 auto; }
        .profile-shell > .panel ~ .panel { margin-top: 16px; }
        .profile-shell .panel-lg { padding: 22px 24px; }

        /* Stack eyebrow on top, title below — instead of horizontal split */
        .profile-shell .panel-header {
            display: block;
            margin-bottom: 18px;
        }
        .profile-shell .panel-header .eyebrow { margin-bottom: 6px; }
        .profile-shell .panel-header .panel-title { font-size: 15px; }
        .profile-shell .panel-header .panel-subtitle { margin-top: 4px; }

        .profile-identity { display: flex; align-items: center; gap: 18px; }
        .profile-identity .avatar-wrap { position: relative; flex-shrink: 0; }
        .profile-identity .avatar-xl { width: 76px; height: 76px; border-radius: 999px; object-fit: cover; }
        .profile-identity-main { flex: 1; min-width: 0; }
        .profile-identity-aside { flex-shrink: 0; align-self: center; }
        .profile-identity .name { font-size: 20px; font-weight: 700; color: var(--ink-0); margin: 0; letter-spacing: -.01em; text-transform: capitalize; }
        .profile-identity .meta { display: flex; align-items: center; gap: 8px; margin-top: 6px; flex-wrap: wrap; color: var(--ink-3); font-size: 12px; }
        .profile-identity .meta-sep { color: var(--ink-4); }
        .profile-identity .remove-photo {
            background: rgba(255,85,119,0.08); border: 1px solid rgba(255,85,119,0.25);
            color: #ff5577; font-size: 11px; font-weight: 600; cursor: pointer;
            padding: 6px 12px; border-radius: 8px;
            display: inline-flex; align-items: center; gap: 6px;
            transition: background .15s, border-color .15s;
        }
        .profile-identity .remove-photo:hover { background: rgba(255,85,119,0.14); border-color: rgba(255,85,119,0.4); }

        @media (max-width: 480px) {
            .profile-identity { flex-wrap: wrap; }
            .profile-identity-aside { width: 100%; }
        }

        .profile-info-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; text-align: center; }
        .profile-info-grid > div { padding: 2px 0; }
        .profile-info-grid > div + div { border-left: 1px solid var(--line-soft); }
        @media (max-width: 540px) {
            .profile-info-grid { grid-template-columns: 1fr 1fr; }
            .profile-info-grid > div + div { border-left: none; }
        }
        .profile-info-label { font-size: 9px; color: var(--ink-3); text-transform: uppercase; letter-spacing: .08em; font-weight: 700; margin: 0 0 4px; }
        .profile-info-value { font-size: 12px; color: var(--ink-0); margin: 0; line-height: 1.35; font-weight: 600; }

        .profile-pwd-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 540px) { .profile-pwd-grid { grid-template-columns: 1fr; } }
        .profile-pwd-field { position: relative; }
        .profile-pwd-field .input { padding-right: 38px; }
        .profile-pwd-field .toggle-eye { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--ink-3); cursor: pointer; padding: 4px; }
        .profile-pwd-field .toggle-eye:hover { color: var(--ink-1); }

        .profile-pwd-actions { display: flex; justify-content: flex-end; margin-top: 14px; }

        .profile-error-box { padding: 10px 12px; border-radius: 10px; background: rgba(255,85,119,0.08); border: 1px solid rgba(255,85,119,0.25); margin-bottom: 14px; }
        .profile-error-box p { margin: 0; font-size: 12px; color: #ff5577; }
        .profile-error-box p + p { margin-top: 4px; }

        .profile-logout { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .profile-logout .text { min-width: 0; }
        .profile-logout .text h3 { margin: 0; font-size: 14px; font-weight: 700; color: var(--ink-0); }
        .profile-logout .text p { margin: 2px 0 0; font-size: 12px; color: var(--ink-3); }
    </style>

</head>
<body>
<div class="custom-bg"></div>
<div id="overlay"></div>

<div class="layout">
    <?php echo $__env->make('components.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="main-content">
        <header class="main-header">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden btn-icon" title="Menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="app-header-title">
                    <h1>My Profile</h1>
                    <p>Account &amp; security settings</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <?php echo $__env->make('components.notification-bell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <span class="pill <?php echo e($user->isOnline ? 'pill-online' : 'pill-offline'); ?>">
                    <span class="dot"></span><span><?php echo e($user->isOnline ? 'Online' : 'Offline'); ?></span>
                </span>
            </div>
        </header>

        <div class="page-body">
            <div class="app-content">
                <div class="app-content-inner profile-shell">

                    
                    <div class="panel panel-lg profile-identity">
                        <div class="avatar-wrap">
                            <?php if($user->avatar_url): ?>
                                <img src="<?php echo e($user->avatar_url); ?>" alt="<?php echo e($user->name); ?>" class="avatar avatar-xl">
                            <?php else: ?>
                                <div class="avatar avatar-xl"><?php echo e(strtoupper(substr($user->name, 0, 1))); ?></div>
                            <?php endif; ?>
                            <button type="button" id="avatarBtn"
                                    title="<?php echo e($user->avatar ? 'Ubah foto' : 'Tambah foto'); ?>"
                                    onclick="document.getElementById('avatarInput').click()"
                                    style="position:absolute;right:-2px;bottom:-2px;width:28px;height:28px;border-radius:999px;background:#0ea5e9;border:2px solid var(--panel-1);color:#0b1220;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.25);">
                                <i class="fa-solid fa-camera text-[10px]"></i>
                            </button>
                        </div>
                        <div class="profile-identity-main">
                            <h2 class="name"><?php echo e($user->name); ?></h2>
                            <div class="meta">
                                <span class="badge-role <?php echo e($user->role); ?>" style="padding:4px 10px;border-radius:6px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;"><?php echo e($user->role); ?></span>
                                <span class="meta-sep">·</span>
                                <span>Member sejak <?php echo e($user->created_at->format('M Y')); ?></span>
                            </div>
                        </div>
                        <?php if($user->avatar): ?>
                            <div class="profile-identity-aside">
                                <form method="POST" action="<?php echo e(route('profile.avatar.delete')); ?>" style="margin:0;"
                                      onsubmit="return confirm('Hapus foto profil?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="remove-photo" title="Hapus foto profil">
                                        <i class="fa-solid fa-trash text-[10px]"></i>
                                        <span>Hapus foto</span>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                    
                    <form id="avatarForm" method="POST" action="<?php echo e(route('profile.avatar.upload')); ?>"
                          enctype="multipart/form-data" style="display:none;">
                        <?php echo csrf_field(); ?>
                        <input type="file" id="avatarInput" name="avatar"
                               accept="image/jpeg,image/png,image/webp"
                               onchange="handleAvatarSelect(this)">
                    </form>

                    
                    <div id="avatarPreviewModal"
                         style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(7,16,31,0.72);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:16px;">
                        <div style="max-width:360px;width:100%;background:var(--panel-1);border:1px solid var(--line);border-radius:18px;padding:22px;box-shadow:0 20px 60px -20px rgba(0,0,0,0.6);">
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                                <span style="width:34px;height:34px;border-radius:10px;background:var(--cyan-soft);border:1px solid var(--cyan-soft-2);display:inline-flex;align-items:center;justify-content:center;color:var(--cyan);">
                                    <i class="fa-solid fa-camera"></i>
                                </span>
                                <div>
                                    <h3 style="margin:0;font-size:15px;font-weight:700;color:var(--ink-0);">Konfirmasi Foto Profil</h3>
                                    <p style="margin:2px 0 0;font-size:12px;color:var(--ink-3);">Cek preview di bawah sebelum upload</p>
                                </div>
                            </div>
                            <div style="display:flex;justify-content:center;margin-bottom:14px;">
                                <img id="avatarPreviewImg" alt="Preview"
                                     style="width:160px;height:160px;border-radius:14px;object-fit:cover;border:1px solid var(--line);background:var(--panel-2);">
                            </div>
                            <p id="avatarPreviewMeta" style="margin:0 0 14px;font-size:12px;color:var(--ink-3);text-align:center;"></p>
                            <div style="display:flex;gap:8px;">
                                <button type="button" id="avatarPreviewCancel" class="btn btn-ghost" style="flex:1;">Batal</button>
                                <button type="button" id="avatarPreviewConfirm" class="btn btn-primary" style="flex:1;">
                                    <i class="fa-solid fa-cloud-arrow-up text-[11px]"></i>
                                    <span>Upload</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    
                    <div class="panel panel-lg">
                        <div class="panel-header" style="margin-bottom:14px;">
                            <p class="eyebrow" style="margin:0;"><i class="fa-solid fa-user-circle"></i> Account Information</p>
                        </div>
                        <div class="profile-info-grid">
                            <div>
                                <p class="profile-info-label">Last Login</p>
                                <p class="profile-info-value"><?php echo e($user->last_login_at ? $user->last_login_at->format('M d, Y H:i') : 'Never'); ?></p>
                            </div>
                            <div>
                                <p class="profile-info-label">Last Activity</p>
                                <p class="profile-info-value"><?php echo e($user->last_activity ? $user->last_activity->diffForHumans() : '-'); ?></p>
                            </div>
                            <div>
                                <p class="profile-info-label">Join Date</p>
                                <p class="profile-info-value"><?php echo e($user->created_at->format('M d, Y')); ?></p>
                            </div>
                        </div>
                    </div>

                    
                    <div class="panel panel-lg">
                        <div class="panel-header" style="margin-bottom:14px;">
                            <p class="eyebrow"><i class="fa-solid fa-key"></i> Security</p>
                            <h3 class="panel-title">Ganti Password</h3>
                            <p class="panel-subtitle">Gunakan password yang kuat dan unik</p>
                        </div>
                        <?php if($errors->any()): ?>
                            <div class="profile-error-box">
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <p><i class="fa-solid fa-circle-exclamation text-[10px]"></i> <?php echo e($err); ?></p>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" action="/change-password" id="changePasswordForm" autocomplete="off">
                            <?php echo csrf_field(); ?>
                            <div class="field">
                                <label class="field-label">Password Saat Ini</label>
                                <div class="profile-pwd-field">
                                    <input class="input" type="password" name="current_password" id="cp_current"
                                           placeholder="••••••••" required autocomplete="current-password">
                                    <button type="button" data-toggle="#cp_current" class="toggle-eye" title="Tampilkan/sembunyikan">
                                        <i class="fa-solid fa-eye text-[12px]"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="profile-pwd-grid mt-3">
                                <div class="field">
                                    <label class="field-label">Password Baru</label>
                                    <div class="profile-pwd-field">
                                        <input class="input" type="password" name="password" id="cp_new"
                                               placeholder="Minimal 6 karakter" required minlength="6" autocomplete="new-password">
                                        <button type="button" data-toggle="#cp_new" class="toggle-eye" title="Tampilkan/sembunyikan">
                                            <i class="fa-solid fa-eye text-[12px]"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="field">
                                    <label class="field-label">Konfirmasi</label>
                                    <div class="profile-pwd-field">
                                        <input class="input" type="password" name="password_confirmation" id="cp_confirm"
                                               placeholder="Ketik ulang" required minlength="6" autocomplete="new-password">
                                        <button type="button" data-toggle="#cp_confirm" class="toggle-eye" title="Tampilkan/sembunyikan">
                                            <i class="fa-solid fa-eye text-[12px]"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <p id="cpMatchHint" class="field-help" style="display:none;color:#ff5577;margin-top:6px;">Password baru tidak cocok</p>
                            <div class="profile-pwd-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-key text-[11px]"></i>
                                    <span>Update Password</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    
                    <div class="panel panel-lg profile-logout">
                        <div class="text">
                            <h3><i class="fa-solid fa-right-from-bracket text-[11px]" style="color:#ff5577;margin-right:6px;"></i>Keluar dari Akun</h3>
                            <p>Mengakhiri sesi login di browser ini</p>
                        </div>
                        <form action="/logout" method="POST" onsubmit="return confirm('Keluar dari akun?');" style="margin:0;">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-soft" style="color:#ff5577;border-color:rgba(255,85,119,0.3);">
                                <i class="fa-solid fa-right-from-bracket text-[11px]"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php echo $__env->make('components.bottom-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<script>
(function () {
    const modal      = document.getElementById('avatarPreviewModal');
    const previewImg = document.getElementById('avatarPreviewImg');
    const previewMeta= document.getElementById('avatarPreviewMeta');
    const cancelBtn  = document.getElementById('avatarPreviewCancel');
    const confirmBtn = document.getElementById('avatarPreviewConfirm');
    const form       = document.getElementById('avatarForm');
    const input      = document.getElementById('avatarInput');
    let currentObjectUrl = null;

    function openModal() {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeModal({ clearInput = true } = {}) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        if (currentObjectUrl) {
            URL.revokeObjectURL(currentObjectUrl);
            currentObjectUrl = null;
        }
        previewImg.src = '';
        if (clearInput) input.value = '';
    }
    function formatBytes(b) {
        if (b < 1024) return b + ' B';
        if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
        return (b / (1024 * 1024)).toFixed(2) + ' MB';
    }

    window.handleAvatarSelect = function (el) {
        const file = el.files[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) {
            (window.smToast || alert)('Ukuran file maksimal 2 MB.', 'error');
            el.value = '';
            return;
        }
        const allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowed.includes(file.type)) {
            (window.smToast || alert)('Format yang didukung: JPG, PNG, WEBP.', 'error');
            el.value = '';
            return;
        }
        if (currentObjectUrl) URL.revokeObjectURL(currentObjectUrl);
        currentObjectUrl = URL.createObjectURL(file);
        previewImg.src = currentObjectUrl;
        previewMeta.textContent = `${file.name} · ${formatBytes(file.size)}`;
        openModal();
    };

    cancelBtn.addEventListener('click', () => closeModal());
    confirmBtn.addEventListener('click', () => {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[11px]"></i><span>Mengupload...</span>';
        closeModal({ clearInput: false });
        form.submit();
    });
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
    });
})();

/* ===== Change-password helpers ===== */
(function () {
    document.querySelectorAll('button[data-toggle]').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.querySelector(btn.dataset.toggle);
            if (!target) return;
            const isPwd = target.type === 'password';
            target.type = isPwd ? 'text' : 'password';
            const icon = btn.querySelector('i');
            if (icon) icon.classList.toggle('fa-eye', !isPwd), icon.classList.toggle('fa-eye-slash', isPwd);
        });
    });

    const form    = document.getElementById('changePasswordForm');
    const newPwd  = document.getElementById('cp_new');
    const confirm = document.getElementById('cp_confirm');
    const hint    = document.getElementById('cpMatchHint');
    if (!form || !newPwd || !confirm || !hint) return;

    function checkMatch() {
        if (!confirm.value) { hint.style.display = 'none'; return true; }
        const ok = newPwd.value === confirm.value;
        hint.style.display = ok ? 'none' : 'block';
        return ok;
    }
    newPwd.addEventListener('input', checkMatch);
    confirm.addEventListener('input', checkMatch);
    form.addEventListener('submit', (e) => {
        if (!checkMatch()) {
            e.preventDefault();
            (window.smToast || alert)('Password baru tidak cocok', 'error');
            confirm.focus();
        }
    });
})();
</script>
<?php echo $__env->make('components.sidebar-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>
</html>

<?php /**PATH C:\laragon\www\tugasakhirremotac\resources\views/profile/index.blade.php ENDPATH**/ ?>