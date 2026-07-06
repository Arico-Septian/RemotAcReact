# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SmartAC — a Laravel 13 IoT dashboard for remotely controlling air conditioners in multiple rooms via ESP32 devices over MQTT. Real-time status is pushed to the browser through Laravel Reverb (WebSockets). The frontend is Inertia.js + React + TypeScript (not Blade).

## Common Commands

```bash
# Full dev environment (server + vite + reverb, concurrently)
composer run dev

# MQTT listener — must run separately alongside composer dev
php artisan mqtt:subscribe

# Run tests
composer run test

# Run a single test file
php artisan test --filter=TestClassName

# Full fresh setup
composer run setup

# Database migrations
php artisan migrate

# Scheduled tasks (run manually)
php artisan device:check-status   # checks device online/offline
php artisan ac:run-timer           # fires scheduled AC on/off timers
php artisan fuzzy:run              # runs Mamdani fuzzy logic for AC adjustment
php artisan logs:clean             # deletes old user logs
php artisan notification:cleanup   # prunes old notifications
php artisan temperature:cleanup --days=7  # prunes old temperature records
php artisan device:list-active     # lists devices currently considered online
php artisan mqtt:cleanup-retained  # clears stale retained MQTT messages
```

The scheduler is defined in `routes/console.php` (Laravel 13 pattern). Every minute: `ac:run-timer`, `fuzzy:run`, and `device:check-status` (the last is gated by a `when()` clause so it only actually runs every `AppSetting::deviceCheckIntervalMinutes()` minutes, default 1). Daily: `logs:clean` at 07:00, `notification:cleanup` at 00:00, `temperature:cleanup` at 00:10; expired cache rows are purged at 00:20. Run `php artisan schedule:run` manually to execute once.

## Architecture

### Runtime-configurable settings (`AppSetting`)

Several values that look like they'd be constants are actually rows in the `app_settings` table, editable at runtime by an admin via `GET/PUT /settings` (`SettingsController`, `resources/js/Pages/Settings.tsx`) — **not** `.env`:

- **MQTT connection** — host/port/username/password (`AppSetting::mqttHost()`, `mqttPort()`, `mqttUsername()`, `mqttPassword()`). `MqttService` reads these exclusively; the `MQTT_*` vars in `.env`/`.env.example` are unused leftovers. TLS auto-enables only when the configured port is `8883`.
- **Sensor/device offline timeout** — `AppSetting::sensorOfflineSeconds()` (minutes × 60, default 3 min = 180 s). Both `Room::onlineThresholdSeconds()` and `Room::temperatureStaleSeconds()` delegate to this single setting, so device liveness and temperature staleness always move together. The `Room::ONLINE_THRESHOLD_SECONDS` / `TEMPERATURE_STALE_SECONDS` class constants (180) are legacy defaults, not what's actually read at runtime.
- **Device check interval** — `AppSetting::deviceCheckIntervalMinutes()` (default 1) — gates how often the `device:check-status` cron actually executes (see scheduler above).
- **Retention windows** — notification/temperature/activity-log retention days, consumed by `logs:clean`, `notification:cleanup`, `temperature:cleanup`.

When touching device-online, temperature-staleness, or MQTT-connection logic, check `AppSetting` first rather than assuming a hardcoded value.

### IoT Communication Flow

1. **ESP32 devices** publish to MQTT topics. Broker connection details come from `AppSetting` (see above), not `.env`. The ESP32 firmware (`esp`) must point at the same broker.
2. **`php artisan mqtt:subscribe`** (`MqttSubscribe` command) listens in an infinite loop and updates `Cache` + DB on each message.
3. **Cache** is the authoritative real-time source for device status (`device_{id}_last_seen`, `device_status_{id}`). DB values are a fallback.
4. **Laravel Reverb** broadcasts events to WebSocket channels so the browser refreshes without polling. Events: `DeviceStatusUpdated`, `RoomTemperatureUpdated`, `AcStatusUpdated`, `AcTimerUpdated`, `NotificationCreated`, `UserLogCreated`, `UserLogsCleared`, `RaspiTemperatureUpdated`, plus `RoomsChanged`, `AcUnitsChanged`, `UsersChanged` (these last three drive Inertia partial reloads — see Frontend below).
5. **Browser** also polls `/device-status`, `/temperature`, and `/notifications/recent` endpoints on a timer for resilience.

