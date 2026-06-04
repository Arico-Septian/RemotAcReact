<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — SmartAC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --cyan: #5a93ec;
            --cyan-d: #335fc2;
            --cyan-rgb: 90 147 236;
            --cyan-d-rgb: 51 95 194;
            --mint: #6ee7b7;
            --mint-rgb: 110 231 183;
            --lavender: #b4a3ff;
            --lavender-rgb: 180 163 255;
            --coral: #fb7185;
            --coral-rgb: 251 113 133;
            --amber: #fbbf24;
            --ink-0: #ffffff;
            --ink-1: #ffffff;
            --ink-2: #ffffff;
            --ink-3: #ffffff;
            --ink-4: #ffffff;
            --line: rgba(255, 255, 255, .08);
            --line-soft: rgba(255, 255, 255, .05);
            --r-sm: 8px;
            --r-md: 10px;
            --r-lg: 12px;
            --r-xl: 16px;
            --r-2xl: 20px;
            --r-3xl: 28px;
        }
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
            font-family: 'Inter', system-ui, sans-serif;
        }

        body {
            color: var(--ink-0);
            -webkit-font-smoothing: antialiased;
            background:
                radial-gradient(800px 400px at 50% -20%, rgba(78, 215, 255, .08), transparent 60%),
                radial-gradient(600px 350px at 85% 110%, rgba(139, 162, 255, .04), transparent 65%),
                linear-gradient(135deg,
                    rgba(4, 7, 16, .86) 0%, rgba(6, 11, 28, .84) 25%,
                    rgba(7, 14, 34, .83) 50%, rgba(5, 10, 26, .84) 75%,
                    rgba(4, 7, 20, .86) 100%),
                url('/images/wallpaper.jpeg') center/cover no-repeat fixed;
            background-blend-mode: multiply;
        }

        /* ── Layout ── */
        .page {
            display: grid;
            grid-template-columns: 1fr 1fr;
            height: 100vh;
        }

        /* ── Left: Brand ── */
        .brand {
            display: flex;
            flex-direction: column;
            padding: 52px 60px 52px 48px;
            position: relative;
            overflow: hidden;
        }

        .brand-inner {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 40px;
            position: relative;
            z-index: 1;
        }

        .brand::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(255, 255, 255, .04) 1px, transparent 1px);
            background-size: 32px 32px;
            -webkit-mask-image: radial-gradient(ellipse 80% 80% at 30% 40%, black 0%, transparent 70%);
            mask-image: radial-gradient(ellipse 80% 80% at 30% 40%, black 0%, transparent 70%);
            pointer-events: none;
        }

        .brand::after {
            content: '';
            position: absolute;
            top: -100px;
            left: -100px;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgb(var(--cyan-d-rgb)/.08) 0%, transparent 65%);
            pointer-events: none;
        }

        /* Logo */
        .b-logo {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .b-mark {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: var(--r-lg);
            background: linear-gradient(135deg, #5a93ec, #335fc2);
            box-shadow: 0 8px 28px -4px rgb(var(--cyan-d-rgb)/.5), inset 0 1px 0 rgba(255, 255, 255, .28);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .b-mark::after {
            content: '';
            position: absolute;
            inset: 2px;
            border-radius: 9px;
            background: rgba(7, 12, 30, .96);
        }

        .b-mark i {
            position: relative;
            z-index: 2;
            font-size: 14px;
            color: var(--cyan-d);
            filter: drop-shadow(0 0 8px rgb(var(--cyan-d-rgb)/.7));
        }

        .b-name {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: -.02em;
            display: block;
        }

        .b-tag {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .10em;
            text-transform: uppercase;
            color: var(--ink-3);
            display: block;
            margin-top: 2px;
        }

        /* Center content */
        .b-content {}

        .b-headline {
            font-size: clamp(36px, 4.2vw, 54px);
            font-weight: 900;
            letter-spacing: -.05em;
            line-height: .95;
        }

        .b-headline .plain {
            color: var(--ink-0);
            display: block;
        }

        .b-headline .grad {
            display: block;
            background: linear-gradient(115deg, #6ea8ff 0%, #5a93ec 50%, #335fc2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .b-desc {
            margin-top: 16px;
            font-size: 14px;
            color: var(--ink-2);
            line-height: 1.75;
            max-width: 340px;
        }

        .b-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 20px;
            align-self: flex-start;
            padding: 5px 14px 5px 8px;
            border-radius: 999px;
            background: rgb(var(--mint-rgb)/.08);
            border: 1px solid rgb(var(--mint-rgb)/.22);
            font-size: 11.5px;
            font-weight: 600;
            color: var(--mint);
        }

        .s-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--mint);
            box-shadow: 0 0 8px var(--mint);
            animation: blink 2s ease-in-out infinite;
        }


        /* Features */
        .b-feats {
            padding-top: 24px;
            border-top: 1px solid var(--line-soft);
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .feat {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .feat-ic {
            width: 34px;
            height: 34px;
            border-radius: var(--r-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }

        .feat-ic.c {
            background: rgb(var(--cyan-d-rgb)/.10);
            border: 1px solid rgb(var(--cyan-d-rgb)/.22);
            color: var(--cyan-d);
        }

        .feat-ic.m {
            background: rgb(var(--mint-rgb)/.10);
            border: 1px solid rgb(var(--mint-rgb)/.22);
            color: var(--mint);
        }

        .feat-ic.l {
            background: rgb(var(--lavender-rgb)/.10);
            border: 1px solid rgb(var(--lavender-rgb)/.22);
            color: var(--lavender);
        }

        .feat-text {
            font-size: 13px;
            color: var(--ink-1);
            font-weight: 500;
        }

        /* Vertical divider */
        .divider {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: 1px;
            background: linear-gradient(180deg, transparent, var(--line) 15%, var(--line) 85%, transparent);
            pointer-events: none;
            z-index: 10;
        }

        /* ── Right: Form ── */
        .form-side {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding: 52px 48px 52px 60px;
            position: relative;
            overflow-y: auto;
            min-height: 0;
        }

        .form-side::before {
            content: '';
            position: absolute;
            top: -120px;
            right: -60px;
            width: 480px;
            height: 480px;
            border-radius: 50%;
            background: radial-gradient(circle, rgb(var(--cyan-d-rgb)/.06) 0%, transparent 60%);
            pointer-events: none;
        }

        .form-side::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -40px;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            background: radial-gradient(circle, rgb(var(--lavender-rgb)/.05) 0%, transparent 65%);
            pointer-events: none;
        }


        /* Form body */
        .form-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 24px 0;
            min-height: 0;
            position: relative;
            z-index: 1;
        }

        /* Glass form card */
        .form-card {
            padding: 36px 38px;
            border-radius: var(--r-3xl);
            background: rgba(10, 18, 40, .65);
            border: 1px solid rgba(255, 255, 255, .1);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            position: relative;
            overflow: hidden;
            box-shadow:
                0 32px 80px rgba(0, 0, 0, .35),
                0 0 0 1px rgba(255, 255, 255, .04),
                inset 0 1px 0 rgba(255, 255, 255, .08);
            z-index: 1;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg,
                    transparent 0%,
                    rgb(var(--cyan-d-rgb)/.8) 25%,
                    rgb(var(--lavender-rgb)/.6) 65%,
                    transparent 100%);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .14em;
            color: var(--cyan-d);
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: '';
            width: 16px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgb(var(--cyan-d-rgb)/.7));
        }

        .form-title {
            margin-top: 12px;
            font-size: 34px;
            font-weight: 800;
            letter-spacing: -.04em;
            line-height: 1.1;
        }

        .form-title .hi {
            background: linear-gradient(120deg, #6ea8ff, #335fc2);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .form-desc {
            margin-top: 8px;
            font-size: 14px;
            color: var(--ink-2);
            line-height: 1.65;
        }

        /* Alert */
        .alert {
            margin-top: 18px;
            padding: 12px 14px;
            border-radius: var(--r-lg);
            background: rgb(var(--coral-rgb)/.10);
            border: 1px solid rgb(var(--coral-rgb)/.28);
            color: #ffc6cf;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert i {
            color: var(--coral);
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* Inline per-field validation */
        .field-err {
            display: none;
            align-items: center;
            gap: 6px;
            margin-top: 7px;
            font-size: 11.5px;
            font-weight: 500;
            color: #fb7185;
            line-height: 1.3;
        }

        .field-err.show {
            display: flex;
        }

        .field-err i {
            font-size: 10px;
            flex-shrink: 0;
        }

        .input-wrap.invalid {
            border-color: rgba(251, 113, 133, .55);
        }

        .input-wrap.invalid:focus-within {
            border-color: #fb7185;
        }

        .input-wrap.invalid .input-ic {
            color: #fb7185;
        }

        /* Fields */
        .fields {
            margin-top: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .field-lbl {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .10em;
            color: var(--ink-3);
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .field-lbl .hint {
            font-size: 10px;
            color: var(--ink-4);
            font-weight: 500;
            letter-spacing: .04em;
            text-transform: none;
        }

        .input-wrap {
            display: flex;
            align-items: center;
            box-sizing: border-box;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: var(--r-lg);
            transition: border-color .18s, background .18s, box-shadow .18s;
        }

        .input-wrap:focus-within {
            border-color: #ffffff;
            background: rgba(255, 255, 255, .05);
            box-shadow: none;
        }

        .input-ic {
            padding: 0 12px 0 16px;
            color: var(--ink-3);
            font-size: 13px;
            display: flex;
            align-items: center;
            transition: color .18s;
        }

        .input-wrap:focus-within .input-ic {
            color: #ffffff;
        }

        .input-wrap input {
            flex: 1;
            min-width: 0;
            background: transparent;
            border: none;
            outline: none;
            color: var(--ink-0);
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            padding: 14px 0;
        }

        .input-wrap input::placeholder {
            color: #64748b;
        }

        .input-wrap input::-ms-reveal,
        .input-wrap input::-ms-clear {
            display: none;
        }

        .input-btn {
            background: transparent;
            border: none;
            cursor: pointer;
            color: var(--ink-3);
            padding: 0 14px;
            display: flex;
            align-items: center;
            font-size: 13px;
            transition: color .18s;
        }

        .input-btn:hover {
            color: var(--ink-1);
        }

        /* Caps */
        .caps-warn {
            margin-top: 6px;
            display: none;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--amber);
        }

        .caps-warn.on {
            display: flex;
        }

        /* Submit */
        .btn-submit {
            width: 100%;
            margin-top: 8px;
            padding: 14px 22px;
            border: none;
            border-radius: var(--r-lg);
            background: var(--ink-0);
            color: #07101f;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: -.01em;
            cursor: pointer;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: none;
            transition: filter .15s, box-shadow .15s;
        }

        .btn-submit:hover:not(:disabled) {
            filter: none;
            transform: none;
            box-shadow: none;
        }

        .btn-submit:active:not(:disabled) {
            transform: none;
        }

        .btn-submit:disabled {
            opacity: .7;
            cursor: not-allowed;
        }

        .btn-submit.loading {
            color: transparent;
            pointer-events: none;
        }

        .btn-submit.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(7, 16, 31, .25);
            border-top-color: #07101f;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        /* Admin note */
        .form-note {
            margin-top: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 12px;
            color: #94a3b8;
            text-align: center;
        }

        .form-note i {
            font-size: 10px;
            color: #94a3b8;
        }

        /* Footer */
        .form-foot {
            padding-top: 20px;
            margin-top: 0;
            border-top: 1px solid var(--line-soft);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .secure-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 11px;
            color: var(--ink-3);
            font-weight: 500;
        }

        .secure-pill i {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgb(var(--mint-rgb)/.10);
            border: 1px solid rgb(var(--mint-rgb)/.25);
            color: var(--mint);
            font-size: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .copy {
            font-size: 11px;
            color: var(--ink-4);
        }

        /* Brand summary — mobile/tablet only */
        .brand-summary {
            display: none;
        }

        .bs-logo {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 20px;
        }

        .bs-headline {
            font-size: clamp(22px, 5vw, 34px);
            font-weight: 900;
            letter-spacing: -.04em;
            line-height: 1.0;
            margin-bottom: 12px;
        }

        .bs-desc {
            font-size: 13.5px;
            color: var(--ink-2);
            line-height: 1.68;
        }

        .bs-feats {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid var(--line-soft);
            display: flex;
            flex-direction: column;
            gap: 11px;
        }

        /* Animations */
        @keyframes blink {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: .2
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ── Responsive ── */
        @media (max-width: 1024px) {

            html,
            body {
                height: auto;
                overflow-y: auto;
                overflow-x: hidden;
            }

            .page {
                display: flex;
                align-items: flex-start;
                justify-content: center;
                height: auto;
                min-height: 100vh;
                padding: 32px 24px;
            }

            .divider {
                display: none;
            }

            .brand {
                display: none;
            }

            .form-side {
                width: 100%;
                max-width: 520px;
                height: auto;
                padding: 36px 28px 32px;
                justify-content: flex-start;
                background: none;
                border: none;
                border-radius: 0;
                -webkit-backdrop-filter: none;
                backdrop-filter: none;
                box-shadow: none;
            }

            .form-side::before {
                display: none;
            }

            .brand-summary {
                display: block;
                padding-bottom: 28px;
                margin-bottom: 8px;
                border-bottom: 1px solid var(--line-soft);
            }

            .form-body {
                flex: none;
                justify-content: flex-start;
                padding: 0;
            }

            .form-card {
                padding: 28px 26px;
            }

            .fields {
                margin-top: 22px;
            }

            .form-foot {
                margin-top: 22px;
            }
        }

        @media (max-width: 480px) {
            .page {
                padding: 24px 20px;
            }

            .form-side {
                padding: 24px 0 28px;
                max-width: 100%;
            }

            .form-title {
                font-size: 22px;
            }

            .bs-headline {
                font-size: 21px;
            }

            .bs-desc {
                font-size: 13px;
            }

            .fields {
                gap: 13px;
                margin-top: 18px;
            }

            .form-foot {
                margin-top: 18px;
            }

            .feat-text {
                font-size: 12.5px;
            }

            .bs-feats {
                gap: 10px;
            }

            .brand-summary {
                padding-bottom: 22px;
                margin-bottom: 6px;
            }

            .form-card {
                padding: 22px 18px;
            }
        }

        @media (max-width: 360px) {
            .page {
                padding: 20px 16px;
            }

            .form-side {
                padding: 20px 0 24px;
            }

            .form-title {
                font-size: 20px;
            }

            .bs-headline {
                font-size: 19px;
            }

            .feat-text {
                font-size: 12px;
            }

            .input-wrap input {
                padding: 12px 0;
                font-size: 13px;
            }

            .btn-submit {
                font-size: 13px;
                padding: 12px;
            }

            .form-card {
                padding: 18px 14px;
            }
        }

        @media (max-height: 500px) and (orientation: landscape) {

            html,
            body {
                height: auto;
                overflow-y: auto;
            }

            .page {
                padding: 16px 20px;
            }

            .form-side {
                padding: 20px 24px 18px;
            }

            .bs-headline,
            .bs-desc,
            .bs-feats {
                display: none;
            }

            .brand-summary {
                padding-bottom: 16px;
                margin-bottom: 4px;
            }

            .form-title {
                font-size: 20px;
            }

            .form-card {
                padding: 20px 22px;
            }

            .fields {
                margin-top: 14px;
                gap: 10px;
            }

            .form-foot {
                margin-top: 14px;
            }

            .form-note {
                display: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: .01ms !important;
                transition-duration: .01ms !important;
            }
        }

        * {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        *::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>

<body>
    <main class="page">
        {{-- ── BRAND (left) ── --}}
        <aside class="brand">
            <div class="brand-inner">
                <div class="b-logo">
                    <div class="b-mark"><i class="fa-solid fa-snowflake"></i></div>
                    <div>
                        <span class="b-name">SmartAC</span>
                        <span class="b-tag">IoT Control System</span>
                    </div>
                </div>
                <div class="b-content">
                    <h1 class="b-headline">
                        <span class="plain">Intelligent</span>
                        <span class="plain">climate control</span>
                        <span class="grad">for server rooms.</span>
                    </h1>
                    <p class="b-desc">
                        Monitor temperatures, control AC units remotely, and automate climate responses — all from one
                        real-time dashboard.
                    </p>
                    <div class="b-status">
                        <span class="s-dot"></span>
                        System online &amp; monitoring
                    </div>
                </div>
                <div class="b-feats">
                    <div class="feat">
                        <span class="feat-ic c"><i class="fa-solid fa-bolt"></i></span>
                        <span class="feat-text">Real-time updates via MQTT + WebSocket</span>
                    </div>
                    <div class="feat">
                        <span class="feat-ic m"><i class="fa-solid fa-brain"></i></span>
                        <span class="feat-text">Fuzzy logic auto-adjusts AC setpoints</span>
                    </div>
                    <div class="feat">
                        <span class="feat-ic l"><i class="fa-solid fa-shield-halved"></i></span>
                        <span class="feat-text">Role-based access: Admin, Operator, User</span>
                    </div>
                </div>
            </div>
        </aside>
        <div class="divider"></div>
        {{-- ── FORM (right) ── --}}
        <section class="form-side">
            {{-- Brand summary — tablet/mobile only --}}
            <div class="brand-summary">
                <div class="bs-logo">
                    <div class="b-mark"><i class="fa-solid fa-snowflake"></i></div>
                    <div>
                        <span class="b-name">SmartAC</span>
                        <span class="b-tag">IoT Control System</span>
                    </div>
                </div>
                <h1 class="bs-headline">
                    <span class="plain">Intelligent climate control</span>
                    <span class="grad">for server rooms.</span>
                </h1>
                <p class="bs-desc">Monitor temperatures, control AC units remotely, and automate climate responses — all
                    from one real-time dashboard.</p>
                <div class="b-status">
                    <span class="s-dot"></span>
                    System online &amp; monitoring
                </div>
                <div class="bs-feats">
                    <div class="feat">
                        <span class="feat-ic c"><i class="fa-solid fa-bolt"></i></span>
                        <span class="feat-text">Real-time updates via MQTT + WebSocket</span>
                    </div>
                    <div class="feat">
                        <span class="feat-ic m"><i class="fa-solid fa-brain"></i></span>
                        <span class="feat-text">Fuzzy logic auto-adjusts AC setpoints</span>
                    </div>
                    <div class="feat">
                        <span class="feat-ic l"><i class="fa-solid fa-shield-halved"></i></span>
                        <span class="feat-text">Role-based access: Admin, Operator, User</span>
                    </div>
                </div>
            </div>
            {{-- Form body --}}
            <div class="form-body">
                <div class="form-card">
                    <p class="eyebrow">Sign In</p>
                    <h2 class="form-title">Welcome <span class="hi">back.</span></h2>
                    <p class="form-desc">Sign in with your account to access the SmartAC dashboard.</p>
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
                    <div class="fields">
                        <div>
                            <label class="field-lbl" for="username">
                                <span>Username</span>
                                <span class="hint">3 – 20 characters</span>
                            </label>
                            <div class="input-wrap">
                                <span class="input-ic"><i class="fa-regular fa-user"></i></span>
                                <input id="username" type="text" name="name" form="loginForm" required autofocus
                                    autocomplete="username" minlength="3" maxlength="20"
                                    placeholder="Enter your username" value="{{ old('name') }}">
                            </div>
                            <p class="field-err" id="errUsername"><i
                                    class="fa-solid fa-circle-exclamation"></i><span></span></p>
                        </div>
                        <div>
                            <label class="field-lbl" for="password">
                                <span>Password</span>
                            </label>
                            <div class="input-wrap">
                                <span class="input-ic"><i class="fa-solid fa-lock"></i></span>
                                <input id="password" type="password" name="password" form="loginForm" required
                                    autocomplete="current-password" placeholder="Enter your password">
                                <button type="button" class="input-btn" onclick="togglePw()"
                                    aria-label="Toggle password">
                                    <i id="pwIcon" class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <p class="caps-warn" id="capsWarn">
                                <i class="fa-solid fa-triangle-exclamation"></i> Caps Lock is on
                            </p>
                            <p class="field-err" id="errPassword"><i
                                    class="fa-solid fa-circle-exclamation"></i><span></span></p>
                        </div>
                    </div>
                    <form method="POST" action="/login" id="loginForm" novalidate style="margin-top:8px;">
                        @csrf
                        <button type="submit" class="btn-submit" id="loginBtn">
                            <span>Sign In</span>
                            <i class="fa-solid fa-arrow-right" style="font-size:11px;"></i>
                        </button>
                    </form>
                    <p class="form-note">
                        <i class="fa-solid fa-circle-info"></i>
                        Accounts are managed by your system administrator.
                    </p>
                </div>
            </div>
            {{-- Footer --}}
            <div class="form-foot">
                <span class="secure-pill">
                    <i class="fa-solid fa-lock"></i>
                    Encrypted connection
                </span>
                <span class="copy">© {{ date('Y') }} SmartAC</span>
            </div>
        </section>
    </main>
    <script>
        function togglePw() {
            const pw = document.getElementById('password');
            const ic = document.getElementById('pwIcon');
            const show = pw.type === 'password';
            pw.type = show ? 'text' : 'password';
            ic.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        }

        const pw = document.getElementById('password');
        const capEl = document.getElementById('capsWarn');

        function checkCaps(e) {
            capEl.classList.toggle('on', !!e.getModifierState?.('CapsLock'));
        }
        pw.addEventListener('keydown', checkCaps);
        pw.addEventListener('keyup', checkCaps);
        pw.addEventListener('blur', () => capEl.classList.remove('on'));

        // Eye toggle only visible when the password field has text
        const pwToggleBtn = document.getElementById('pwIcon').closest('.input-btn');

        function syncPwToggle() {
            const hasText = pw.value.length > 0;
            pwToggleBtn.style.display = hasText ? '' : 'none';
            if (!hasText && pw.type === 'text') {
                pw.type = 'password';
                document.getElementById('pwIcon').className = 'fa-solid fa-eye';
            }
        }
        pw.addEventListener('input', syncPwToggle);
        syncPwToggle();

        // ---- Inline per-field validation (format check, like major apps) ----
        const loginForm = document.getElementById('loginForm');
        const uname = document.getElementById('username');
        const errU = document.getElementById('errUsername');
        const errP = document.getElementById('errPassword');
        const wrapU = uname.closest('.input-wrap');
        const wrapP = pw.closest('.input-wrap');
        const nameRe = /^[A-Za-z][A-Za-z0-9_]{2,19}$/;

        function setFieldErr(wrap, errEl, msg) {
            if (msg) {
                wrap.classList.add('invalid');
                errEl.querySelector('span').textContent = msg;
                errEl.classList.add('show');
            } else {
                wrap.classList.remove('invalid');
                errEl.classList.remove('show');
            }
        }

        function validateUsername(force) {
            const v = uname.value.trim();
            let msg = '';
            if (!v) msg = 'Username is required.';
            else if (v.length < 3) msg = 'Username must be at least 3 characters.';
            else if (v.length > 20) msg = 'Username may not exceed 20 characters.';
            else if (!nameRe.test(v)) msg = 'Use letters, numbers, or underscore; start with a letter.';
            if (force || errU.classList.contains('show')) setFieldErr(wrapU, errU, msg);
            return !msg;
        }

        function validatePassword(force) {
            const msg = pw.value ? '' : 'Password is required.';
            if (force || errP.classList.contains('show')) setFieldErr(wrapP, errP, msg);
            return !msg;
        }

        uname.addEventListener('blur', () => validateUsername(true));
        pw.addEventListener('blur', () => validatePassword(true));
        uname.addEventListener('input', () => validateUsername(false));
        pw.addEventListener('input', () => validatePassword(false));

        loginForm.addEventListener('submit', function (e) {
            const okU = validateUsername(true);
            const okP = validatePassword(true);
            if (!okU || !okP) {
                e.preventDefault();
                (okU ? pw : uname).focus();
                return;
            }
            document.getElementById('loginBtn').classList.add('loading');
        });
    </script>
</body>

</html>
