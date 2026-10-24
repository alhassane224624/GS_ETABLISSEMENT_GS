<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompteursSessionExpireeTest extends TestCase
{
    use RefreshDatabase;

    public function test_compteur_sans_session_repond_401_sans_memoriser_l_adresse(): void
    {
        $this->get('/messages/unread-count')->assertUnauthorized();
        $this->assertNull(session('url.intended'));
    }

    public function test_apres_connexion_on_arrive_sur_le_tableau_de_bord(): void
    {
        $user = User::factory()->create(['role' => 'administrateur']);

        $this->get('/messages/unread-count'); // interrogation en arrière-plan pendant la session expirée
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_une_vraie_page_est_toujours_rouverte_apres_connexion(): void
    {
        $user = User::factory()->create(['role' => 'administrateur']);

        $this->get('/stagiaires')->assertRedirect(route('login'));
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(url('/stagiaires'));
    }
}