### MQTT Topic Scheme

| Direction | Topic | Purpose |
|-----------|-------|---------|
| Device → Server | `device/{id}/online` | Device boot announcement |
| Device → Server | `device/{id}/ping` / `device/{id}/heartbeat` | Keepalive (sets online, 60 s TTL) |
| Device → Server | `device/{id}/status` | LWT — payload `offline` marks device down |
| Device → Server | `room/{room}/ac/{n}/status` | AC state feedback from device |
| Server → Device | `device/{id}/config` | Sends room + AC list on reconnect |
| Server → Device | `room/{room}/ac/{n}/control` | AC control command (retained QoS 1) |

Room names in topics are always `strtolower(trim())`. A device is considered **online** when `last_seen` is within `Room::onlineThresholdSeconds()` (see `AppSetting` above; default 180 s) — sized for the 60 s device ping (3x). This is the source of truth for **device** liveness — used by every UI view (DashboardController, RoomController, AcUnitController, `/device-status`), by `RunFuzzyLogic`, and by `CheckDeviceStatus`, so on-screen status and offline notifications always agree.

**Temperature staleness** uses the same underlying setting via `Room::temperatureStaleSeconds()`: a room's latest `RoomTemperature` older than this is treated as offline/null. The ESP32 sends temperature **report-by-exception** — immediately when the reading changes ≥1 °C, otherwise a heartbeat every **60 s** — so the default 180 s ≈ 3× the slowest send rate. ESP32 firmware (`esp`) pings every **60 s** (`PING_INTERVAL`) and reads the DHT every 5 s (`SENSOR_INTERVAL`) but only publishes on change or on the 60 s heartbeat (`SENSOR_HEARTBEAT_INTERVAL`).

### Role System

Three roles enforced by `RoleMiddleware` via `role:admin,operator` or `role:admin` route groups:

- **user** — read-only: dashboard, temperature, notifications
- **operator** — room/AC CRUD and control
- **admin** — everything above + user management, activity logs export, `/settings`

Auth middleware stack on protected routes: `auth` → `activity` (`UpdateLastActivity` middleware, throttled to update at most once every 60 seconds).

### Key Models

- `Room` — has `device_id` (maps to ESP32), `device_status`, `last_seen`, `floor`
- `AcUnit` — belongs to Room; has `ac_number`, `brand`, `timer_on`, `timer_off`
- `AcStatus` — one-to-one with AcUnit; stores `power`, `mode`, `set_temperature`, `fan_speed`, `swing`
- `RoomTemperature` — append-only time-series; use `RoomTemperature::normalizeRoomName()` and `latestByNormalizedRoom()` for lookups
- `Notification` — broadcast (null `user_id`) or per-user; static helpers `Notification::deviceOffline()`, `Notification::deviceOnline()`, `Notification::fuzzyAction()`, `Notification::fuzzyWarning()`, `Notification::fuzzyRecovery()` — all deduplicate via Cache key per-room (state TTL 30 days)
- `AppSetting` — key/value store backing the runtime-configurable settings described above; always go through its static accessors, never read `app_settings` rows directly

### AC Control Flow

`AcControlController` → updates `AcStatus` → calls `MqttService::publish("room/{room}/ac/{n}/control", …, QoS 1, retain=true)` → `UserLog::create()`. All control actions are logged. The MQTT subscriber also echoes control messages back to update DB state on receipt. AC control endpoints have a rate limit of **30 requests/minute per user**.

### Timer System

`RunAcTimer` (`ac:run-timer`) runs every minute. It fires if `now` is within a ±30 s window of `timer_on`/`timer_off`, guarded by Cache locks (`lock:timer_{type}_{id}_v{version}_{date_time}`) and a **5 s cooldown** (`ac_cooldown_{id}`) to prevent double-execution.

