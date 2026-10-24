<?php

namespace Tests\Feature;

use App\Models\Annonce;
use App\Models\Classe;
use App\Models\Stagiaire;
use App\Models\User;
use App\Notifications\Alerte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnoncesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $comptable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->app['auth']->forgetGuards();
        Storage::fake('local');
        $this->admin = User::where('email', 'admin@demo.ma')->firstOrFail();
        $this->comptable = User::where('email', 'comptable@demo.ma')->firstOrFail();
    }

    private function publier(User $auteur, array $donnees = []): Annonce
    {
        $this->actingAs($auteur)->post(route('gestion.annonces.store'), array_merge([
            'titre' => 'Réunion parents-professeurs',
            'contenu' => 'Rendez-vous samedi à 10h dans la grande salle.',
            'importance' => 'important',
            'roles' => array_keys(Annonce::ROLES),
            'portee' => 'tous',
        ], $donnees))->assertSessionHasNoErrors();

        return Annonce::latest('id')->firstOrFail();
    }

    private function stagiaireDemo(): User
    {
        return User::where('email', 'stagiaire@demo.ma')->firstOrFail();
    }

    public function test_pages_de_gestion_et_de_consultation(): void
    {
        $this->actingAs($this->admin)->get(route('gestion.annonces.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('gestion.annonces.create'))->assertOk();
        $this->actingAs($this->comptable)->get(route('gestion.annonces.create'))->assertOk();

        foreach (['stagiaire@demo.ma', 'parent@demo.ma', 'comptable@demo.ma'] as $email) {
            $this->actingAs(User::where('email', $email)->firstOrFail())->get(route('annonces.index'))->assertOk();
        }
    }

    public function test_professeurs_et_stagiaires_n_ont_pas_acces_a_la_gestion(): void
    {
        $prof = User::where('role', 'professeur')->firstOrFail();
        $this->actingAs($prof)->get(route('gestion.annonces.create'))->assertForbidden();
        $this->actingAs($this->stagiaireDemo())->post(route('gestion.annonces.store'), ['titre' => 'x'])->assertForbidden();
    }

    public function test_annonce_importante_notifiee_et_affichee_en_bandeau(): void
    {
        Notification::fake();
        $annonce = $this->publier($this->admin, ['fichier' => UploadedFile::fake()->create('circulaire.pdf', 80, 'application/pdf')]);

        $stagiaire = $this->stagiaireDemo();
        Notification::assertSentTo($stagiaire, Alerte::class);
        Notification::assertSentTo(User::where('email', 'parent@demo.ma')->first(), Alerte::class);
        $this->assertNotNull($annonce->fresh()->notifiee_at);

        // Bandeau sur les pages tant que l'annonce n'est pas lue
        $this->actingAs($stagiaire)->get(route('stagiaire.dashboard'))->assertSee('Réunion parents-professeurs');

        $this->actingAs($stagiaire)->get(route('annonces.show', $annonce))->assertOk()->assertSee('grande salle');
        $this->actingAs($stagiaire)->get(route('annonces.fichier', $annonce))->assertOk();
        $this->assertTrue($annonce->estLuePar($stagiaire));

        // Lue : le bandeau disparaît
        $this->assertTrue(Annonce::aSignaler($stagiaire)->isEmpty());
    }

    public function test_annonce_information_non_notifiee(): void
    {
        Notification::fake();
        $this->publier($this->comptable, ['importance' => 'info', 'titre' => 'Nouveaux horaires de la caisse']);

        Notification::assertNothingSent();
        $this->actingAs($this->stagiaireDemo())->get(route('annonces.index'))->assertSee('Nouveaux horaires de la caisse');
    }

    public function test_ciblage_par_classe(): void
    {
        $stagiaire = Stagiaire::where('email', 'stagiaire@demo.ma')->firstOrFail();
        $autre = Stagiaire::where('classe_id', '!=', $stagiaire->classe_id)->whereNotNull('user_id')->whereHas('user', fn ($q) => $q->where('is_active', true))->firstOrFail();

        $annonce = $this->publier($this->admin, [
            'titre' => 'Sortie pédagogique',
            'roles' => ['stagiaire', 'parent'],
            'portee' => 'classes',
            'classe_ids' => [$stagiaire->classe_id],
        ]);

        $this->assertTrue($annonce->estDestinataire($stagiaire->user));
        $this->assertTrue($annonce->estDestinataire(User::where('email', 'parent@demo.ma')->first()));
        $this->assertFalse($annonce->estDestinataire($autre->user));

        $this->actingAs($autre->user)->get(route('annonces.show', $annonce))->assertNotFound();
        $this->actingAs($autre->user)->get(route('annonces.index'))->assertDontSee('Sortie pédagogique');
    }

    public function test_annonce_programmee_diffusee_a_l_heure(): void
    {
        Notification::fake();
        $annonce = $this->publier($this->admin, ['titre' => 'Fermeture exceptionnelle', 'publie_le' => now()->addDay()->format('Y-m-d\TH:i')]);

        Notification::assertNothingSent();
        $this->actingAs($this->stagiaireDemo())->get(route('annonces.index'))->assertDontSee('Fermeture exceptionnelle');

        $annonce->update(['publie_le' => now()->subMinute()]);
        $this->artisan('annonces:diffuser')->assertSuccessful();

        Notification::assertSentTo($this->stagiaireDemo(), Alerte::class);
        $this->actingAs($this->stagiaireDemo())->get(route('annonces.index'))->assertSee('Fermeture exceptionnelle');
    }

    public function test_annonce_expiree_n_est_plus_visible(): void
    {
        $annonce = $this->publier($this->admin, ['titre' => 'Inscriptions examens', 'importance' => 'info']);
        $annonce->update(['expire_le' => now()->subMinute()]);

        $this->actingAs($this->stagiaireDemo())->get(route('annonces.index'))->assertDontSee('Inscriptions examens');
        $this->actingAs($this->stagiaireDemo())->get(route('annonces.show', $annonce))->assertNotFound();
    }

    public function test_suivi_des_lectures(): void
    {
        $annonce = $this->publier($this->admin, ['importance' => 'info']);
        $this->actingAs($this->stagiaireDemo())->get(route('annonces.show', $annonce));

        $this->actingAs($this->admin)->get(route('gestion.annonces.show', $annonce))
            ->assertOk()->assertSee('Pas encore lue par');
        $this->assertSame(1, $annonce->lectures()->count());
    }

    public function test_le_comptable_ne_modifie_que_ses_annonces(): void
    {
        $deLAdmin = $this->publier($this->admin, ['importance' => 'info']);
        $duComptable = $this->publier($this->comptable, ['importance' => 'info', 'titre' => 'Rappel paiements']);

        $this->actingAs($this->comptable)->get(route('gestion.annonces.edit', $deLAdmin))->assertForbidden();
        $this->actingAs($this->comptable)->delete(route('gestion.annonces.destroy', $deLAdmin))->assertForbidden();
        $this->actingAs($this->comptable)->get(route('gestion.annonces.edit', $duComptable))->assertOk();

        // L'administration peut tout gérer
        $this->actingAs($this->admin)->delete(route('gestion.annonces.destroy', $duComptable))->assertRedirect();
        $this->assertModelMissing($duComptable);
    }
}
