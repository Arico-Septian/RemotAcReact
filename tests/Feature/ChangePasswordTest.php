<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Tester_1',
            'password' => Hash::make('OldPass123'),
        ]);

        $response = $this->actingAs($user)->post('/change-password', [
            'current_password' => 'OldPass123',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        $response->assertSessionHas('success', 'Password changed successfully.');

        $user->refresh();

        $this->assertTrue(Hash::check('NewPass123', $user->password));
        $this->assertFalse(Hash::check('OldPass123', $user->password));
        $this->assertDatabaseHas('user_logs', [
            'user_id' => $user->id,
            'activity' => 'change_password',
        ]);
    }
}
