<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — SmartAC</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Cormorant+Garamond:ital,wght@1,500;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --bg-0:    #060912;
            --bg-1:    #0a0f1c;
            --panel:   #0e1424;
            --panel-2: #131a2e;
            --line:    rgba(255, 255, 255, 0.08);
            --line-2:  rgba(255, 255, 255, 0.14);
            --line-3:  rgba(255, 255, 255, 0.22);
            --ink-0:   #f4f6fb;
            --ink-1:   #d8def0;
            --ink-2:   #9aa3bd;
            --ink-3:   #6b7596;
            --ink-4:   #4a5273;
            --cyan:    #5ed0ff;
            --cyan-rgb: 94 208 255;
            --mint:    #6ee7b7;
            --lavender:#b4a3ff;
            --coral:   #fb7185;
            --amber:   #fbbf24;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body { height: 100%; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        body {
            color: var(--ink-0);
            -webkit-font-smoothing: antialiased;
            overflow: hidden;
            background:
                linear-gradient(rgba(6, 9, 18, 0.88), rgba(6, 9, 18, 0.94)),
                url('/images/wallpaper.jpeg') center/cover no-repeat fixed;
        }

        /* Glow accents */
        .glow-1 {
            position: fixed; top: -250px; left: -150px;
            width: 700px; height: 700px;
            background: radial-gradient(circle, rgb(var(--cyan-rgb) / 0.16) 0%, transparent 60%);
            filter: blur(50px);
            pointer-events: none;
            z-index: 0;
        }
        .glow-2 {
            position: fixed; bottom: -250px; right: -150px;
            width: 700px; height: 700px;
            background: radial-gradient(circle, rgb(var(--lavender-rgb) / 0.14) 0%, transparent 60%);
            filter: blur(50px);
            pointer-events: none;
            z-index: 0;
        }
        .grid-overlay {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,0.022) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.022) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: radial-gradient(circle at center, black, transparent 70%);
            -webkit-mask-image: radial-gradient(circle at center, black, transparent 70%);
        }

        .serif {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-weight: 600;
        }

        /* ===== Layout ===== */
        .page {
            position: relative; z-index: 2;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px;
            gap: 24px;
            align-items: center;
        }

        /* ===== Brand panel (left) ===== */
        .brand-panel {
            position: relative;
            padding: 56px 48px;
            border-radius: 28px;
            background: linear-gradient(160deg, var(--panel-2) 0%, var(--panel) 100%);
            border: 1px solid var(--line);
            box-shadow: 0 1px 0 rgba(255,255,255,0.06) inset, 0 20px 60px -20px rgba(0,0,0,0.7);
            overflow: hidden;
            min-height: 580px;
            display: flex; flex-direction: column;
        }
        .brand-panel::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--line-2), transparent);
        }
        .brand-panel::after {
            content: '';
            position: absolute;
            top: -120px; right: -120px;
            width: 380px; height: 380px;
            border-radius: 999px;
            background: radial-gradient(circle, rgb(var(--cyan-rgb) / 0.16) 0%, transparent 60%);
            filter: blur(20px);
            pointer-events: none;
        }

        .brand-top { display: flex; align-items: center; gap: 12px; position: relative; z-index: 2; }
        .brand-mark {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: conic-gradient(from 220deg, #5ed0ff, var(--lavender), var(--coral), var(--amber), var(--mint), #5ed0ff);
            display: inline-flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 24px -8px rgb(var(--cyan-rgb) / 0.4);
        }
        .brand-mark .mark-inner {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: var(--panel);
            display: inline-flex; align-items: center; justify-content: center;
            color: var(--cyan);
            font-size: 16px;
        }
        .brand-name { font-size: 16px; font-weight: 700; line-height: 1.1; letter-spacing: 0em; }
        .brand-tag { font-size: 10px; font-weight: 700; letter-spacing: 0.16em; color: var(--ink-3); margin-top: 4px; text-transform: uppercase; }

        .brand-hero {
            margin-top: 60px;
            font-size: clamp(36px, 4.5vw, 52px);
            line-height: 1;
            letter-spacing: -0.03em;
            font-weight: 800;
            position: relative; z-index: 2;
        }
        .brand-hero .accent {
            background: linear-gradient(135deg, #5ed0ff, var(--lavender), #f0abfc);
            -webkit-background-clip: text; background-clip: text;
            color: transparent;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-weight: 600;
        }

        .brand-sub {
            margin-top: 18px;
            font-size: 14px;
            line-height: 1.6;
            color: var(--ink-2);
            max-width: 420px;
            position: relative; z-index: 2;
        }

        .brand-features {
            margin-top: auto;
            padding-top: 36px;
            display: flex; flex-direction: column; gap: 14px;
            position: relative; z-index: 2;
        }
        .feat-row { display: flex; align-items: center; gap: 12px; }
        .feat-ic {
            width: 30px; height: 30px;
            border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }
        .feat-ic.cyan     { background: rgb(var(--cyan-rgb) / 0.14);  border: 1px solid rgb(var(--cyan-rgb) / 0.32);  color: var(--cyan);     box-shadow: 0 0 14px -3px rgb(var(--cyan-rgb) / 0.28); }
        .feat-ic.mint     { background: rgb(var(--mint-rgb) / 0.14); border: 1px solid rgb(var(--mint-rgb) / 0.32); color: var(--mint);     box-shadow: 0 0 14px -3px rgb(var(--mint-rgb) / 0.28); }
        .feat-ic.lavender { background: rgb(var(--lavender-rgb) / 0.14); border: 1px solid rgb(var(--lavender-rgb) / 0.32); color: var(--lavender); box-shadow: 0 0 14px -3px rgb(var(--lavender-rgb) / 0.28); }
        .feat-text { font-size: 13px; color: var(--ink-1); font-weight: 500; }

        /* ===== Form panel (right) ===== */
        .form-panel {
            position: relative;
            padding: 48px 44px 36px;
            border-radius: 28px;
            background: linear-gradient(160deg, var(--panel) 0%, var(--bg-1) 100%);
            border: 1px solid var(--line);
            box-shadow: 0 1px 0 rgba(255,255,255,0.06) inset, 0 20px 60px -20px rgba(0,0,0,0.8);
            min-height: 580px;
            display: flex; flex-direction: column;
        }
        .form-panel::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--line-2), transparent);
        }

        .back-link {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 12px; color: var(--ink-3);
            text-decoration: none; font-weight: 500;
            margin-bottom: 32px;
            transition: color 0.2s var(--ease);
        }
        .back-link:hover { color: var(--ink-1); }

        .form-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 11px; font-weight: 700; letter-spacing: 0.16em;
            color: var(--cyan); text-transform: uppercase;
        }
        .form-eyebrow::before {
            content: ''; width: 18px; height: 1px;
            background: linear-gradient(90deg, transparent, var(--cyan));
        }

        .form-title {
            margin-top: 14px;
            font-size: 32px; font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.1;
        }
        .form-title .accent {
            background: linear-gradient(135deg, #5ed0ff, var(--lavender));
            -webkit-background-clip: text; background-clip: text;
            color: transparent;
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-weight: 600;
        }

        .form-sub {
            margin-top: 10px;
            font-size: 14px; color: var(--ink-2);
            line-height: 1.6;
        }

        /* Alert */
        .alert {
            margin-top: 24px;
            padding: 12px 14px;
            border-radius: 12px;
            background: rgb(var(--coral-rgb) / 0.10);
            border: 1px solid rgb(var(--coral-rgb) / 0.32);
            color: #ffc6cf;
            font-size: 13px;
            display: flex; align-items: flex-start; gap: 10px;
            font-weight: 500;
        }
        .alert i { color: var(--coral); margin-top: 2px; }

        /* Form */
        .form { margin-top: 26px; display: flex; flex-direction: column; gap: 16px; }
        .field-label {
            display: flex; align-items: center; justify-content: space-between;
            font-size: 11px; font-weight: 700; letter-spacing: 0.12em;
            color: var(--ink-3);
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .field-label .help {
            font-size: 10px; color: var(--ink-4); font-weight: 500; letter-spacing: 0.05em; text-transform: none;
        }
        .input-wrap {
            position: relative;
            display: flex; align-items: center;
            background: rgba(255,255,255,0.025);
            border: 1px solid var(--line);
            border-radius: 12px;
            transition: all 0.2s var(--ease);
        }
        .input-wrap:focus-within {
            border-color: rgb(var(--cyan-rgb) / 0.45);
            background: rgb(var(--cyan-rgb) / 0.04);
            box-shadow: 0 0 0 4px rgb(var(--cyan-rgb) / 0.10);
        }
        .input-wrap .leading {
            padding: 0 12px 0 14px;
            color: var(--ink-3);
            font-size: 13px;
            display: inline-flex; align-items: center; justify-content: center;
            transition: color 0.2s var(--ease);
        }
        .input-wrap:focus-within .leading { color: var(--cyan); }

        .input-wrap input {
            flex: 1;
            min-width: 0;
            background: transparent; border: none; outline: none;
            color: var(--ink-0);
            font-family: inherit;
            font-size: 14px; font-weight: 500;
            padding: 14px 0;
        }
        .input-wrap input::placeholder { color: var(--ink-4); }
        .input-wrap .trailing {
            background: transparent; border: none; cursor: pointer;
            color: var(--ink-3);
            padding: 0 14px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 13px;
        }
        .input-wrap .trailing:hover { color: var(--ink-1); }

        /* Live validation checkmark — appears when field is valid & filled */
        .input-wrap .valid-check {
            display: inline-flex; align-items: center; justify-content: center;
            width: 22px; height: 22px;
            border-radius: 999px;
            background: rgb(var(--mint-rgb) / 0.16);
            border: 1px solid rgb(var(--mint-rgb) / 0.36);
            color: var(--mint);
            font-size: 10px;
            margin-right: 10px;
            opacity: 0;
            transform: scale(0.7);
            transition: opacity 0.2s var(--ease), transform 0.2s var(--ease);
            pointer-events: none;
        }
        .input-wrap input:valid:not(:placeholder-shown) ~ .valid-check {
            opacity: 1;
            transform: scale(1);
        }

        /* CapsLock warning */
        .caps-warn {
            margin-top: 8px;
            display: none;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--amber);
        }
        .caps-warn i { font-size: 10px; }
        .caps-warn.visible { display: inline-flex; }

        .submit-btn {
            margin-top: 8px;
            width: 100%;
            padding: 14px 22px;
            border: none;
            border-radius: 12px;
            background: var(--ink-0);
            color: #0a0e1c;
            font-family: inherit;
            font-size: 14px; font-weight: 700;
            cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center;
            gap: 10px;
            transition: all 0.2s var(--ease);
            box-shadow: 0 10px 30px -10px rgba(244,246,251,0.30);
        }
        .submit-btn:hover { transform: translateY(-1px); box-shadow: 0 14px 36px -10px rgba(244,246,251,0.42); }
        .submit-btn:active { transform: translateY(0); }
        .submit-btn:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
        .submit-btn.is-loading {
            color: transparent;
            position: relative;
            pointer-events: none;
        }
        .submit-btn.is-loading::after {
            content: '';
            position: absolute;
            width: 16px; height: 16px;
            border: 2px solid rgba(10,14,28,0.30);
            border-top-color: #0a0e1c;
            border-radius: 999px;
            animation: spin 0.7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Footer */
        .form-footer {
            margin-top: auto; padding-top: 28px;
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; flex-wrap: wrap;
            border-top: 1px solid var(--line);
            margin-left: -44px; margin-right: -44px;
            padding-left: 44px; padding-right: 44px;
            padding-top: 22px;
        }
        .secure-badge {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 11px; color: var(--ink-3); font-weight: 500;
        }
        .secure-badge i {
            color: var(--mint);
            background: rgb(var(--mint-rgb) / 0.10);
            width: 22px; height: 22px;
            border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid rgb(var(--mint-rgb) / 0.28);
            font-size: 10px;
        }
        .copyright { font-size: 11px; color: var(--ink-4); }

        /* Hide scrollbars completely — all browsers including mobile */
        html, body, * {
            scrollbar-width: none !important;
            scrollbar-color: transparent transparent !important;
            -ms-overflow-style: none !important;
        }
        html::-webkit-scrollbar,
        body::-webkit-scrollbar,
        *::-webkit-scrollbar,
        *::-webkit-scrollbar-track,
        *::-webkit-scrollbar-thumb,
        *::-webkit-scrollbar-corner,
        *::-webkit-scrollbar-button {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
            background: transparent !important;
            -webkit-appearance: none !important;
            appearance: none !important;
        }

        /* ===== Responsive ===== */
        /* Tablet & smaller laptop (≤ 1024 px) — hide brand panel, center form */
        @media (max-width: 1024px) {
            html, body { height: 100%; }
            body { overflow-y: auto; overflow-x: hidden; }
            .page {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
                gap: 0;
                min-height: 100vh;
                max-width: 100%;
                margin: 0 auto;
            }
            .brand-panel { display: none; }
            .form-panel {
                padding: 40px 36px 28px;
                min-height: 0;
                width: 100%;
                max-width: 480px;
                border-radius: 28px;
            }
            .form-footer { margin-left: -36px; margin-right: -36px; padding-left: 36px; padding-right: 36px; }
        }

        /* Mobile L (≤ 480 px) — hide brand panel, center the form */
        @media (max-width: 480px) {
            html, body { height: 100%; }
            body { overflow-y: auto; }
            .page {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px;
                gap: 0;
                max-width: 100%;
                min-height: 100vh;
                margin: 0 auto;
            }
            .brand-panel { display: none; }
            .form-panel {
                padding: 28px 22px 22px;
                border-radius: 20px;
                width: 100%;
                max-width: 400px;
                margin: 0;
            }
            .form-title { font-size: 24px; margin-top: 12px; }
            .form-sub { font-size: 13px; margin-top: 8px; }
            .form { margin-top: 22px; gap: 14px; }
            .form-footer { margin-left: -22px; margin-right: -22px; padding-left: 22px; padding-right: 22px; }
            .back-link { margin-bottom: 22px; font-size: 12px; }
            .input-wrap input { padding: 12px 0; font-size: 14px; }
            .alert { font-size: 12px; padding: 10px 12px; }
        }

        /* Mobile S (≤ 480 px) — very tight, form-only */
        @media (max-width: 480px) {
            .page { padding: 12px; }
            .form-panel { padding: 22px 16px 18px; border-radius: 20px; }
            .form-eyebrow { font-size: 10px; letter-spacing: 0.12em; }
            .form-title { font-size: 20px; margin-top: 10px; }
            .form-sub { font-size: 12px; margin-top: 6px; }
            .form { margin-top: 18px; gap: 12px; }
            .form-footer { margin-left: -16px; margin-right: -16px; padding-left: 16px; padding-right: 16px; }
            .input-wrap { border-radius: 10px; }
            .input-wrap input { font-size: 13px; padding: 11px 0; }
            .input-wrap .leading { padding: 0 10px 0 12px; font-size: 12px; }
            .input-wrap .trailing { padding: 0 12px; font-size: 12px; }
            .field-label { font-size: 10px; letter-spacing: 0.12em; margin-bottom: 6px; }
            .alert { font-size: 12px; padding: 9px 11px; margin-top: 18px; gap: 8px; }
            .back-link { margin-bottom: 18px; font-size: 11px; }
        }

        /* Landscape mobile — make body scrollable so form is reachable */
        @media (max-height: 600px) and (orientation: landscape) {
            html, body { height: auto; }
            body { overflow: auto; }
            .page {
                min-height: 100vh;
                padding: 16px;
                grid-template-columns: 1fr 1fr;
                gap: 16px;
                align-items: stretch;
            }
            .brand-panel, .form-panel { min-height: 0; padding: 26px 26px 22px; }
            .brand-hero { margin-top: 22px; font-size: 28px; }
            .brand-features { padding-top: 22px; gap: 10px; }
            .form-panel { padding-top: 22px; }
            .back-link { margin-bottom: 18px; }
        }

        /* Touch devices — ensure tap targets ≥44px */
        @media (hover: none) and (pointer: coarse) {
            .submit-btn { min-height: 48px; }
            .input-wrap input { padding: 16px 0; }
            .input-wrap .trailing { min-width: 44px; min-height: 44px; }
        }

        /* Respect prefers-reduced-motion */
        @media (prefers-reduced-motion: reduce) {
            * { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; }
        }
    </style>
</head>
<body>
    <div class="glow-1"></div>
    <div class="glow-2"></div>
    <div class="grid-overlay"></div>

    <main class="page">

        {{-- BRAND PANEL (LEFT) --}}
        <aside class="brand-panel">
            <div class="brand-top">
                <div class="brand-mark">
                    <div class="mark-inner"><i class="fa-solid fa-snowflake"></i></div>
                </div>
                <div>
                    <div class="brand-name">Control AC</div>
                    <div class="brand-tag">Cooling, Reimagined</div>
                </div>
            </div>

            <h1 class="brand-hero">
                Your gateway to<br>
                <span class="accent">control</span> your<br>
                server rooms.
            </h1>

            <p class="brand-sub">
                Monitor temperature, schedule AC, and handle anomalies — all in one lightweight, responsive dashboard.
            </p>

            <div class="brand-features">
                <div class="feat-row">
                    <span class="feat-ic cyan"><i class="fa-solid fa-bolt"></i></span>
                    <span class="feat-text">Sub-second realtime via MQTT + WebSocket</span>
                </div>
                <div class="feat-row">
                    <span class="feat-ic lavender"><i class="fa-solid fa-shield-halved"></i></span>
                    <span class="feat-text">Role-based access: Admin, Operator, User</span>
                </div>
                <div class="feat-row">
                    <span class="feat-ic mint"><i class="fa-solid fa-clock"></i></span>
                    <span class="feat-text">Automatic scheduling with ±30 second tolerance</span>
                </div>
            </div>
        </aside>

        {{-- FORM PANEL (RIGHT) --}}
        <section class="form-panel">
            <a href="/" class="back-link">
                <i class="fa-solid fa-arrow-left" style="font-size:10px;"></i>
                Back to home
            </a>

            <p class="form-eyebrow">Sign In</p>
            <h2 class="form-title">Welcome <span class="accent">back.</span></h2>
            <p class="form-sub">Sign in with the account created by the administrator to continue to the dashboard.</p>

            @if (session('error'))
                <div class="alert" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="/login" id="loginForm" class="form">
                @csrf

                <div class="field">
                    <label class="field-label" for="username">
                        <span>Username</span>
                        <span class="help">3–20 characters</span>
                    </label>
                    <div class="input-wrap" id="usernameWrap">
                        <span class="leading"><i class="fa-regular fa-user"></i></span>
                        <input id="username" type="text" name="name"
                               required autofocus autocomplete="username"
                               minlength="3" maxlength="20"
                               pattern="[A-Za-z][A-Za-z0-9_]{2,19}"
                               title="Username 3–20 characters, letters/numbers/underscore, must start with a letter"
                               placeholder="e.g. Admin"
                               value="{{ old('name') }}">
                        <span class="valid-check" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                    </div>
                </div>

                <div class="field">
                    <label class="field-label" for="password">
                        <span>Password</span>
                    </label>
                    <div class="input-wrap" id="passwordWrap">
                        <span class="leading"><i class="fa-solid fa-lock"></i></span>
                        <input id="password" type="password" name="password"
                               required autocomplete="current-password"
                               placeholder="••••••••">
                        <span class="valid-check" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                        <button type="button" class="trailing" onclick="togglePassword()" aria-label="Show password">
                            <i id="toggleIcon" class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                    <p class="caps-warn" id="capsWarn">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        CapsLock is on
                    </p>
                </div>

                <button type="submit" class="submit-btn" id="loginBtn">
                    <span>Sign In</span>
                    <i class="fa-solid fa-arrow-right" style="font-size:11px;"></i>
                </button>
            </form>

            <div class="form-footer">
                <span class="secure-badge">
                    <i class="fa-solid fa-lock"></i>
                    Encrypted connection
                </span>
                <span class="copyright">© {{ date('Y') }} Control AC</span>
            </div>
        </section>

    </main>

    <script>
        function togglePassword() {
            const pw = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (pw.type === 'password') {
                pw.type = 'text';
                icon.className = 'fa-regular fa-eye-slash';
            } else {
                pw.type = 'password';
                icon.className = 'fa-regular fa-eye';
            }
        }

        document.getElementById('loginForm')?.addEventListener('submit', () => {
            document.getElementById('loginBtn').classList.add('is-loading');
        });

        // CapsLock detection on password field
        const pwInput = document.getElementById('password');
        const capsWarn = document.getElementById('capsWarn');
        function checkCaps(e) {
            if (!capsWarn) return;
            const on = typeof e.getModifierState === 'function' && e.getModifierState('CapsLock');
            capsWarn.classList.toggle('visible', !!on);
        }
        if (pwInput) {
            pwInput.addEventListener('keydown', checkCaps);
            pwInput.addEventListener('keyup', checkCaps);
            pwInput.addEventListener('blur', () => capsWarn?.classList.remove('visible'));
        }
    </script>
</body>
</html>
