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
        .profile-avatar-wrap {
            position: relative;
            flex-shrink: 0;
        }

        .profile-avatar-xl {
            width: 72px;
            height: 72px;
            border-radius: var(--r-full);
            object-fit: cover;
            font-size: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-avatar-btn {
            position: absolute;
            right: -2px;
            bottom: -2px;
            width: 26px;
            height: 26px;
            border-radius: var(--r-full);
            background: var(--cyan-d);
            border: 2px solid var(--panel-1);
            color: #0b1220;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0;
        }

        .profile-info-cell {
            padding: 10px 0;
            text-align: center;
        }

        .profile-info-cell+.profile-info-cell {
            border-left: 1px solid var(--line-soft);
        }

        .profile-info-label {
            font-size: 10px;
            color: var(--ink-3);
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .profile-info-value {
            font-size: 12px;
            color: var(--ink-0);
            margin: 0;
            font-weight: 600;
            line-height: 1.4;
        }

        .pwd-field {
            position: relative;
        }

        .pwd-field .input {
            padding-right: 38px;
        }

        .pwd-field .toggle-eye {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--ink-3);
            cursor: pointer;
            padding: 4px;
        }

        .pwd-field .toggle-eye:hover {
            color: var(--ink-1);
        }

        .profile-error-box {
            padding: 10px 12px;
            border-radius: var(--r-md);
            background: rgb(var(--danger-rgb) / 0.08);
            border: 1px solid rgb(var(--danger-rgb) / 0.25);
            margin-bottom: 14px;
        }

        .profile-error-box p {
            margin: 0;
            font-size: 12px;
            color: var(--danger);
        }

        .profile-error-box p+p {
            margin-top: 4px;
        }

        /* ============ Profile (single column, modern) ============ */
        .profile-shell {
            max-width: 760px;
            margin: 0 auto;
        }

        .profile-card {
            background: var(--panel-1);
            border: 1px solid var(--line-soft);
            border-radius: var(--r-xl);
            padding: 24px 26px;
        }

        /* Identity */
        .profile-id {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .profile-av {
            position: relative;
            flex-shrink: 0;
        }

        .profile-av-img {
            width: 94px;
            height: 94px;
            border-radius: 24px;
            object-fit: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: 800;
            color: #fff;
            background: linear-gradient(140deg, #5b8cff 0%, #8b5cf6 58%, #a855f7 100%);
            box-shadow: 0 16px 36px -12px rgba(124, 92, 246, 0.65);
        }

        .profile-av-cam {
            position: absolute;
            right: -6px;
            bottom: -6px;
            width: 34px;
            height: 34px;
            border-radius: 12px;
            background: var(--cyan-d);
            border: 3px solid var(--panel-1);
            color: #06121b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 13px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.35);
        }

        .profile-id-text {
            min-width: 0;
        }

        .profile-name-row {
            display: flex;
            align-items: center;
            gap: 9px;
            flex-wrap: wrap;
        }

        .profile-name {
            font-size: 24px;
            font-weight: 800;
            color: var(--ink-0);
            line-height: 1.1;
            word-break: break-word;
        }

        .profile-verified {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #10b981;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            flex-shrink: 0;
        }

        .profile-role {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 12px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: rgba(139, 92, 246, 0.14);
            border: 1px solid rgba(139, 92, 246, 0.32);
            color: #c4b5fd;
        }

        .profile-divider {
            height: 1px;
            background: var(--line-soft);
            margin: 22px 0;
        }

        /* Info list */
        .profile-info-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 15px 0;
        }

        .profile-info-row+.profile-info-row {
            border-top: 1px solid var(--line-soft);
        }

        .profile-info-ic {
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
        }

        .ic-blue {
            background: rgba(56, 132, 255, 0.15);
            color: #6ea8ff;
        }

        .ic-green {
            background: rgba(16, 185, 129, 0.16);
            color: #34d399;
        }

        .ic-amber {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
        }

        .profile-info-key {
            flex: 1;
            min-width: 0;
            font-size: 15px;
            color: var(--ink-1);
            font-weight: 500;
        }

        .profile-info-val {
            font-size: 15px;
            font-weight: 700;
            color: var(--ink-0);
            font-family: ui-monospace, 'SFMono-Regular', 'Consolas', monospace;
            letter-spacing: .01em;
            text-align: right;
        }

        .profile-info-val.is-online {
            color: #34d399;
            font-family: inherit;
        }

        /* Section label */
        .profile-sec-label {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .13em;
            text-transform: uppercase;
            color: var(--ink-3);
            margin: 0 0 16px;
        }

        /* Form */
        .profile-field {
            margin-bottom: 16px;
        }

        .profile-field-label {
            display: block;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--ink-1);
            margin-bottom: 9px;
        }

        .profile-input-wrap {
            position: relative;
        }

        .profile-input {
            width: 100%;
            height: 52px;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.02);
            color: var(--ink-0);
            font-size: 15px;
            padding: 0 46px 0 16px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        .profile-input::placeholder {
            color: #64748b;
        }

        /* Hide the browser's native password reveal/clear icon (Edge/IE) so only
           our custom eye toggle shows */
        .profile-input::-ms-reveal,
        .profile-input::-ms-clear {
            display: none;
        }

        .profile-input:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 3px rgba(34, 184, 230, 0.15);
        }

        .profile-eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--ink-3);
            cursor: pointer;
            padding: 6px;
            font-size: 15px;
        }

        .profile-eye:hover {
            color: var(--ink-1);
        }

        /* Password requirements checklist */
        .pwd-checklist {
            list-style: none;
            margin: 11px 0 16px;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .pwd-checklist li {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 12.5px;
            color: #94a3b8;
            transition: color .15s;
        }

        .pwd-checklist li i {
            font-size: 13px;
            width: 14px;
            text-align: center;
            color: #5b6677;
            transition: color .15s;
        }

        .pwd-checklist li.met,
        .pwd-checklist li.met i {
            color: #34d399;
        }

        /* Save button */
        .profile-save {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            font-size: 15px;
            font-weight: 700;
            color: #06121b;
            background: linear-gradient(90deg, var(--cyan), var(--cyan-d));
            box-shadow: 0 10px 30px -8px rgba(34, 184, 230, 0.55);
            transition: filter .15s, transform .05s;
        }

        .profile-save:hover {
            filter: brightness(1.05);
        }

        .profile-save:active {
            transform: translateY(1px);
        }

        .profile-save:disabled {
            opacity: .7;
            cursor: default;
        }

        .profile-match-hint {
            display: none;
            font-size: 12px;
            color: #ff5577;
            margin: -6px 0 14px;
        }

        /* Sign out */
        .profile-signout {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }

        .profile-signout-title {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: var(--ink-0);
        }

        .profile-signout-sub {
            margin: 3px 0 0;
            font-size: 13px;
            color: var(--ink-3);
        }

        .profile-logout {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            color: #ff5577;
            background: rgba(255, 85, 119, 0.08);
            border: 1px solid rgba(255, 85, 119, 0.30);
            transition: background .15s, border-color .15s;
        }

        .profile-logout:hover {
            background: rgba(255, 85, 119, 0.14);
            border-color: rgba(255, 85, 119, 0.5);
        }

        @media (max-width: 600px) {
            .profile-card {
                padding: 18px 16px;
            }

            .profile-av-img {
                width: 80px;
                height: 80px;
                font-size: 32px;
            }

            .profile-name {
                font-size: 21px;
            }

            .profile-info-key,
            .profile-info-val {
                font-size: 13.5px;
            }

            .profile-info-ic {
                width: 38px;
                height: 38px;
            }
        }

        @media (max-width: 768px) {
            .profile-info-grid {
                grid-template-columns: 1fr 1fr;
            }

            .profile-info-cell:nth-child(3) {
                border-left: none;
                border-top: 1px solid var(--line-soft);
                grid-column: 1 / -1;
                text-align: left;
            }

            .profile-info-cell {
                text-align: left;
            }

            .profile-info-cell+.profile-info-cell {
                border-left: none;
                padding-left: 0;
            }

            .profile-info-cell:nth-child(2) {
                border-left: 1px solid var(--line-soft);
                padding-left: 12px;
            }
        }

        @media (max-width: 480px) {
            .profile-info-grid {
                grid-template-columns: 1fr;
            }

            .profile-info-cell+.profile-info-cell {
                border-left: none;
                border-top: 1px solid var(--line-soft);
            }

            .profile-info-cell:nth-child(2) {
                border-left: none;
                padding-left: 0;
            }

            .profile-info-cell:nth-child(3) {
                grid-column: auto;
            }

            .profile-avatar-xl {
                width: 58px;
                height: 58px;
                font-size: 22px;
            }

            .profile-avatar-btn {
                width: 22px;
                height: 22px;
            }
        }
    </style>
</head>

<body>
    <div class="custom-bg"></div>
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
                </div>
            </header>
            <div class="page-body">
                <div class="app-content">
                    <div class="app-content-inner profile-shell">
                        <div class="profile-card">
                            {{-- Identity --}}
                            <div class="profile-id">
                                <div class="profile-av">
                                    @if ($user->avatar_url)
                                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                                            class="profile-av-img">
                                    @else
                                        <div class="profile-av-img">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                    @endif
                                    <button type="button" class="profile-av-cam"
                                        title="{{ $user->avatar ? 'Change photo' : 'Add photo' }}"
                                        onclick="document.getElementById('avatarInput').click()">
                                        <i class="fa-solid fa-camera"></i>
                                    </button>
                                </div>
                                <div class="profile-id-text">
                                    <div class="profile-name-row">
                                        <span class="profile-name">{{ $user->name }}</span>
                                        <span class="profile-verified" title="Verified account"><i
                                                class="fa-solid fa-check"></i></span>
                                    </div>
                                    <span class="profile-role">
                                        <i class="fa-solid fa-shield-halved text-[11px]"></i>
                                        {{ $user->role === 'admin' ? 'Administrator' : ucfirst($user->role) }}
                                    </span>
                                </div>
                            </div>

                            <div class="profile-divider"></div>

                            {{-- Account info --}}
                            @php $isOnline = $user->last_activity && $user->last_activity->gt(now()->subMinutes(2)); @endphp
                            <div class="profile-info-row">
                                <span class="profile-info-ic ic-blue"><i class="fa-regular fa-clock"></i></span>
                                <span class="profile-info-key">Last login</span>
                                <span
                                    class="profile-info-val">{{ $user->last_login_at ? $user->last_login_at->format('d M Y, H:i') : 'Never' }}</span>
                            </div>
                            <div class="profile-info-row">
                                <span class="profile-info-ic ic-green"><i class="fa-solid fa-signal"></i></span>
                                <span class="profile-info-key">Last activity</span>
                                <span
                                    class="profile-info-val {{ $isOnline ? 'is-online' : '' }}">{{ $isOnline ? 'Online now' : ($user->last_activity ? $user->last_activity->diffForHumans() : '-') }}</span>
                            </div>
                            <div class="profile-info-row">
                                <span class="profile-info-ic ic-amber"><i class="fa-regular fa-calendar"></i></span>
                                <span class="profile-info-key">Joined date</span>
                                <span class="profile-info-val">{{ $user->created_at->format('d M Y') }}</span>
                            </div>

                            <div class="profile-divider"></div>

                            {{-- Change password --}}
                            <p class="profile-sec-label">Change Password</p>
                            @if ($errors->any())
                                <div class="profile-error-box">
                                    @foreach ($errors->all() as $err)
                                        <p><i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $err }}</p>
                                    @endforeach
                                </div>
                            @endif
                            <form method="POST" action="/change-password" id="changePasswordForm" autocomplete="off">
                                @csrf
                                <div class="profile-field">
                                    <label class="profile-field-label">Current password</label>
                                    <div class="profile-input-wrap">
                                        <input class="profile-input" type="password" name="current_password"
                                            id="cp_current" placeholder="••••••••••" required
                                            autocomplete="current-password">
                                        <button type="button" data-toggle="#cp_current" class="profile-eye"
                                            title="Show/hide"><i class="fa-solid fa-eye"></i></button>
                                    </div>
                                </div>
                                <div class="profile-field">
                                    <label class="profile-field-label">New password</label>
                                    <div class="profile-input-wrap">
                                        <input class="profile-input" type="password" name="password" id="cp_new"
                                            placeholder="Min. 8 characters" required minlength="8"
                                            autocomplete="new-password">
                                        <button type="button" data-toggle="#cp_new" class="profile-eye"
                                            title="Show/hide"><i class="fa-solid fa-eye"></i></button>
                                    </div>
                                    <ul class="pwd-checklist" id="pwdChecklist">
                                        <li data-rule="len"><i class="fa-regular fa-circle"></i><span>At least 8
                                                characters</span></li>
                                        <li data-rule="case"><i class="fa-regular fa-circle"></i><span>One uppercase &
                                                one lowercase letter</span></li>
                                        <li data-rule="num"><i class="fa-regular fa-circle"></i><span>At least one
                                                number</span></li>
                                    </ul>
                                </div>
                                <div class="profile-field">
                                    <label class="profile-field-label">Confirm new password</label>
                                    <div class="profile-input-wrap">
                                        <input class="profile-input" type="password" name="password_confirmation"
                                            id="cp_confirm" placeholder="Retype password" required minlength="8"
                                            autocomplete="new-password">
                                        <button type="button" data-toggle="#cp_confirm" class="profile-eye"
                                            title="Show/hide"><i class="fa-solid fa-eye"></i></button>
                                    </div>
                                </div>
                                <p id="cpMatchHint" class="profile-match-hint">New passwords do not match</p>
                                <button type="submit" id="changePwdBtn" class="profile-save">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    <span>Save password</span>
                                </button>
                            </form>

                            <div class="profile-divider"></div>

                            {{-- Sign out --}}
                            <div class="profile-signout">
                                <div>
                                    <p class="profile-signout-title">Sign out</p>
                                    <p class="profile-signout-sub">End the login session in this browser.</p>
                                </div>
                                <form action="/logout" method="POST"
                                    onsubmit="return confirm('Sign out of your account?');" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="profile-logout">
                                        <i class="fa-solid fa-right-from-bracket"></i>
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
        <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/webp"
            onchange="handleAvatarSelect(this)">
    </form>
    {{-- Avatar preview modal --}}
    <div id="avatarPreviewModal"
        style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(7,16,31,0.72);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:16px;">
        <div
            style="max-width:360px;width:100%;background:var(--panel-1);border:1px solid var(--line);border-radius: 20px;padding:22px;box-shadow:0 20px 60px -20px rgba(0,0,0,0.6);">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                <span
                    style="width:34px;height:34px;border-radius:10px;background:var(--cyan-soft);border:1px solid var(--cyan-soft-2);display:inline-flex;align-items:center;justify-content:center;color:var(--cyan);">
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
            <p id="avatarPreviewMeta" style="margin:0 0 14px;font-size:12px;color:var(--ink-3);text-align:center;">
            </p>
            <div style="display:flex;gap:8px;">
                <button type="button" id="avatarPreviewCancel" class="btn btn-ghost"
                    style="flex:1;">Cancel</button>
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
        (function() {
            const modal = document.getElementById('avatarPreviewModal');
            const previewImg = document.getElementById('avatarPreviewImg');
            const previewMeta = document.getElementById('avatarPreviewMeta');
            const cancelBtn = document.getElementById('avatarPreviewCancel');
            const confirmBtn = document.getElementById('avatarPreviewConfirm');
            const form = document.getElementById('avatarForm');
            const input = document.getElementById('avatarInput');
            let currentObjectUrl = null;

            function openModal() {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            function closeModal({
                clearInput = true
            } = {}) {
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

            window.handleAvatarSelect = function(el) {
                const file = el.files[0];
                if (!file) return;
                if (file.size > 2 * 1024 * 1024) {
                    (window.smToast || alert)('Maximum file size is 2 MB.', 'error');
                    el.value = '';
                    return;
                }
                const allowed = ['image/jpeg', 'image/png', 'image/webp'];
                if (!allowed.includes(file.type)) {
                    (window.smToast || alert)('Supported formats: JPG, PNG, WEBP.', 'error');
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
                confirmBtn.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin text-[11px]"></i><span>Uploading...</span>';
                closeModal({
                    clearInput: false
                });
                form.submit();
            });
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
            });
        })();

        (function() {
            document.querySelectorAll('button[data-toggle]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const target = document.querySelector(btn.dataset.toggle);
                    if (!target) return;
                    const isPwd = target.type === 'password';
                    target.type = isPwd ? 'text' : 'password';
                    const icon = btn.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-eye', !isPwd);
                        icon.classList.toggle('fa-eye-slash', isPwd);
                    }
                });
            });

            const form = document.getElementById('changePasswordForm');
            const newPwd = document.getElementById('cp_new');
            const confirm = document.getElementById('cp_confirm');
            const hint = document.getElementById('cpMatchHint');
            if (!form || !newPwd || !confirm || !hint) return;

            function checkMatch() {
                if (!confirm.value) {
                    hint.style.display = 'none';
                    return true;
                }
                const ok = newPwd.value === confirm.value;
                hint.style.display = ok ? 'none' : 'block';
                return ok;
            }
            newPwd.addEventListener('input', checkMatch);
            confirm.addEventListener('input', checkMatch);

            // Real-time password requirements checklist (same rules as the server)
            const checklist = document.getElementById('pwdChecklist');

            function pwError(v) {
                if (v.length < 8) return 'Password must be at least 8 characters.';
                if (!/[a-z]/.test(v)) return 'Password must contain a lowercase letter.';
                if (!/[A-Z]/.test(v)) return 'Password must contain an uppercase letter.';
                if (!/[0-9]/.test(v)) return 'Password must contain at least 1 number.';
                return null;
            }

            function setRule(rule, ok) {
                const li = checklist && checklist.querySelector('[data-rule="' + rule + '"]');
                if (!li) return;
                li.classList.toggle('met', ok);
                const icon = li.querySelector('i');
                if (icon) icon.className = ok ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle';
            }

            function updateChecklist() {
                const v = newPwd.value;
                setRule('len', v.length >= 8);
                setRule('case', /[a-z]/.test(v) && /[A-Z]/.test(v));
                setRule('num', /[0-9]/.test(v));
            }
            newPwd.addEventListener('input', updateChecklist);

            const submitBtn = document.getElementById('changePwdBtn');
            form.addEventListener('submit', (e) => {
                const pwErr = pwError(newPwd.value);
                if (pwErr) {
                    e.preventDefault();
                    (window.smToast || alert)(pwErr, 'error');
                    updateChecklist();
                    newPwd.focus();
                    return;
                }
                if (!checkMatch()) {
                    e.preventDefault();
                    (window.smToast || alert)('New passwords do not match', 'error');
                    confirm.focus();
                    return;
                }
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('is-loading');
                    submitBtn.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin text-[11px]"></i><span>Saving...</span>';
                }
            });
        })();
    </script>
</body>

</html>
