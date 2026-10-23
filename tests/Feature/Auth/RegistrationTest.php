<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    // L'inscription libre de Breeze est désactivée : /register renvoie vers la demande d'inscription stagiaire
    public function test_register_redirects_to_stagiaire_inscription(): void
    {
        $this->get('/register')->assertRedirect('/inscription-stagiaire');
    }

    public function test_stagiaire_inscription_form_can_be_rendered(): void
    {
        $this->get('/inscription-stagiaire')->assertStatus(200);
    }

    public function test_posting_to_register_does_not_create_an_account(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
