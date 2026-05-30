<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control AC — Cooling, Reimagined</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Cormorant+Garamond:ital,wght@1,500;1,600;1,700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --bg-0:  #060912;
            --bg-1:  #0a0f1c;
            --panel:    #0e1424;
            --panel-2:  #131a2e;
            --panel-3:  #182238;
            --line:     rgba(255, 255, 255, 0.08);
            --line-2:   rgba(255, 255, 255, 0.14);
            --line-3:   rgba(255, 255, 255, 0.22);
            --ink-0: #f4f6fb;
            --ink-1: #d8def0;
            --ink-2: #9aa3bd;
            --ink-3: #6b7596;
            --ink-4: #4a5273;
            --cyan:     #5ed0ff;
            --cyan-rgb: 94 208 255;
            --mint:     #6ee7b7;
            --lavender: #b4a3ff;
            --coral:    #fb7185;
            --amber:    #fbbf24;
            --sky:      #38bdf8;
            --pink:     #f0abfc;
            --shadow-1: 0 1px 0 rgba(255,255,255,0.06) inset, 0 10px 30px -15px rgba(0,0,0,0.7);
            --shadow-2: 0 1px 0 rgba(255,255,255,0.08) inset, 0 20px 60px -20px rgba(0,0,0,0.8);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html { scroll-behavior: smooth; }
        body {
            font-family: var(--font-sans);
            color: var(--ink-0);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            min-height: 100vh;
            overflow-x: hidden;
            background:
                linear-gradient(rgba(6, 9, 18, 0.86), rgba(6, 9, 18, 0.92)),
                url('/images/wallpaper.jpeg') center/cover no-repeat fixed;
        }

        /* ===== Accent glow — localized, not full wash ===== */
        .glow-top {
            position: fixed; top: -300px; left: 50%; transform: translateX(-50%);
            width: 1200px; height: 700px;
            background: radial-gradient(ellipse at center, rgb(var(--cyan-rgb) / 0.18) 0%, transparent 55%);
            filter: blur(40px);
            z-index: -1;
            pointer-events: none;
        }
        .glow-right {
            position: fixed; top: 30%; right: -200px;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgb(var(--lavender-rgb) / 0.16) 0%, transparent 60%);
            filter: blur(60px);
            z-index: -1;
            pointer-events: none;
        }
        .grid-overlay {
            position: fixed; inset: 0; z-index: -1; pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 64px 64px;
            mask-image: radial-gradient(circle at center, black, transparent 75%);
            -webkit-mask-image: radial-gradient(circle at center, black, transparent 75%);
        }

        /* Cursor spotlight */
        .spotlight {
            position: fixed; pointer-events: none; z-index: 1;
            width: 500px; height: 500px;
            border-radius: var(--r-full);
            background: radial-gradient(circle, rgb(var(--cyan-rgb) / 0.06) 0%, transparent 60%);
            transform: translate(-50%, -50%);
            opacity: 0;
            mix-blend-mode: screen;
            transition: opacity 0.5s var(--ease);
        }
        body:hover .spotlight { opacity: 1; }

        /* ===== Typography ===== */
        .serif {
            font-family: var(--font-serif);
            font-style: italic;
            font-weight: 600;
        }
        @keyframes pulse {
            0%   { box-shadow: 0 0 0 0 rgb(var(--mint-rgb) / 0.6); }
            70%  { box-shadow: 0 0 0 9px rgb(var(--mint-rgb) / 0); }
            100% { box-shadow: 0 0 0 0 rgb(var(--mint-rgb) / 0); }
        }

        /* ===== Nav ===== */
        .nav {
            position: sticky; top: 0; z-index: 50;
            background: rgba(6, 9, 18, 0.55);
            backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid transparent;
            transition: background 0.3s var(--ease), border-color 0.3s var(--ease);
        }
        .nav.scrolled {
            background: rgba(6, 9, 18, 0.92);
            border-bottom-color: var(--line);
        }
        .nav-inner {
            max-width: 1320px; margin: 0 auto;
            padding: 18px 28px;
            display: flex; align-items: center; justify-content: space-between;
            gap: 24px;
        }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand-mark {
            width: 42px; height: 42px;
            border-radius: var(--r-lg);
            background: conic-gradient(from 220deg, #5ed0ff, var(--lavender), var(--coral), var(--amber), var(--mint), #5ed0ff);
            display: inline-flex; align-items: center; justify-content: center;
            position: relative;
            box-shadow: 0 8px 24px -8px rgb(var(--cyan-rgb) / 0.4);
        }
        .brand-mark .mark-inner {
            width: 34px; height: 34px;
            border-radius: var(--r-md);
            background: var(--panel);
            display: inline-flex; align-items: center; justify-content: center;
            color: var(--cyan);
            font-size: 14px;
        }
        .brand-name { font-size: 16px; font-weight: 700; letter-spacing: 0em; line-height: 1.1; color: var(--ink-0); white-space: nowrap; }
        .brand-tag { font-size: 10px; font-weight: 700; letter-spacing: 0.16em; color: var(--ink-3); margin-top: 4px; text-transform: uppercase; }

        .nav-links { display: flex; align-items: center; gap: 2px; }
        .nav-links a {
            color: var(--ink-1); text-decoration: none;
            font-size: 13px; font-weight: 500;
            padding: 8px 14px; border-radius: var(--r-md);
            transition: all var(--t-base);
        }
        .nav-links a:hover { color: var(--ink-0); background: rgba(255,255,255,0.04); }

        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .lang-pill {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 14px; border-radius: var(--r-md);
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--line);
            color: var(--ink-2);
            font-size: 12px; font-weight: 600;
            cursor: pointer;
        }
        .btn-pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 18px; border-radius: var(--r-md);
            background: var(--ink-0); color: #0a0e1c;
            font-size: 13px; font-weight: 700;
            text-decoration: none;
            transition: all var(--t-base);
            box-shadow: 0 8px 24px -8px rgba(244, 246, 251, 0.25);
        }
        .btn-pill:hover { transform: translateY(-1px); box-shadow: 0 12px 28px -8px rgba(244,246,251,0.4); }
        .menu-toggle { display: none; background: transparent; border: 1px solid var(--line); color: var(--ink-1); width: 38px; height: 38px; border-radius: var(--r-md); cursor: pointer; }

        /* ===== Section base ===== */
        .section { max-width: 1320px; margin: 0 auto; padding: 0 28px; position: relative; z-index: 2; }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 11px; font-weight: 700; letter-spacing: 0.16em;
            color: var(--cyan); text-transform: uppercase;
        }
        .eyebrow::before {
            content: ''; width: 22px; height: 1px;
            background: linear-gradient(90deg, transparent, var(--cyan));
        }

        /* ===== HERO ===== */
        .hero { padding: 72px 0 56px; position: relative; }
        .hero-grid {
            display: grid;
            grid-template-columns: 1.45fr 1fr;
            gap: 22px;
            align-items: stretch;
        }
        .hero-main {
            position: relative;
            padding: 52px 52px 44px;
            border-radius: var(--r-3xl);
            background: linear-gradient(160deg, var(--panel) 0%, var(--bg-1) 100%);
            border: 1px solid var(--line);
            box-shadow: var(--shadow-2);
            overflow: hidden;
            min-height: 580px;
            display: flex; flex-direction: column;
        }
        .hero-main::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--line-2), transparent);
        }
        .hero-main::after {
            content: '';
            position: absolute;
            top: -100px; right: -100px;
            width: 400px; height: 400px;
            border-radius: var(--r-full);
            background: radial-gradient(circle, rgb(var(--cyan-rgb) / 0.15) 0%, transparent 60%);
            filter: blur(20px);
            pointer-events: none;
        }
        .hero-main > * { position: relative; z-index: 2; }

        .hero-h1 {
            font-size: clamp(48px, 6vw, 86px);
            font-weight: 800;
            line-height: 1.0;
            letter-spacing: -0.04em;
        }
        .hero-h1 .accent {
            display: inline-block;
            background: linear-gradient(135deg, #5ed0ff 0%, var(--lavender) 50%, #f0abfc 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-family: var(--font-serif);
            font-style: italic;
            font-weight: 600;
            letter-spacing: -0.02em;
            padding: 0 2px;
        }

        .hero-sub {
            margin-top: 24px;
            max-width: 520px;
            font-size: 16px;
            line-height: 1.6;
            color: var(--ink-1);
        }

        .hero-cta { margin-top: 32px; display: flex; gap: 12px; flex-wrap: wrap; }
        .btn-cta-primary {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 14px 24px; border-radius: var(--r-lg);
            background: var(--ink-0); color: #0a0e1c;
            font-size: 14px; font-weight: 700;
            text-decoration: none;
            transition: all var(--t-base);
            box-shadow: 0 10px 30px -10px rgba(244,246,251,0.30);
        }
        .btn-cta-primary:hover { transform: translateY(-2px); box-shadow: 0 16px 40px -10px rgba(244,246,251,0.42); }

        .btn-cta-ghost {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 14px 22px; border-radius: var(--r-lg);
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--line-2);
            color: var(--ink-1);
            font-size: 14px; font-weight: 600;
            text-decoration: none;
            transition: all var(--t-base);
        }
        .btn-cta-ghost:hover { background: rgba(255,255,255,0.08); color: var(--ink-0); border-color: var(--line-3); }
        .play-icon {
            width: 22px; height: 22px; border-radius: var(--r-full);
            background: linear-gradient(135deg, var(--cyan), var(--lavender));
            color: #0a0e1c;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 10px;
        }

        .hero-trust {
            margin-top: auto; padding-top: 44px;
            display: flex; gap: 36px; flex-wrap: wrap;
            border-top: 1px solid var(--line);
            margin-left: -52px; margin-right: -52px;
            padding-left: 52px; padding-right: 52px;
        }
        .hero-trust .item { display: flex; flex-direction: column; gap: 4px; }
        .hero-trust .num {
            font-size: 24px; font-weight: 800; letter-spacing: -0.02em;
            font-feature-settings: 'tnum' 1, 'lnum' 1;
            color: var(--ink-0);
        }
        .hero-trust .lbl {
            font-size: 10px; font-weight: 700; letter-spacing: 0.16em;
            color: var(--ink-3); text-transform: uppercase;
        }

        /* ===== Right aside ===== */
        .hero-aside { display: grid; gap: 16px; grid-template-rows: 1fr auto; }

        .status-card {
            padding: 32px 32px 26px;
            border-radius: var(--r-3xl);
            background: linear-gradient(160deg, var(--panel-2) 0%, var(--panel) 100%);
            border: 1px solid var(--line);
            box-shadow: var(--shadow-1);
            position: relative;
            overflow: hidden;
        }
        .status-card::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 280px; height: 280px;
            border-radius: var(--r-full);
            background: radial-gradient(circle, rgb(var(--lavender-rgb) / 0.18) 0%, transparent 60%);
            filter: blur(20px);
            pointer-events: none;
        }
        .status-card > * { position: relative; z-index: 2; }

        .status-title {
            margin-top: 14px;
            font-size: 32px; line-height: 1.1;
            letter-spacing: -0.02em;
        }
        .status-list { margin-top: 26px; display: flex; flex-direction: column; gap: 20px; }
        .status-row {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 14px;
            align-items: center;
        }
        .status-dot { width: 9px; height: 9px; border-radius: var(--r-full); flex-shrink: 0; }
        .status-dot.mint  { background: var(--mint);  box-shadow: 0 0 14px rgb(var(--mint-rgb) / 0.6); }
        .status-dot.amber { background: var(--amber); box-shadow: 0 0 14px rgb(var(--amber-rgb) / 0.5); }
        .status-dot.coral { background: var(--coral); box-shadow: 0 0 14px rgb(var(--coral-rgb) / 0.5); animation: pulse 2s ease-out infinite; }

        .status-row .text-main { font-size: 14px; font-weight: 600; line-height: 1.3; color: var(--ink-0); }
        .status-row .text-sub { font-size: 12px; color: var(--ink-3); margin-top: 3px; }
        .status-row .num {
            font-size: 24px; font-weight: 700;
            font-feature-settings: 'tnum' 1, 'lnum' 1;
            letter-spacing: -0.02em;
        }
        .status-row .num.mint  { color: var(--mint); }
        .status-row .num.amber { color: var(--amber); }
        .status-row .num.coral { color: var(--coral); }

        /* Spec list with colored icons per item */
        .spec-list { margin-top: 24px; display: flex; flex-direction: column; gap: 14px; }
        .spec-row {
            display: flex; align-items: flex-start; gap: 12px;
        }
        .spec-ic {
            width: 30px; height: 30px; border-radius: var(--r-md);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
            transition: transform var(--t-base);
        }
        .spec-row:hover .spec-ic { transform: scale(1.08); }
        .spec-ic.cyan     { background: rgb(var(--cyan-rgb) / 0.14); border: 1px solid rgb(var(--cyan-rgb) / 0.32); color: var(--cyan); box-shadow: 0 0 16px -3px rgb(var(--cyan-rgb) / 0.28); }
        .spec-ic.mint     { background: rgb(var(--mint-rgb) / 0.14); border: 1px solid rgb(var(--mint-rgb) / 0.32); color: var(--mint); box-shadow: 0 0 16px -3px rgb(var(--mint-rgb) / 0.28); }
        .spec-ic.lavender { background: rgb(var(--lavender-rgb) / 0.14); border: 1px solid rgb(var(--lavender-rgb) / 0.32); color: var(--lavender); box-shadow: 0 0 16px -3px rgb(var(--lavender-rgb) / 0.28); }
        .spec-ic.amber    { background: rgb(var(--amber-rgb) / 0.14);  border: 1px solid rgb(var(--amber-rgb) / 0.32);  color: var(--amber);    box-shadow: 0 0 16px -3px rgb(var(--amber-rgb) / 0.28); }
        .spec-ic.coral    { background: rgb(var(--coral-rgb) / 0.14); border: 1px solid rgb(var(--coral-rgb) / 0.32); color: var(--coral);    box-shadow: 0 0 16px -3px rgb(var(--coral-rgb) / 0.28); }

        .spec-row .text-main { font-size: 14px; font-weight: 600; line-height: 1.3; color: var(--ink-0); margin-top: 5px; }
        .spec-row .text-sub  { font-size: 12px; color: var(--ink-3); margin-top: 3px; line-height: 1.4; }

        .status-cta {
            margin-top: 28px;
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 18px;
            border-radius: var(--r-lg);
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--line);
            color: var(--ink-1);
            text-decoration: none;
            font-size: 14px; font-weight: 600;
            transition: all var(--t-base);
        }
        .status-cta:hover { background: rgba(255,255,255,0.08); color: var(--ink-0); border-color: var(--line-2); }

        .anomaly-card {
            padding: 26px 28px 24px;
            border-radius: var(--r-2xl);
            background: linear-gradient(160deg, var(--panel-2) 0%, var(--panel) 100%);
            border: 1px solid var(--line);
            box-shadow: var(--shadow-1);
            position: relative;
            overflow: hidden;
        }
        .anomaly-card::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 200px; height: 200px;
            border-radius: var(--r-full);
            background: radial-gradient(circle, rgb(var(--coral-rgb) / 0.14) 0%, transparent 60%);
            filter: blur(20px);
            pointer-events: none;
        }
        .anomaly-card > * { position: relative; z-index: 2; }

        .badge-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 11px; border-radius: var(--r-full);
            background: rgb(var(--lavender-rgb) / 0.14);
            border: 1px solid rgb(var(--lavender-rgb) / 0.30);
            color: var(--lavender);
            font-size: 10px; font-weight: 700; letter-spacing: 0.16em;
            text-transform: uppercase;
        }
        .anomaly-title {
            margin-top: 14px;
            font-size: 24px; line-height: 1.1; letter-spacing: -0.02em;
        }
        .anomaly-desc {
            margin-top: 10px;
            font-size: 13px; line-height: 1.6;
            color: var(--ink-2);
        }
        .anomaly-link {
            display: inline-flex; align-items: center; gap: 8px;
            margin-top: 16px;
            color: var(--lavender);
            font-size: 13px; font-weight: 600;
            text-decoration: none;
            transition: gap var(--t-base);
        }
        .anomaly-link:hover { gap: 12px; color: var(--pink); }

        /* ===== FEATURES BENTO ===== */
        .features { padding: 80px 0; }
        .features-head {
            display: flex; justify-content: space-between; align-items: end;
            gap: 24px; flex-wrap: wrap; margin-bottom: 40px;
        }
        .features-h2 {
            font-size: clamp(36px, 4vw, 56px);
            line-height: 1; letter-spacing: -0.03em;
            max-width: 680px;
            font-weight: 800;
        }
        .features-h2 .accent {
            background: linear-gradient(135deg, #5ed0ff, var(--lavender));
            -webkit-background-clip: text; background-clip: text;
            color: transparent;
            font-family: var(--font-serif);
            font-style: italic; font-weight: 600;
        }
        .features-sub { font-size: 14px; color: var(--ink-2); max-width: 320px; line-height: 1.6; }

        .bento {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 16px;
        }
        .bento-card {
            position: relative;
            padding: 30px;
            border-radius: var(--r-2xl);
            background: linear-gradient(160deg, var(--panel) 0%, var(--bg-1) 100%);
            border: 1px solid var(--line);
            box-shadow: var(--shadow-1);
            overflow: hidden;
            transition: all 0.3s var(--ease);
            min-height: 240px;
        }
        .bento-card:hover {
            transform: translateY(-4px);
            border-color: var(--line-2);
            box-shadow: var(--shadow-2);
        }
        .bento-card::after {
            content: '';
            position: absolute;
            top: 0; left: 30%;
            width: 40%; height: 1px;
            background: linear-gradient(90deg, transparent, var(--line-2), transparent);
        }
        .bento-card .ic {
            width: 42px; height: 42px;
            border-radius: var(--r-lg);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 18px;
            margin-bottom: 18px;
        }
        .bento-card.cyan    .ic { background: rgb(var(--cyan-rgb) / 0.14); color: var(--cyan); border: 1px solid rgb(var(--cyan-rgb) / 0.32); box-shadow: 0 0 24px -4px rgb(var(--cyan-rgb) / 0.25); }
        .bento-card.mint    .ic { background: rgb(var(--mint-rgb) / 0.14); color: var(--mint); border: 1px solid rgb(var(--mint-rgb) / 0.32); box-shadow: 0 0 24px -4px rgb(var(--mint-rgb) / 0.25); }
        .bento-card.lavender .ic { background: rgb(var(--lavender-rgb) / 0.14); color: var(--lavender); border: 1px solid rgb(var(--lavender-rgb) / 0.32); box-shadow: 0 0 24px -4px rgb(var(--lavender-rgb) / 0.25); }
        .bento-card.amber   .ic { background: rgb(var(--amber-rgb) / 0.14); color: var(--amber); border: 1px solid rgb(var(--amber-rgb) / 0.32); box-shadow: 0 0 24px -4px rgb(var(--amber-rgb) / 0.25); }
        .bento-card.sky     .ic { background: rgba(56,189,248,0.14); color: var(--sky); border: 1px solid rgba(56,189,248,0.32); box-shadow: 0 0 24px -4px rgba(56,189,248,0.25); }

        .bento-card h3 { font-size: 20px; font-weight: 700; letter-spacing: -0.02em; line-height: 1.3; color: var(--ink-0); }
        .bento-card p  { margin-top: 10px; font-size: 14px; line-height: 1.6; color: var(--ink-2); }

        .bento-card.wide-3 { grid-column: span 3; }
        .bento-card.wide-2 { grid-column: span 2; }
        .bento-card.wide-4 { grid-column: span 4; min-height: 300px; }

        .preview-card { padding: 28px; min-height: 300px; grid-column: span 4; }
        .preview-card .mini-dashboard {
            margin-top: 16px;
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .preview-stat {
            padding: 12px 14px;
            border-radius: var(--r-lg);
            background: rgba(0,0,0,0.30);
            border: 1px solid var(--line);
        }
        .preview-stat .lbl { font-size: 10px; font-weight: 700; letter-spacing: 0.12em; color: var(--ink-3); text-transform: uppercase; }
        .preview-stat .val { font-size: 24px; font-weight: 800; margin-top: 4px; letter-spacing: -0.02em; font-feature-settings: 'tnum' 1, 'lnum' 1; }
        .preview-stat.cyan    .val { color: var(--cyan); }
        .preview-stat.mint    .val { color: var(--mint); }
        .preview-stat.lavender .val { color: var(--lavender); }

        .mini-chart { margin-top: 14px; height: 56px; }

        /* ===== METRICS STRIP ===== */
        .metrics {
            padding: 36px 38px;
            background: linear-gradient(180deg, rgba(20, 28, 50, 0.5), rgba(14, 20, 36, 0.5));
            border: 1px solid var(--line);
            border-radius: var(--r-3xl);
            box-shadow: var(--shadow-1);
        }
        .metrics-inner {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }
        .metric { text-align: left; position: relative; }
        .metric:not(:first-child) { padding-left: 24px; border-left: 1px solid var(--line); }
        .metric .v {
            font-size: 40px; font-weight: 800; letter-spacing: -0.04em;
            font-feature-settings: 'tnum' 1, 'lnum' 1;
            color: var(--ink-0);
            line-height: 1;
        }
        .metric .v sup { font-size: 18px; color: var(--ink-3); margin-left: 2px; top: -10px; }
        .metric .l { font-size: 12px; font-weight: 600; color: var(--ink-3); margin-top: 8px; letter-spacing: 0.05em; }

        /* ===== HOW IT WORKS ===== */
        .how { padding: 80px 0; }
        .how-head { display: flex; align-items: end; justify-content: space-between; gap: 24px; flex-wrap: wrap; margin-bottom: 36px; }
        .how-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
        }
        .step {
            padding: 30px 28px;
            border-radius: var(--r-2xl);
            background: linear-gradient(160deg, var(--panel) 0%, var(--bg-1) 100%);
            border: 1px solid var(--line);
            box-shadow: var(--shadow-1);
            position: relative;
            transition: all 0.3s var(--ease);
        }
        .step:hover {
            border-color: var(--line-2);
            transform: translateY(-3px);
        }
        .step .num-circle {
            width: 38px; height: 38px; border-radius: var(--r-lg);
            background: rgb(var(--cyan-rgb) / 0.12);
            border: 1px solid rgb(var(--cyan-rgb) / 0.32);
            color: var(--cyan);
            display: inline-flex; align-items: center; justify-content: center;
            font-family: var(--font-mono);
            font-size: 13px; font-weight: 600;
            margin-bottom: 20px;
        }
        .step h4 { font-size: 18px; font-weight: 700; letter-spacing: -0.02em; color: var(--ink-0); }
        .step p { margin-top: 10px; font-size: 14px; line-height: 1.6; color: var(--ink-2); }

        /* ===== CTA FINAL ===== */
        .cta-final { padding: 60px 0 100px; }
        .cta-box {
            position: relative;
            padding: 72px 56px;
            border-radius: var(--r-3xl);
            background: linear-gradient(160deg, var(--panel-2) 0%, var(--panel) 100%);
            border: 1px solid var(--line-2);
            box-shadow: var(--shadow-2);
            text-align: center;
            overflow: hidden;
        }
        .cta-box::before {
            content: '';
            position: absolute;
            top: -160px; left: 20%;
            width: 600px; height: 400px;
            background: radial-gradient(ellipse, rgb(var(--cyan-rgb) / 0.18) 0%, transparent 60%);
            filter: blur(20px);
            pointer-events: none;
        }
        .cta-box::after {
            content: '';
            position: absolute;
            bottom: -160px; right: 20%;
            width: 600px; height: 400px;
            background: radial-gradient(ellipse, rgb(var(--lavender-rgb) / 0.18) 0%, transparent 60%);
            filter: blur(20px);
            pointer-events: none;
        }
        .cta-box > * { position: relative; z-index: 2; }
        .cta-h2 {
            font-size: clamp(40px, 5vw, 68px);
            line-height: 1; letter-spacing: -0.04em;
            max-width: 820px; margin: 14px auto 0;
            font-weight: 800;
        }
        .cta-h2 .accent {
            background: linear-gradient(135deg, #5ed0ff, var(--lavender));
            -webkit-background-clip: text; background-clip: text;
            color: transparent;
            font-family: var(--font-serif);
            font-style: italic; font-weight: 600;
        }
        .cta-sub {
            margin-top: 20px;
            font-size: 16px; color: var(--ink-1); max-width: 560px;
            margin-left: auto; margin-right: auto; line-height: 1.6;
        }
        .cta-buttons { margin-top: 36px; display: inline-flex; gap: 12px; flex-wrap: wrap; justify-content: center; }

        /* ===== Footer ===== */
        .footer {
            border-top: 1px solid var(--line);
            padding: 36px 0 30px;
            background: rgba(6, 9, 18, 0.5);
        }
        .footer-inner {
            display: flex; justify-content: space-between; align-items: center;
            gap: 24px; flex-wrap: wrap;
        }
        .footer-links { display: flex; gap: 22px; flex-wrap: wrap; }
        .footer-links a { color: var(--ink-3); text-decoration: none; font-size: 13px; font-weight: 500; transition: color var(--t-base); }
        .footer-links a:hover { color: var(--ink-1); }
        .footer-copy { font-size: 12px; color: var(--ink-4); }

        /* ===== Reveal ===== */
        .reveal {
            opacity: 0; transform: translateY(24px);
            transition: opacity 0.7s cubic-bezier(0.16,1,0.3,1), transform 0.7s cubic-bezier(0.16,1,0.3,1);
        }
        .reveal.visible { opacity: 1; transform: translateY(0); }

        /* Hide scrollbars completely — all browsers including mobile */
        html, body, * {
            scrollbar-width: none !important;
            scrollbar-color: transparent transparent !important;
            -ms-overflow-style: none !important;
        }
        html { overflow-x: hidden; }
        body { -webkit-overflow-scrolling: touch; }
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
        /* Tablet & smaller laptop (≤ 1024 px) — stack hero, 2-col bento */
        @media (max-width: 1024px) {
            .hero-grid { grid-template-columns: 1fr; }
            .hero-main { padding: 40px 32px 32px; min-height: 0; }
            .hero-trust { margin-left: -32px; margin-right: -32px; padding-left: 32px; padding-right: 32px; }
            .bento { grid-template-columns: repeat(2, 1fr); }
            .bento-card.wide-3, .bento-card.wide-2, .bento-card.wide-4, .preview-card { grid-column: span 2; }
            .metrics-inner { grid-template-columns: repeat(2, 1fr); gap: 32px 24px; }
            .metric:nth-child(3) { padding-left: 0; border-left: 0; }
            .how-grid { grid-template-columns: 1fr; }
            .nav-links { display: none; }
            .menu-toggle { display: inline-flex; align-items: center; justify-content: center; }
        }

        /* Tablet portrait (≤ 768 px) — tighten hero & spacing */
        @media (max-width: 768px) {
            .section { padding: 0 24px; }
            .hero { padding: 48px 0 40px; }
            .hero-main { padding: 32px 28px 28px; border-radius: var(--r-2xl); }
            .hero-h1 { font-size: 44px; letter-spacing: -0.02em; }
            .hero-sub { font-size: 16px; max-width: 100%; }
            .hero-trust { margin-left: -28px; margin-right: -28px; padding-left: 28px; padding-right: 28px; }
            .status-card { padding: 28px 26px 24px; border-radius: var(--r-2xl); }
            .status-title { font-size: 28px; }
            .features, .how, .cta-final { padding: 64px 0; }
            .cta-box { padding: 56px 32px; }
        }

        /* Mobile (≤ 480px) — full mobile layout */
        @media (max-width: 480px) {
            .nav-inner { padding: 14px 24px; }
            .section { padding: 0 24px; max-width: 100%; }
            .brand-tag { display: none; }
            .lang-pill { display: none; }
            .btn-pill { padding: 8px 14px; font-size: 12px; }

            .hero { padding: 32px 0 28px; }
            .hero-grid { gap: 20px; }
            .hero-main { padding: 32px 28px 28px; border-radius: var(--r-2xl); }
            .hero-h1 { font-size: 36px; letter-spacing: -0.02em; line-height: 1.1; }
            .hero-sub { font-size: 14px; line-height: 1.6; max-width: 100%; }
            .hero-cta { flex-direction: column; gap: 10px; }
            .hero-cta > a { width: 100%; justify-content: center; }
            .btn-cta-primary, .btn-cta-ghost { padding: 13px 18px; font-size: 13px; min-height: 44px; }

            /* hero-trust → 2×2 grid (lebih rapi dari flex-wrap) */
            .hero-trust {
                margin-left: -28px; margin-right: -28px;
                padding: 22px 28px 4px;
                gap: 18px 14px;
                display: grid;
                grid-template-columns: 1fr 1fr;
            }
            .hero-trust .num { font-size: 24px; }
            .hero-trust .lbl { font-size: 10px; }

            .status-card { padding: 24px 22px 20px; border-radius: var(--r-2xl); }
            .status-title { font-size: 28px; }
            .spec-row { gap: 10px; }
            .anomaly-card { padding: 22px 20px 20px; border-radius: var(--r-2xl); }

            .bento { grid-template-columns: 1fr; gap: 12px; }
            .bento-card, .bento-card.wide-3, .bento-card.wide-2, .bento-card.wide-4, .preview-card {
                grid-column: span 1; min-height: 0; padding: 22px 20px; border-radius: var(--r-2xl);
            }

            .metrics { padding: 26px 22px; border-radius: var(--r-2xl); }
            .metrics-inner { grid-template-columns: 1fr 1fr; gap: 24px 16px; }
            .metric:not(:first-child) { padding-left: 0; border-left: 0; }
            .metric .v { font-size: 28px; }

            .cta-box { padding: 44px 24px; border-radius: var(--r-2xl); }
            .features, .how, .cta-final { padding: 56px 0; }
            .features-h2, .cta-h2 { font-size: 28px; line-height: 1.1; }
            .features-sub, .cta-sub { font-size: 14px; }
        }

        /* Mobile M (≤ 480 px) — further tightening */
        @media (max-width: 480px) {
            .nav-inner { padding: 12px 20px; }
            .section { padding: 0 20px; }
            .hero { padding: 24px 0 22px; }
            .hero-grid { gap: 16px; }
            .hero-main { padding: 26px 22px 22px; border-radius: var(--r-2xl); }
            .hero-h1 { font-size: 28px; line-height: 1.1; }
            .hero-sub { font-size: 14px; margin-top: 12px; }
            .hero-cta { margin-top: 18px; }
            .btn-cta-primary, .btn-cta-ghost { padding: 12px 16px; font-size: 13px; }

            .hero-trust {
                margin-left: -22px; margin-right: -22px;
                padding: 18px 22px 2px;
                gap: 14px 12px;
            }
            .hero-trust .num { font-size: 20px; }
            .hero-trust .lbl { font-size: 10px; letter-spacing: 0.12em; }

            .status-card { padding: 20px 18px 16px; border-radius: var(--r-2xl); }
            .status-title { font-size: 24px; }
            .anomaly-card { padding: 20px 18px 18px; border-radius: var(--r-2xl); }
            .bento-card { padding: 20px 18px; border-radius: var(--r-xl); }

            .metrics { padding: 22px 18px; }
            .metric .v { font-size: 28px; }

            .cta-box { padding: 36px 20px; border-radius: var(--r-2xl); }
            .features, .how, .cta-final { padding: 44px 0; }
            .features-h2, .cta-h2 { font-size: 28px; }
        }

        /* Mobile S (≤ 480px) — extra tight */
        @media (max-width: 480px) {
            .nav-inner { padding: 10px 18px; }
            .section { padding: 0 18px; }
            .hero { padding: 20px 0 18px; }
            .hero-main { padding: 22px 18px 20px; border-radius: var(--r-xl); }
            .hero-h1 { font-size: 24px; letter-spacing: -0.02em; }
            .hero-sub { font-size: 13px; line-height: 1.5; }
            .btn-cta-primary, .btn-cta-ghost { padding: 11px 14px; font-size: 12px; min-height: 42px; }

            .hero-trust {
                margin-left: -18px; margin-right: -18px;
                padding: 16px 18px 2px;
                gap: 12px 10px;
            }
            .hero-trust .num { font-size: 18px; }
            .hero-trust .lbl { font-size: 8px; letter-spacing: 0.12em; }

            .status-card { padding: 18px 14px 14px; border-radius: var(--r-xl); }
            .status-title, .features-h2, .cta-h2 { font-size: 20px !important; line-height: 1.1; }
            .anomaly-card { padding: 18px 14px 14px; border-radius: var(--r-xl); }
            .bento-card { padding: 18px 14px; border-radius: var(--r-xl); }

            .metrics { padding: 20px 14px; border-radius: var(--r-2xl); }
            .metric .v { font-size: 24px; }
            .metric .l { font-size: 10px; }

            .cta-box { padding: 28px 16px; border-radius: var(--r-xl); }
            .features, .how, .cta-final { padding: 36px 0; }
            .features-sub, .cta-sub { font-size: 13px; }
        }

        /* ===== Cross-cutting cleanup for ALL mobile/tablet (≤ 1024 px) ===== */
        @media (max-width: 1024px) {
            /* Section headings (features-head, how-head) — match bento card visual offset */
            .features-head, .how-head { padding-left: 14px; padding-right: 14px; }
            .features-h2 { line-height: 1.1; }
            .features-sub { line-height: 1.6; }

            /* Brand block — keep name on single line */
            .brand-block { min-width: 0; }
            .brand-block .brand-name { white-space: nowrap; }

            /* Spec list rows — tighter spacing + smaller icons on mobile */
            .spec-list { gap: 14px; }
            .spec-ic { width: 32px; height: 32px; font-size: 13px; flex-shrink: 0; }
            .text-main { font-size: 13px; line-height: 1.3; }
            .text-sub { font-size: 12px; }

            /* Bento preview-card mini dashboard — 3 stats can squeeze on narrow screens */
            .preview-card .mini-dashboard {
                gap: 10px;
                grid-template-columns: repeat(3, 1fr);
            }
            .preview-stat { padding: 12px; }
            .preview-stat .val { font-size: 20px; }
            .preview-stat .lbl { font-size: 8px; letter-spacing: 0.12em; }

            /* How section — reduce step number circle */
            .step .num-circle { width: 40px; height: 40px; font-size: 14px; }
            .step h4 { font-size: 16px; }
            .step p { font-size: 13px; margin-top: 8px; }

            /* Footer — center alignment on smaller screens, vertical stack */
            .footer-inner {
                flex-direction: column;
                text-align: center;
                gap: 16px;
            }
            .footer-links { justify-content: center; }
        }

        /* Mobile (≤ 480 px): preview-card stats 1-col so values stay readable */
        @media (max-width: 480px) {
            .preview-card .mini-dashboard {
                grid-template-columns: 1fr 1fr;
            }
            .preview-card .preview-stat:nth-child(3) { grid-column: 1 / -1; }
            .preview-stat .val { font-size: 24px; }
        }

        /* Mobile (≤ 480px): spec rows even tighter */
        @media (max-width: 480px) {
            .spec-list { gap: 11px; }
            .spec-ic { width: 28px; height: 28px; font-size: 11px; }
            .text-main { font-size: 13px; }
            .text-sub { font-size: 11px; }
            .step .num-circle { width: 36px; height: 36px; font-size: 13px; }
            .step h4 { font-size: 16px; }
            .step p { font-size: 13px; }
            .footer-links { gap: 16px; font-size: 12px; }
            .footer-copy { font-size: 11px; }
        }

        /* Landscape mobile (short screens) */
        @media (max-height: 500px) and (orientation: landscape) {
            .hero { padding: 24px 0; }
            .hero-main { padding: 26px 28px 24px; min-height: 0; }
            .hero-h1 { font-size: 40px; }
            .hero-trust { padding-top: 22px; gap: 22px; }
            .glow-top, .glow-right { opacity: 0.6; }
            .features, .how, .cta-final { padding: 40px 0; }
        }

        /* Touch devices — disable cursor spotlight (waste on touch) */
        @media (hover: none) and (pointer: coarse) {
            .spotlight { display: none !important; }
            .btn-cta-primary, .btn-cta-ghost, .btn-pill, .status-cta { min-height: 44px; }
            .menu-toggle, .lang-pill { min-width: 44px; min-height: 44px; }
        }

        /* Respect prefers-reduced-motion — disable decorative animations */
        @media (prefers-reduced-motion: reduce) {
            * { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; }
            .glow-top, .glow-right { animation: none !important; }
            .spotlight { display: none !important; }
            html { scroll-behavior: auto; }
        }
    </style>
</head>
<body>

    <div class="glow-top"></div>
    <div class="glow-right"></div>
    <div class="grid-overlay"></div>
    <div class="spotlight" id="spotlight"></div>

    {{-- NAV --}}
    <nav class="nav" id="topNav">
        <div class="nav-inner">
            <a href="#produk" class="brand" style="text-decoration:none;color:inherit;">
                <div class="brand-mark">
                    <div class="mark-inner"><i class="fa-solid fa-snowflake"></i></div>
                </div>
                <div>
                    <div class="brand-name">Control AC</div>
                    <div class="brand-tag">Cooling, Reimagined</div>
                </div>
            </a>

            <div class="nav-links">
                <a href="#produk">Product</a>
                <a href="#fitur">Features</a>
                <a href="#cara-kerja">How It Works</a>
            </div>

            <div class="nav-actions">
                <button type="button" class="lang-pill">
                    <i class="fa-solid fa-globe" style="font-size:10px;opacity:0.7;"></i>
                    <span>ID</span>
                </button>
                <a href="{{ route('login') }}" class="btn-pill">
                    Sign In
                    <i class="fa-solid fa-arrow-right" style="font-size:10px;"></i>
                </a>
            </div>
        </div>
    </nav>

    {{-- HERO --}}
    <section class="hero section" id="produk">
        <div class="hero-grid">
            <div class="hero-main reveal">
                <h1 class="hero-h1">
                    Cooling that<br>
                    <span class="accent">thinks</span><br>
                    before you do.
                </h1>

                <p class="hero-sub">
                    Monitor every server room. Learn its rhythms. The system keeps
                    temperature stable — even while you sleep.
                </p>

                <div class="hero-cta">
                    <a href="{{ route('login') }}" class="btn-cta-primary">
                        Get started
                        <i class="fa-solid fa-arrow-right" style="font-size:11px;"></i>
                    </a>
                </div>

                <div class="hero-trust">
                    <div class="item">
                        <div class="num">10</div>
                        <div class="lbl">Active rooms</div>
                    </div>
                    <div class="item">
                        <div class="num">99.9<sup style="font-size:14px;color:var(--ink-3);">%</sup></div>
                        <div class="lbl">Uptime</div>
                    </div>
                    <div class="item">
                        <div class="num">&lt;2<sup style="font-size:14px;color:var(--ink-3);">s</sup></div>
                        <div class="lbl">Latency</div>
                    </div>
                    <div class="item">
                        <div class="num">24/7</div>
                        <div class="lbl">Monitor</div>
                    </div>
                </div>
            </div>

            <aside class="hero-aside">
                <section class="status-card reveal">
                    <p class="eyebrow">Specs</p>
                    <h2 class="serif status-title">Built for<br>scale.</h2>

                    <div class="spec-list">
                        <div class="spec-row">
                            <span class="spec-ic cyan"><i class="fa-solid fa-plug"></i></span>
                            <div>
                                <p class="text-main">MQTT + WebSocket realtime</p>
                                <p class="text-sub">Sub-second updates via Laravel Reverb</p>
                            </div>
                        </div>
                        <div class="spec-row">
                            <span class="spec-ic lavender"><i class="fa-solid fa-shield-halved"></i></span>
                            <div>
                                <p class="text-main">Multi-role hierarchy</p>
                                <p class="text-sub">Admin · Operator · User</p>
                            </div>
                        </div>
                        <div class="spec-row">
                            <span class="spec-ic mint"><i class="fa-solid fa-microchip"></i></span>
                            <div>
                                <p class="text-main">ESP32 auto-discovery</p>
                                <p class="text-sub">Plug, flash, ready to go</p>
                            </div>
                        </div>
                        <div class="spec-row">
                            <span class="spec-ic amber"><i class="fa-solid fa-clipboard-list"></i></span>
                            <div>
                                <p class="text-main">Complete activity logs</p>
                                <p class="text-sub">Audit trail per user action</p>
                            </div>
                        </div>
                        <div class="spec-row">
                            <span class="spec-ic coral"><i class="fa-solid fa-bell-slash"></i></span>
                            <div>
                                <p class="text-main">Notification deduplication</p>
                                <p class="text-sub">No spam during extended incidents</p>
                            </div>
                        </div>
                    </div>

                    <a href="#cara-kerja" class="status-cta">
                        <span>See how it works</span>
                        <i class="fa-solid fa-arrow-right" style="font-size:11px;"></i>
                    </a>
                </section>

                <section class="anomaly-card reveal">
                    <span class="badge-pill">
                        <i class="fa-solid fa-sparkles" style="font-size: 10px;"></i>
                        New
                    </span>
                    <h3 class="serif anomaly-title">Anomalies, detected early.</h3>
                    <p class="anomaly-desc">The system learns the normal pattern for each room. When something is off — you're the first to know.</p>
                    <a href="#fitur" class="anomaly-link">
                        Learn more
                        <i class="fa-solid fa-arrow-right" style="font-size:10px;"></i>
                    </a>
                </section>
            </aside>
        </div>
    </section>

    {{-- FEATURES BENTO --}}
    <section class="features section" id="fitur">
        <div class="features-head reveal">
            <h2 class="features-h2">
                Not just an AC remote.<br><span class="accent">A system that thinks</span> for you.
            </h2>
            <p class="features-sub">Every feature is built to keep server rooms cool without you having to think about it.</p>
        </div>

        <div class="bento">
            <div class="bento-card wide-3 cyan reveal">
                <span class="ic"><i class="fa-solid fa-bolt"></i></span>
                <h3>Realtime · sub-second</h3>
                <p>Every temperature change, AC status, or ESP32 connection is instantly updated via MQTT + WebSocket. Zero lag.</p>
            </div>

            <div class="bento-card wide-3 lavender reveal">
                <span class="ic"><i class="fa-solid fa-clock"></i></span>
                <h3>Automatic scheduling</h3>
                <p>Set AC on/off per room, per hour. The system executes on time with ±30 second tolerance.</p>
            </div>

            <div class="bento-card wide-2 mint reveal">
                <span class="ic"><i class="fa-solid fa-bell"></i></span>
                <h3>Smart notifications</h3>
                <p>Device offline, temperature above threshold, anomaly — all become actionable alerts.</p>
            </div>

            <div class="bento-card preview-card wide-4 sky reveal">
                <span class="ic"><i class="fa-solid fa-chart-line"></i></span>
                <h3>Trend &amp; 24-hour history</h3>
                <p>Visualize each room's performance — see when temperature rises and how long AC has been running.</p>
                <div class="mini-dashboard">
                    <div class="preview-stat cyan"><div class="lbl">Server 14</div><div class="val">22.4°</div></div>
                    <div class="preview-stat mint"><div class="lbl">AC Active</div><div class="val">8</div></div>
                    <div class="preview-stat lavender"><div class="lbl">Comfort</div><div class="val">73%</div></div>
                </div>
                <svg class="mini-chart" viewBox="0 0 400 56" preserveAspectRatio="none" fill="none">
                    <path d="M0 36 Q 40 22, 70 28 T 130 24 T 200 32 T 270 16 T 340 24 T 400 18"
                          stroke="url(#chart-grad)" stroke-width="2.2" stroke-linecap="round"/>
                    <defs>
                        <linearGradient id="chart-grad" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%" stop-color="#5ed0ff" stop-opacity="0.5"/>
                            <stop offset="50%" stop-color="#b4a3ff"/>
                            <stop offset="100%" stop-color="#6ee7b7" stop-opacity="0.6"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
        </div>
    </section>

    {{-- METRICS STRIP --}}
    <section class="section" id="statistik">
        <div class="metrics reveal">
            <div class="metrics-inner">
                <div class="metric">
                    <p class="v">10K<sup>+</sup></p>
                    <p class="l">Controlled actions</p>
                </div>
                <div class="metric">
                    <p class="v">99.9<sup>%</sup></p>
                    <p class="l">System uptime</p>
                </div>
                <div class="metric">
                    <p class="v">&lt;500<sup>ms</sup></p>
                    <p class="l">Response broadcast</p>
                </div>
                <div class="metric">
                    <p class="v">3</p>
                    <p class="l">Role hierarchy</p>
                </div>
            </div>
        </div>
    </section>

    {{-- HOW IT WORKS --}}
    <section class="how section" id="cara-kerja">
        <div class="how-head reveal">
            <div>
                <p class="eyebrow">How It Works</p>
                <h2 class="features-h2" style="margin-top:14px;">Three steps, <span class="accent">done.</span></h2>
            </div>
            <p class="features-sub">From unboxing the ESP32 to live monitoring — the whole process takes under 10 minutes.</p>
        </div>

        <div class="how-grid">
            <div class="step reveal">
                <div class="num-circle">01</div>
                <h4>Connect ESP32</h4>
                <p>Flash the firmware, install the sensor in the room, and the device is automatically discovered by the system.</p>
            </div>
            <div class="step reveal">
                <div class="num-circle">02</div>
                <h4>Configure rooms &amp; AC</h4>
                <p>Add rooms, register AC units, choose the brand. MQTT topic mapping is built automatically.</p>
            </div>
            <div class="step reveal">
                <div class="num-circle">03</div>
                <h4>Let the system handle it</h4>
                <p>Schedules, anomalies, notifications — all run in the background. You just check the dashboard.</p>
            </div>
        </div>
    </section>

    {{-- CTA FINAL --}}
    <section class="cta-final section">
        <div class="cta-box reveal">
            <span class="eyebrow"><i class="fa-solid fa-bolt" style="font-size: 10px;"></i> Ready to use</span>
            <h2 class="cta-h2">
                Start monitoring your rooms <span class="accent">today.</span>
            </h2>
            <p class="cta-sub">
                Free for all roles. Sign in with the account created by your administrator.
            </p>
            <div class="cta-buttons">
                <a href="{{ route('login') }}" class="btn-cta-primary">
                    Sign In
                    <i class="fa-solid fa-arrow-right" style="font-size:11px;"></i>
                </a>
                <a href="#fitur" class="btn-cta-ghost">
                    <span class="play-icon"><i class="fa-solid fa-circle-info"></i></span>
                    Explore features
                </a>
            </div>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="footer">
        <div class="section footer-inner">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="brand-mark" style="width:32px;height:32px;border-radius: 10px;">
                    <div class="mark-inner" style="width:26px;height:26px;font-size:12px;border-radius: 8px;"><i class="fa-solid fa-snowflake"></i></div>
                </div>
                <span style="font-size:13px;font-weight:600;color:var(--ink-1);">Control AC</span>
            </div>
            <div class="footer-links">
                <a href="#produk">Product</a>
                <a href="#fitur">Features</a>
                <a href="#cara-kerja">How It Works</a>
            </div>
            <div class="footer-copy">© 2026 Control AC — SmartAC IoT Platform</div>
        </div>
    </footer>

    <script>
        // Nav scrolled state
        const nav = document.getElementById('topNav');
        const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 8);
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        // Reveal on scroll
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.12 });
        document.querySelectorAll('.reveal').forEach(el => io.observe(el));

        // Cursor spotlight (skip on touch / reduced motion)
        const isTouch = window.matchMedia('(hover: none) and (pointer: coarse)').matches;
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!isTouch && !reducedMotion) {
            const spotlight = document.getElementById('spotlight');
            let tx = window.innerWidth / 2, ty = window.innerHeight / 2;
            let cx = tx, cy = ty;
            window.addEventListener('pointermove', (e) => { tx = e.clientX; ty = e.clientY; });
            (function tick() {
                cx += (tx - cx) * 0.08;
                cy += (ty - cy) * 0.08;
                spotlight.style.transform = `translate(${cx}px, ${cy}px) translate(-50%, -50%)`;
                requestAnimationFrame(tick);
            })();
        }

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', (e) => {
                const id = a.getAttribute('href').slice(1);
                const el = document.getElementById(id);
                if (el) { e.preventDefault(); el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
            });
        });
    </script>

</body>
</html>
