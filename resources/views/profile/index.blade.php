<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Profile — SmartAC</title>
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite('resources/js/app.js')
    @include('components.sidebar-styles')
    <style>
        .profile-avatar-wrap { position: relative; flex-shrink: 0; }
        .profile-avatar-xl {
            width: 72px; height: 72px; border-radius: 999px;
            object-fit: cover; font-size: 28px;
            display: flex; align-items: center; justify-content: center;
        }
        .profile-avatar-btn {
            position: absolute; right: -2px; bottom: -2px;
            width: 26px; height: 26px; border-radius: 999px;
            background: #0ea5e9; border: 2px solid var(--panel-1);
            color: #0b1220; display: inline-flex; align-items: center;
            justify-content: center; cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        }
        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 0;
        }
        .profile-info-cell { padding: 10px 0; text-align: center; }
        .profile-info-cell + .profile-info-cell { border-left: 1px solid var(--line-soft); }
        .profile-info-label { font-size: 10px; color: var(--ink-3); text-transform: uppercase; letter-spacing: .08em; font-weight: 700; margin: 0 0 4px; }
        .profile-info-value { font-size: 12px; color: var(--ink-0); margin: 0; font-weight: 600; line-height: 1.4; }

        .pwd-field { position: relative; }
        .pwd-field .input { padding-right: 38px; }
        .pwd-field .toggle-eye {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--ink-3); cursor: pointer; padding: 4px;
        }
        .pwd-field .toggle-eye:hover { color: var(--ink-1); }

        .profile-error-box {
            padding: 10px 12px; border-radius: 10px;
            background: rgba(255,85,119,0.08); border: 1px solid rgba(255,85,119,0.25);
            margin-bottom: 14px;
        }
        .profile-error-box p { margin: 0; font-size: 12px; color: #ff5577; }
        .profile-error-box p + p { margin-top: 4px; }

        @media (max-width: 768px) {
            .profile-info-grid { grid-template-columns: 1fr 1fr; }
            .profile-info-cell:nth-child(3) { border-left: none; border-top: 1px solid var(--line-soft); grid-column: 1 / -1; text-align: left; }
            .profile-info-cell { text-align: left; }
            .profile-info-cell + .profile-info-cell { border-left: none; padding-left: 0; }
            .profile-info-cell:nth-child(2) { border-left: 1px solid var(--line-soft); padding-left: 12px; }
        }
        @media (max-width: 480px) {
            .profile-info-grid { grid-template-columns: 1fr; }
            .profile-info-cell + .profile-info-cell { border-left: none; border-top: 1px solid var(--line-soft); }
            .profile-info-cell:nth-child(2) { border-left: none; padding-left: 0; }
            .profile-info-cell:nth-child(3) { grid-column: auto; }
        }
    </style>
</head>
<body>
<div id="overlay"></div>

<div class="layout">
    @include('components.sidebar')

    <div class="main-content">
        <header class="main-header">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden btn-icon" title="Menu">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
                <div class="app-header-title">
                    <h1>My Profile</h1>
                    <p>Account &amp; security settings</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @include('components.notification-bell')
                <span class="pill {{ $user->isOnline ? 'pill-online' : 'pill-offline' }}">
                    <span class="dot"></span>
                    <span>{{ $user->isOnline ? 'Online' : 'Offline' }}</span>
                </span>
            </div>
        </header>

        <div class="page-body">
            <div class="app-content">
                <div class="app-content-inner space-y-4" style="max-width:720px;">

                    {{-- Identity Card --}}
                    <div class="tbl-wrap" style="padding:0;">
                        <div class="tbl-toolbar" style="border-bottom:1px solid var(--line-soft);">
                            <div class="app-header-title" style="margin:0;">
                                <h1 style="font-size:13px;"><i class="fa-solid fa-circle-user" style="color:var(--cyan);margin-right:7px;"></i>Identity</h1>
                                <p>Profile photo and account information</p>
                            </div>
                        </div>
                        <div style="padding:16px;">
                            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                                <div class="profile-avatar-wrap">
                                    @if ($user->avatar_url)
                                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                                             class="avatar profile-avatar-xl">
                                    @else
                                        <div class="avatar profile-avatar-xl">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                    @endif
                                    <button type="button" class="profile-avatar-btn"
                                            title="{{ $user->avatar ? 'Change photo' : 'Add photo' }}"
                                            onclick="document.getElementById('avatarInput').click()">
                                        <i class="fa-solid fa-camera text-[10px]"></i>
                                    </button>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <p style="margin:0;font-size: 18px;font-weight:700;color:var(--ink-0);word-break:break-word;">{{ $user->name }}</p>
                                    <div style="display:flex;align-items:center;gap:8px;margin-top:6px;flex-wrap:wrap;">
                                        <span class="badge-role {{ $user->role }}" style="padding:3px 10px;border-radius: 8px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">{{ $user->role }}</span>
                                        <span style="font-size:12px;color:var(--ink-3);">Bergabung {{ $user->created_at->format('M Y') }}</span>
                                    </div>
                                </div>
                                @if ($user->avatar)
                                    <form method="POST" action="{{ route('profile.avatar.delete') }}" style="margin:0;"
                                          onsubmit="return confirm('Delete profile photo?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;border:1px solid rgba(255,85,119,0.25);background:rgba(255,85,119,0.08);color:#ff5577;font-size:11px;font-weight:600;cursor:pointer;">
                                            <i class="fa-solid fa-trash text-[10px]"></i>
                                            <span>Delete Photo</span>
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <div class="profile-info-grid" style="margin-top:16px;border-top:1px solid var(--line-soft);padding-top:14px;">
                                <div class="profile-info-cell">
                                    <p class="profile-info-label">Last Login</p>
                                    <p class="profile-info-value">{{ $user->last_login_at ? $user->last_login_at->format('d M Y H:i') : 'Never' }}</p>
                                </div>
                                <div class="profile-info-cell">
                                    <p class="profile-info-label">Last Activity</p>
                                    <p class="profile-info-value">{{ $user->last_activity ? $user->last_activity->diffForHumans() : '-' }}</p>
                                </div>
                                <div class="profile-info-cell">
                                    <p class="profile-info-label">Joined Date</p>
                                    <p class="profile-info-value">{{ $user->created_at->format('d M Y') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Change Password --}}
                    <div class="tbl-wrap" style="padding:0;">
                        <div class="tbl-toolbar" style="border-bottom:1px solid var(--line-soft);">
                            <div class="app-header-title" style="margin:0;">
                                <h1 style="font-size:13px;"><i class="fa-solid fa-key" style="color:var(--cyan);margin-right:7px;"></i>Security</h1>
                                <p>Use a strong and unique password</p>
                            </div>
                        </div>
                        <div style="padding:4px 16px 16px;">
                            @if ($errors->any())
                                <div class="profile-error-box" style="margin-top:12px;">
                                    @foreach ($errors->all() as $err)
                                        <p><i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $err }}</p>
                                    @endforeach
                                </div>
                            @endif
                            <form method="POST" action="/change-password" id="changePasswordForm" autocomplete="off">
                                @csrf
                                <div class="setting-row">
                                    <label class="setting-label">Current Password</label>
                                    <div class="pwd-field" style="width:200px;">
                                        <input class="input" type="password" name="current_password" id="cp_current"
                                               placeholder="••••••••" required autocomplete="current-password"
                                               style="width:100%;font-size:13px;">
                                        <button type="button" data-toggle="#cp_current" class="toggle-eye" title="Show/hide">
                                            <i class="fa-solid fa-eye text-[12px]"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="setting-row">
                                    <label class="setting-label">New Password</label>
                                    <div class="pwd-field" style="width:200px;">
                                        <input class="input" type="password" name="password" id="cp_new"
                                               placeholder="Min 8 characters" required minlength="8" autocomplete="new-password"
                                               style="width:100%;font-size:13px;">
                                        <button type="button" data-toggle="#cp_new" class="toggle-eye" title="Show/hide">
                                            <i class="fa-solid fa-eye text-[12px]"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="setting-row">
                                    <label class="setting-label">Confirm Password</label>
                                    <div class="pwd-field" style="width:200px;">
                                        <input class="input" type="password" name="password_confirmation" id="cp_confirm"
                                               placeholder="Retype password" required minlength="8" autocomplete="new-password"
                                               style="width:100%;font-size:13px;">
                                        <button type="button" data-toggle="#cp_confirm" class="toggle-eye" title="Show/hide">
                                            <i class="fa-solid fa-eye text-[12px]"></i>
                                        </button>
                                    </div>
                                </div>
                                <p id="cpMatchHint" style="display:none;font-size:11px;color:#ff5577;margin:4px 0 0;">New passwords do not match</p>
                                <div class="flex justify-end" style="margin-top:14px;">
                                    <button type="submit" id="changePwdBtn" class="btn btn-primary">
                                        <i class="fa-solid fa-key text-[11px]"></i>
                                        <span>Save Password</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Logout --}}
                    <div class="tbl-wrap" style="padding:0;">
                        <div style="padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                            <div>
                                <p style="margin:0;font-size:13px;font-weight:700;color:var(--ink-0);">
                                    <i class="fa-solid fa-right-from-bracket text-[11px]" style="color:#ff5577;margin-right:6px;"></i>Sign Out
                                </p>
                                <p style="margin:3px 0 0;font-size:12px;color:var(--ink-3);">End the login session in this browser</p>
                            </div>
                            <form action="/logout" method="POST" onsubmit="return confirm('Sign out of your account?');" style="margin:0;">
                                @csrf
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
</div>

