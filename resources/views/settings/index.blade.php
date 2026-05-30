<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Settings — SmartAC</title>
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite('resources/js/app.js')
    @include('components.sidebar-styles')
    <style>
        .setting-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--line-soft);
        }
        .setting-row:last-of-type { border-bottom: none; }
        .setting-label {
            font-size: 13px;
            color: var(--ink-1);
            flex: 1;
        }
        .setting-input-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }
        .setting-input {
            width: 80px;
            background: var(--panel-2);
            border: 1px solid var(--line);
            border-radius: var(--r-md);
            color: var(--ink-0);
            font-size: 13px;
            font-family: var(--font-mono);
            padding: 5px 8px;
            text-align: right;
            outline: none;
            transition: var(--t-base);
        }
        .setting-input:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 2px rgb(var(--cyan-rgb) / .15);
        }
        .setting-input.is-invalid { border-color: var(--coral); }
        .setting-unit {
            font-size: 11px;
            color: var(--ink-3);
            min-width: 30px;
        }
        .setting-hint {
            font-size: 11px;
            color: var(--ink-3);
            margin-top: 10px;
            line-height: 1.6;
            display: flex;
            align-items: flex-start;
            gap: 6px;
        }
        .fuzzy-preview {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 14px;
            padding: 10px 12px;
            background: var(--panel-2);
            border-radius: var(--r-lg);
            font-size: 12px;
            font-family: var(--font-mono);
            color: var(--ink-2);
        }
        .fz-badge {
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.05em;
        }
        .fz-cold   { background: rgb(var(--cyan-rgb) / .14);  color: var(--cyan); }
        .fz-normal { background: rgb(var(--mint-rgb) / .14); color: var(--mint); }
        .fz-hot    { background: rgba(248,113,113,.14); color: var(--coral); }
        .fz-sep    { color: var(--ink-4); }
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
                        <h1>Settings</h1>
                        <p>SmartAC system configuration</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @include('components.notification-bell')
                    <span id="systemStatus" class="pill pill-online">
                        <span class="dot"></span>
                        <span>Online</span>
                    </span>
                </div>
            </header>

            <div class="page-body">
                <div class="app-content">
                    <div class="app-content-inner space-y-4">

                        <form method="POST" action="{{ route('settings.update') }}">
                            @csrf

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                                {{-- DATA RETENTION --}}
                                <div class="tbl-wrap" style="padding:0;">
                                    <div class="tbl-toolbar" style="border-bottom:1px solid var(--line-soft);">
                                        <div class="app-header-title" style="margin:0;">
                                            <h1 style="font-size:13px;"><i class="fa-solid fa-database" style="color:var(--cyan);margin-right:7px;"></i>Data Retention</h1>
                                            <p>How long data is retained in the database</p>
                                        </div>
                                    </div>
                                    <div style="padding:4px 16px 16px;">
                                        <div class="setting-row">
                                            <label for="temp_retention_days" class="setting-label">Retain temperature data</label>
                                            <div class="setting-input-wrap">
                                                <input type="number" id="temp_retention_days" name="temp_retention_days"
                                                    class="setting-input {{ $errors->has('temp_retention_days') ? 'is-invalid' : '' }}"
                                                    value="{{ old('temp_retention_days', $settings['temp_retention_days']->value ?? 7) }}"
                                                    min="1" max="365">
                                                <span class="setting-unit">days</span>
                                            </div>
                                        </div>
                                        @error('temp_retention_days')
                                            <p style="font-size:11px;color:var(--coral);margin:2px 0 4px;">{{ $message }}</p>
                                        @enderror

                                        <div class="setting-row">
                                            <label for="notification_retention_days" class="setting-label">Retain notifications</label>
                                            <div class="setting-input-wrap">
                                                <input type="number" id="notification_retention_days" name="notification_retention_days"
                                                    class="setting-input {{ $errors->has('notification_retention_days') ? 'is-invalid' : '' }}"
                                                    value="{{ old('notification_retention_days', $settings['notification_retention_days']->value ?? 30) }}"
                                                    min="1" max="365">
                                                <span class="setting-unit">days</span>
                                            </div>
                                        </div>
                                        @error('notification_retention_days')
                                            <p style="font-size:11px;color:var(--coral);margin:2px 0 4px;">{{ $message }}</p>
                                        @enderror

                                        <div class="setting-row">
                                            <label for="log_retention_days" class="setting-label">Retain activity log</label>
                                            <div class="setting-input-wrap">
                                                <input type="number" id="log_retention_days" name="log_retention_days"
                                                    class="setting-input {{ $errors->has('log_retention_days') ? 'is-invalid' : '' }}"
                                                    value="{{ old('log_retention_days', $settings['log_retention_days']->value ?? 90) }}"
                                                    min="1" max="365">
                                                <span class="setting-unit">days</span>
                                            </div>
                                        </div>
                                        @error('log_retention_days')
                                            <p style="font-size:11px;color:var(--coral);margin:2px 0 4px;">{{ $message }}</p>
                                        @enderror

                                        <p class="setting-hint">
                                            <i class="fa-regular fa-clock" style="margin-top:1px;flex-shrink:0;"></i>
                                            Data older than this value is automatically deleted daily by the scheduler.
                                        </p>
                                    </div>
                                </div>

                                {{-- FUZZY THRESHOLD --}}
                                <div class="tbl-wrap" style="padding:0;">
                                    <div class="tbl-toolbar" style="border-bottom:1px solid var(--line-soft);">
                                        <div class="app-header-title" style="margin:0;">
                                            <h1 style="font-size:13px;"><i class="fa-solid fa-sliders" style="color:var(--cyan);margin-right:7px;"></i>Fuzzy Logic Threshold</h1>
                                            <p>Temperature thresholds for automatic cooling logic</p>
                                        </div>
                                    </div>
                                    <div style="padding:4px 16px 16px;">
                                        <div class="setting-row">
                                            <label for="fuzzy_temp_cold" class="setting-label">Cold temperature limit (max)</label>
                                            <div class="setting-input-wrap">
                                                <input type="number" id="fuzzy_temp_cold" name="fuzzy_temp_cold"
                                                    class="setting-input {{ $errors->has('fuzzy_temp_cold') ? 'is-invalid' : '' }}"
                                                    value="{{ old('fuzzy_temp_cold', $settings['fuzzy_temp_cold']->value ?? 22) }}"
                                                    min="16" max="28" oninput="updateFuzzyPreview()">
                                                <span class="setting-unit">°C</span>
                                            </div>
                                        </div>
                                        @error('fuzzy_temp_cold')
                                            <p style="font-size:11px;color:var(--coral);margin:2px 0 4px;">{{ $message }}</p>
                                        @enderror

                                        <div class="setting-row">
                                            <label for="fuzzy_temp_hot" class="setting-label">Hot temperature limit (min)</label>
                                            <div class="setting-input-wrap">
                                                <input type="number" id="fuzzy_temp_hot" name="fuzzy_temp_hot"
                                                    class="setting-input {{ $errors->has('fuzzy_temp_hot') ? 'is-invalid' : '' }}"
                                                    value="{{ old('fuzzy_temp_hot', $settings['fuzzy_temp_hot']->value ?? 30) }}"
                                                    min="24" max="40" oninput="updateFuzzyPreview()">
                                                <span class="setting-unit">°C</span>
                                            </div>
                                        </div>
                                        @error('fuzzy_temp_hot')
                                            <p style="font-size:11px;color:var(--coral);margin:2px 0 4px;">{{ $message }}</p>
                                        @enderror

                                        <div class="fuzzy-preview">
                                            <span class="fz-badge fz-cold">Cold</span>
                                            <span>≤ <span id="previewCold">{{ $settings['fuzzy_temp_cold']->value ?? 22 }}</span>°C</span>
                                            <span class="fz-sep">·</span>
                                            <span class="fz-badge fz-normal">Normal</span>
                                            <span><span id="previewNormalRange">{{ $settings['fuzzy_temp_cold']->value ?? 22 }}–{{ $settings['fuzzy_temp_hot']->value ?? 30 }}</span>°C</span>
                                            <span class="fz-sep">·</span>
                                            <span class="fz-badge fz-hot">Hot</span>
                                            <span>≥ <span id="previewHot">{{ $settings['fuzzy_temp_hot']->value ?? 30 }}</span>°C</span>
                                        </div>

                                        <p class="setting-hint">
                                            <i class="fa-solid fa-circle-info" style="margin-top:1px;flex-shrink:0;"></i>
                                            Normal midpoint calculated automatically:
                                            <strong id="previewMid">{{ round((($settings['fuzzy_temp_cold']->value ?? 22) + ($settings['fuzzy_temp_hot']->value ?? 30)) / 2) }}</strong>°C.
                                        </p>
                                    </div>
                                </div>

                            </div>

                            <div class="flex justify-end" style="margin-top:16px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Save Settings
                                </button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('components.bottom-nav')
    @include('components.sidebar-scripts')

    <script>
    function updateFuzzyPreview() {
        const cold = parseInt(document.getElementById('fuzzy_temp_cold').value) || 22;
        const hot  = parseInt(document.getElementById('fuzzy_temp_hot').value)  || 30;
        const mid  = Math.round((cold + hot) / 2);
        document.getElementById('previewCold').textContent        = cold;
        document.getElementById('previewHot').textContent         = hot;
        document.getElementById('previewNormalRange').textContent = cold + '–' + hot;
        document.getElementById('previewMid').textContent         = mid;
    }
    </script>
</body>

</html>