### Fuzzy Logic System

`RunFuzzyLogic` (`fuzzy:run`) runs every minute via `FuzzyMamdaniService`. It reads the latest room temperature, evaluates membership functions (dingin/normal/panas), and automatically adjusts AC setpoints. Results are logged as `Notification::fuzzyAction()` / `Notification::fuzzyWarning()` / `Notification::fuzzyRecovery()`. Fuzzy logic can also be triggered manually via `POST /rooms/{id}/ac/fuzzy/apply`.

### Frontend

Inertia.js v3 + React 19 + TypeScript — **not** Blade. `resources/views/app.blade.php` is the only Blade file left (the Inertia root shell). Pages live in `resources/js/Pages/*.tsx` (one per route, e.g. `Dashboard.tsx`, `RoomDetail.tsx`, `AcControl.tsx`), shared UI in `resources/js/Components/`, and `resources/js/Layouts/AppLayout.tsx` wraps authenticated pages. `HandleInertiaRequests` middleware shares `auth.user` and flash messages as default props on every response.

Live updates are wired per-page, not through a shared hook: pages call `window.Echo.channel(...).listen('.EventName', handler)` directly (Echo is configured once in `resources/js/bootstrap.js` for the Reverb broadcaster). Two patterns are used depending on the event:
- Fine-grained events (`RoomTemperatureUpdated`, `DeviceStatusUpdated`, etc.) update local component state directly from the payload.
- Coarse "something changed" events (`RoomsChanged`, `AcUnitsChanged`, `UsersChanged`) trigger an Inertia partial reload, e.g. `router.reload({ only: ['rooms'] })`, re-fetching just that prop from the controller.

## Key Endpoints

Read-only endpoints (authenticated, available to all roles):
- `GET /device-status` — room device online/offline status (`Room::onlineThresholdSeconds()`, default 180 s)
- `GET /temperature` or `/temperatures` — latest room temperatures
- `GET /temperature/history/{id}` — history for one room, grouped by `range` query param
- `GET /temperature/trend` — cross-room average temperature chart, `range` query param (`1h`/`1d`), cached 10 s
- `GET /notifications/recent` — recent notifications
- `GET /notifications/unread-count` — unread notification count
- `GET /api/ac-status` — AC unit status with room relationships
- `GET /users-online` — real-time online/offline user counts (admin only)

AC control endpoints (`role:admin,operator`, rate-limited 30 req/min):
- `GET /ac/{id}/on`, `/ac/{id}/off`, `POST /ac/{id}/toggle` — power control
- `POST /ac/{id}/temp/{value}`, `/ac/{id}/mode/{mode}`, `/ac/{id}/fan-speed/{speed}`, `/ac/{id}/swing/{swing}` — settings
- `POST /ac/{id}/schedule` — set timer (`timer_on`/`timer_off`)
- `POST /rooms/{id}/ac/bulk-power` — bulk power on/off for all ACs in a room
- `POST /rooms/{id}/ac/fuzzy/apply` — manually trigger fuzzy logic for a room
- `POST /notifications/read-all` — mark all notifications read

Admin-only (`role:admin`): `/users` CRUD, `/logs` (activity log) + bulk delete, `GET/PUT /settings` (retention/monitoring/MQTT config described above).

Page routes return Inertia responses (`Inertia::render(...)`); the JSON-only endpoints above return plain `response()->json(...)`/Eloquent collections. All control actions are logged to `UserLog`.

## Queue System

The queue driver is `sync` — jobs are processed immediately inline, no background worker needed. `composer run dev` no longer runs `queue:listen`.

## Testing

There is currently no `tests/` directory in this repo, though `phpunit.xml` still points at `tests/Unit` and `tests/Feature` and `composer run test` / `php artisan test --filter=ClassName` will work once tests are added. Don't assume test coverage exists for a given piece of code — check `tests/` first.
