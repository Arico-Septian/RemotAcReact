<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTrendTest extends TestCase
{
    use RefreshDatabase;

    public function test_temperature_trend_today_starts_at_midnight(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-24 15:30:00'));

        try {
            $user = User::factory()->create();

            $response = $this
                ->actingAs($user)
                ->getJson('/temperature/trend?range=today&limit=5');

            $response->assertOk();

            $payload = $response->json();

            $this->assertSame('today', $payload['range']);
            $this->assertSame(60, $payload['interval_minutes']);
            $this->assertCount(16, $payload['labels']);
            $this->assertSame('00:00', $payload['labels'][0]);
            $this->assertSame('15:00', $payload['labels'][15]);
        } finally {
            Carbon::setTestNow();
        }
    }
}
