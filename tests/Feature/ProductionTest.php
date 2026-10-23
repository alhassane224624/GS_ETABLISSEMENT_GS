<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_du_premier_administrateur(): void
    {
        $this->artisan('app:creer-admin')
            ->expectsQuestion('Nom complet', 'Direction')
            ->expectsQuestion('Adresse e-mail (identifiant de connexion)', 'direction@ecole.ma')
            ->expectsQuestion('Mot de passe (12 caractères minimum)', 'MotDePasse2026')
            ->expectsQuestion('Confirmez le mot de passe', 'MotDePasse2026')
            ->assertSuccessful();

        $admin = User::where('email', 'direction@ecole.ma')->firstOrFail();
        $this->assertSame('administrateur', $admin->role);
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_mot_de_passe_trop_faible_refuse(): void
    {
        $this->artisan('app:creer-admin')
            ->expectsQuestion('Nom complet', 'Direction')
            ->expectsQuestion('Adresse e-mail (identifiant de connexion)', 'direction@ecole.ma')
            ->expectsQuestion('Mot de passe (12 caractères minimum)', 'password')
            ->expectsQuestion('Confirmez le mot de passe', 'password')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'direction@ecole.ma']);
    }

    public function test_pas_de_donnees_de_demo_en_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'admin@demo.ma']);
    }
}
