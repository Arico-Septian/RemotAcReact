<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Notification;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SettingsRetentionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_update_cleanup_retention_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/settings', [
            AppSetting::NOTIFICATION_RETENTION_DAYS => 5,
            AppSetting::TEMPERATURE_RETENTION_DAYS => 14,
            AppSetting::ACTIVITY_LOG_RETENTION_DAYS => 21,
            AppSetting::SENSOR_OFFLINE_MINUTES => 4,
            AppSetting::DEVICE_CHECK_INTERVAL_MINUTES => 5,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('app_settings', [
            'key' => AppSetting::NOTIFICATION_RETENTION_DAYS,
            'value' => '5',
        ]);
        $this->assertDatabaseHas('app_settings', [
            'key' => AppSetting::TEMPERATURE_RETENTION_DAYS,
            'value' => '14',
        ]);
        $this->assertDatabaseHas('app_settings', [
            'key' => AppSetting::ACTIVITY_LOG_RETENTION_DAYS,
            'value' => '21',
        ]);
        $this->assertDatabaseHas('app_settings', [
            'key' => AppSetting::SENSOR_OFFLINE_MINUTES,
            'value' => '4',
        ]);
        $this->assertDatabaseHas('app_settings', [
            'key' => AppSetting::DEVICE_CHECK_INTERVAL_MINUTES,
            'value' => '5',
        ]);
    }

    public function test_notification_cleanup_uses_saved_retention_days(): void
    {
        AppSetting::query()->create([
            'key' => AppSetting::NOTIFICATION_RETENTION_DAYS,
            'value' => '5',
        ]);

        $oldNotification = Notification::query()->create([
            'type' => 'system',
            'severity' => 'info',
            'title' => 'Old',
            'message' => 'Old notification',
        ]);
        $oldNotification->timestamps = false;
        $oldNotification->forceFill([
            'created_at' => now()->subDays(6),
            'updated_at' => now()->subDays(6),
        ])->save();

        $freshNotification = Notification::query()->create([
            'type' => 'system',
            'severity' => 'info',
            'title' => 'Fresh',
            'message' => 'Fresh notification',
        ]);
        $freshNotification->timestamps = false;
        $freshNotification->forceFill([
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4),
        ])->save();

        $this->artisan('notification:cleanup')->assertSuccessful();

        $this->assertModelMissing($oldNotification);
        $this->assertModelExists($freshNotification);
    }

    public function test_device_status_checker_uses_saved_sensor_offline_timeout(): void
    {
        AppSetting::query()->create([
            'key' => AppSetting::SENSOR_OFFLINE_MINUTES,
            'value' => '1',
        ]);

        $room = Room::query()->create([
            'name' => 'lab_test',
            'device_id' => 'esp_test',
            'device_status' => 'online',
            'last_seen' => now()->subSeconds(75),
        ]);

        $this->artisan('device:check-status')->assertSuccessful();

        $this->assertSame('offline', $room->fresh()->device_status);
    }
}
