<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RoomsOverviewViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_rooms_overview_renders_history_controls(): void
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Server Room',
            'device_id' => 'esp32-server-room',
            'floor' => '1',
            'device_status' => 'online',
            'last_seen' => now(),
        ]);

        Cache::put("device_{$room->device_id}_last_seen", now(), 30);
        Cache::put("device_status_{$room->device_id}", 'online', 30);

        $response = $this
            ->actingAs($user)
            ->get('/rooms/overview');

        $response
            ->assertOk()
            ->assertSee('historyRange')
            ->assertSee('Today')
            ->assertSee('fetchHistoryStatus')
            ->assertSee('Temp: No data');
    }
}