{{-- Hidden avatar upload form --}}
<form id="avatarForm" method="POST" action="{{ route('profile.avatar.upload') }}"
      enctype="multipart/form-data" style="display:none;">
    @csrf
    <input type="file" id="avatarInput" name="avatar"
           accept="image/jpeg,image/png,image/webp"
           onchange="handleAvatarSelect(this)">
</form>

{{-- Avatar preview modal --}}
<div id="avatarPreviewModal"
     style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(7,16,31,0.72);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:16px;">
    <div style="max-width:360px;width:100%;background:var(--panel-1);border:1px solid var(--line);border-radius: 20px;padding:22px;box-shadow:0 20px 60px -20px rgba(0,0,0,0.6);">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <span style="width:34px;height:34px;border-radius:10px;background:var(--cyan-soft);border:1px solid var(--cyan-soft-2);display:inline-flex;align-items:center;justify-content:center;color:var(--cyan);">
                <i class="fa-solid fa-camera"></i>
            </span>
            <div>
                <h3 style="margin:0;font-size: 16px;font-weight:700;color:var(--ink-0);">Confirm Profile Photo</h3>
                <p style="margin:2px 0 0;font-size:12px;color:var(--ink-3);">Check preview before uploading</p>
            </div>
        </div>
        <div style="display:flex;justify-content:center;margin-bottom:14px;">
            <img id="avatarPreviewImg" alt="Preview"
                 style="width:160px;height:160px;border-radius: 16px;object-fit:cover;border:1px solid var(--line);background:var(--panel-2);">
        </div>
        <p id="avatarPreviewMeta" style="margin:0 0 14px;font-size:12px;color:var(--ink-3);text-align:center;"></p>
        <div style="display:flex;gap:8px;">
            <button type="button" id="avatarPreviewCancel" class="btn btn-ghost" style="flex:1;">Cancel</button>
            <button type="button" id="avatarPreviewConfirm" class="btn btn-primary" style="flex:1;">
                <i class="fa-solid fa-cloud-arrow-up text-[11px]"></i>
                <span>Upload</span>
            </button>
        </div>
    </div>
