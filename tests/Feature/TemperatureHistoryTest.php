<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomTemperature;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemperatureHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_temperature_history_defaults_to_today_from_midnight(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-24 15:30:00'));

        try {
            $user = User::factory()->create();
            $room = Room::create([
                'name' => 'Server Room',
                'device_id' => 'esp32-server-room',
                'floor' => '1',
                'device_status' => 'online',
                'last_seen' => now(),
            ]);

            RoomTemperature::forceCreate([
                'room' => RoomTemperature::normalizeRoomName($room->name),
                'temperature' => 20.5,
                'created_at' => Carbon::parse('2026-05-23 16:15:00'),
                'updated_at' => Carbon::parse('2026-05-23 16:15:00'),
            ]);
            RoomTemperature::forceCreate([
                'room' => RoomTemperature::normalizeRoomName($room->name),
                'temperature' => 18,
                'created_at' => Carbon::parse('2026-05-23 15:45:00'),
                'updated_at' => Carbon::parse('2026-05-23 15:45:00'),
            ]);
            RoomTemperature::forceCreate([
                'room' => RoomTemperature::normalizeRoomName($room->name),
                'temperature' => 25.5,
                'created_at' => Carbon::parse('2026-05-24 15:15:00'),
                'updated_at' => Carbon::parse('2026-05-24 15:15:00'),
            ]);

            $response = $this
                ->actingAs($user)
                ->getJson("/temperature/history/{$room->id}");

            $response->assertOk();

            $history = $response->json();

            $this->assertCount(16, $history);
            $this->assertSame('00:00', $history[0]['time']);
            $this->assertNull($history[0]['temp']);
            $this->assertSame('15:00', $history[15]['time']);
            $this->assertSame(25.5, $history[15]['temp']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_temperature_history_can_be_filtered_to_one_hour(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-24 15:30:00'));

        try {
            $user = User::factory()->create();
            $room = Room::create([
                'name' => 'Server Room',
                'device_id' => 'esp32-server-room',
                'floor' => '1',
                'device_status' => 'online',
                'last_seen' => now(),
            ]);

            RoomTemperature::forceCreate([
                'room' => RoomTemperature::normalizeRoomName($room->name),
                'temperature' => 26.5,
                'created_at' => Carbon::parse('2026-05-24 15:26:00'),
                'updated_at' => Carbon::parse('2026-05-24 15:26:00'),
            ]);
            RoomTemperature::forceCreate([
                'room' => RoomTemperature::normalizeRoomName($room->name),
                'temperature' => 31.5,
                'created_at' => Carbon::parse('2026-05-24 14:20:00'),
                'updated_at' => Carbon::parse('2026-05-24 14:20:00'),
            ]);

            $response = $this
                ->actingAs($user)
                ->getJson("/temperature/history/{$room->id}?range=1h");

            $response->assertOk();

            $history = $response->json();

            $this->assertCount(12, $history);
            $this->assertSame('14:35', $history[0]['time']);
            $this->assertSame('15:30', $history[11]['time']);
            $this->assertSame(26.5, $history[10]['temp']);
            $this->assertNull($history[0]['temp']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_temperature_history_can_be_filtered_to_six_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-24 15:30:00'));

        try {
            $user = User::factory()->create();
            $room = Room::create([
                'name' => 'Server Room',
                'device_id' => 'esp32-server-room',
                'floor' => '1',
                'device_status' => 'online',
                'last_seen' => now(),
            ]);

            RoomTemperature::forceCreate([
                'room' => RoomTemperature::normalizeRoomName($room->name),
                'temperature' => 27.5,
                'created_at' => Carbon::parse('2026-05-24 15:20:00'),
                'updated_at' => Carbon::parse('2026-05-24 15:20:00'),
            ]);

            $response = $this
                ->actingAs($user)
                ->getJson("/temperature/history/{$room->id}?range=6h");

            $response->assertOk();

            $history = $response->json();

            $this->assertCount(24, $history);
            $this->assertSame('09:45', $history[0]['time']);
            $this->assertSame('15:30', $history[23]['time']);
            $this->assertSame(27.5, $history[22]['temp']);
        } finally {
            Carbon::setTestNow();
        }
    }
}
