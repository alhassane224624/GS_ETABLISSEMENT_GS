<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_user_deactivated_during_session_is_logged_out(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $user->update(['is_active' => false]);

        $this->get('/messages')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