</div>

@include('components.bottom-nav')
@include('components.sidebar-scripts')

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

    function openModal() { modal.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    function closeModal({ clearInput = true } = {}) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        if (currentObjectUrl) { URL.revokeObjectURL(currentObjectUrl); currentObjectUrl = null; }
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
        if (file.size > 2 * 1024 * 1024) { (window.smToast || alert)('Ukuran file maksimal 2 MB.', 'error'); el.value = ''; return; }
        const allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowed.includes(file.type)) { (window.smToast || alert)('Format yang didukung: JPG, PNG, WEBP.', 'error'); el.value = ''; return; }
        if (currentObjectUrl) URL.revokeObjectURL(currentObjectUrl);
        currentObjectUrl = URL.createObjectURL(file);
        previewImg.src = currentObjectUrl;
        previewMeta.textContent = `${file.name} · ${formatBytes(file.size)}`;
        openModal();
    };

    cancelBtn.addEventListener('click', () => closeModal());
    confirmBtn.addEventListener('click', () => {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[11px]"></i><span>Uploading...</span>';
        closeModal({ clearInput: false });
        form.submit();
    });
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.style.display === 'flex') closeModal(); });
})();

(function () {
    document.querySelectorAll('button[data-toggle]').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.querySelector(btn.dataset.toggle);
            if (!target) return;
            const isPwd = target.type === 'password';
            target.type = isPwd ? 'text' : 'password';
            const icon = btn.querySelector('i');
            if (icon) { icon.classList.toggle('fa-eye', !isPwd); icon.classList.toggle('fa-eye-slash', isPwd); }
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

    const submitBtn = document.getElementById('changePwdBtn');
    form.addEventListener('submit', (e) => {
        if (!checkMatch()) {
            e.preventDefault();
            (window.smToast || alert)('New passwords do not match', 'error');
            confirm.focus();
            return;
        }
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('is-loading');
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[11px]"></i><span>Menyimpan...</span>';
        }
    });
})();
</script>
</body>
</html>
