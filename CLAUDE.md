# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SmartAC — a Laravel 13 IoT dashboard for remotely controlling air conditioners in multiple rooms via ESP32 devices over MQTT. Real-time status is pushed to the browser through Laravel Reverb (WebSockets).

## Common Commands

```bash
# Full dev environment (server + queue + vite, concurrently)
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
php artisan logs:clean             # deletes old user logs
```

The scheduler is defined in `routes/console.php` (Laravel 13 pattern). It automatically fires `device:check-status` and `ac:run-timer` every minute, and `logs:clean` daily at 07:00. Run `php artisan schedule:run` manually to execute the schedule once (used by cron jobs on production servers).

## Architecture

### IoT Communication Flow

1. **ESP32 devices** publish to MQTT topics (`broker.hivemq.com:1883`, public, no auth).
2. **`php artisan mqtt:subscribe`** (`MqttSubscribe` command) listens in an infinite loop and updates `Cache` + DB on each message.
3. **Cache** is the authoritative real-time source for device status (`device_{id}_last_seen`, `device_status_{id}`). DB values are a fallback.
4. **Laravel Reverb** broadcasts `DeviceStatusUpdated` events to the `device-status` WebSocket channel so the browser refreshes without polling.
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

Room names in topics are always `strtolower(trim())`. A device is considered **online** in two contexts:
- **Dashboard view** (DashboardController): `last_seen` within 30 seconds
- **API endpoint** (`/device-status`): `last_seen` within 15 seconds

### Role System

Three roles enforced by `RoleMiddleware` via `role:admin,operator` or `role:admin` route groups:

- **user** — read-only: dashboard, temperature, notifications
- **operator** — room/AC CRUD and control
- **admin** — everything above + user management, activity logs export

Auth middleware stack on protected routes: `auth` → `activity` (updates `last_activity`).

### Key Models

- `Room` — has `device_id` (maps to ESP32), `device_status`, `last_seen`, `floor`
- `AcUnit` — belongs to Room; has `ac_number`, `brand`, `timer_on`, `timer_off`
- `AcStatus` — one-to-one with AcUnit; stores `power`, `mode`, `set_temperature`, `fan_speed`, `swing`
- `RoomTemperature` — append-only time-series; use `RoomTemperature::normalizeRoomName()` and `latestByNormalizedRoom()` for lookups
- `Notification` — broadcast (null `user_id`) or per-user; static helpers `Notification::deviceOffline()`, `Notification::deviceOnline()`, `Notification::fuzzyAction()`, `Notification::fuzzyWarning()`, `Notification::fuzzyRecovery()` — semua deduplicate via Cache key per-room

### AC Control Flow

`AcControlController` → updates `AcStatus` → calls `MqttService::publish("room/{room}/ac/{n}/control", …, QoS 1, retain=true)` → `UserLog::create()`. All control actions are logged. The MQTT subscriber also echoes control messages back to update DB state on receipt.

### Timer System

`RunAcTimer` (`ac:run-timer`) runs every minute. It fires if `now` is within a ±30 s / +60 s window of `timer_on`/`timer_off`, guarded by Cache locks (`lock:timer_{type}_{id}_v{version}_{date_time}`) and a 60 s cooldown (`ac_cooldown_{id}`) to prevent double-execution.

### Frontend

Blade templates in `resources/views/`. Sidebar and bottom-nav are Blade components (`components/sidebar`, `components/bottom-nav`). No separate SPA — Alpine.js / vanilla JS handles live updates by polling JSON API endpoints.

## Key Endpoints

Read-only endpoints (authenticated, available to all roles):
- `GET /device-status` — room device online/offline status (15 s online threshold)
- `GET /temperature` or `/temperatures` — latest room temperatures
- `GET /temperature/history/{id}` — 24-hour temperature data grouped by hour
- `GET /notifications/recent` — recent notifications
- `GET /api/ac-status` — AC unit status with room relationships

AC control endpoints (`role:admin,operator`):
- `GET /ac/{id}/on`, `/ac/{id}/off`, `POST /ac/{id}/toggle` — power control
- `POST /ac/{id}/temp/{value}`, `/ac/{id}/mode/{mode}`, `/ac/{id}/fan-speed/{speed}`, `/ac/{id}/swing/{swing}` — settings
- `POST /ac/{id}/schedule` — set timer (`timer_on`/`timer_off`)

All endpoints return JSON for API calls, Blade views for page requests. All control actions are logged to `UserLog`.

## Queue System

`composer run dev` includes `queue:listen` which processes queued jobs. Jobs run synchronously in development (see `config/queue.php`). In production, the SMTP driver might queue mail jobs — ensure `queue:work` is running as a service.

## Testing

Tests live in `tests/` (Feature and Unit). Use `composer run test` to run all tests, or `php artisan test --filter=ClassName` for a specific class. The CI environment clears config cache before tests to ensure fresh state.
