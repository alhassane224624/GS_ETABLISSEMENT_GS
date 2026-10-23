<?php

namespace Tests\Feature;

use App\Models\Classe;
use App\Models\Stagiaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StagiaireFormulaireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->app['auth']->forgetGuards();
        $this->actingAs(User::where('email', 'admin@demo.ma')->firstOrFail());
    }

    private function classeLibre(): Classe
    {
        return Classe::whereHas('anneeScolaire', fn ($q) => $q->where('is_active', true))
            ->whereColumn('effectif_actuel', '<', 'effectif_max')->firstOrFail();
    }

    public function test_le_formulaire_propose_les_classes_disponibles(): void
    {
        $classe = $this->classeLibre();

        $this->get(route('stagiaires.create'))
            ->assertOk()
            ->assertSee('Informations personnelles')
            ->assertSee('value="' . $classe->id . '"', false);
    }

    public function test_enregistrement_affiche_un_message_de_confirmation(): void
    {
        $classe = $this->classeLibre();

        $this->followingRedirects()->post(route('stagiaires.store'), [
            'nom' => 'Camara',
            'prenom' => 'Mariame',
            'email' => 'mariame.camara@test.ma',
            'date_naissance' => '2005-03-12',
            'sexe' => 'F',
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_id,
            'classe_id' => $classe->id,
            'frais_inscription' => '399.84',
            'frais_payes' => '0',
        ])->assertOk()->assertSee('Mariame Camara a été créé', false)->assertSee('mot de passe provisoire');

        $stagiaire = Stagiaire::where('email', 'mariame.camara@test.ma')->firstOrFail();
        $this->assertSame($classe->id, (int) $stagiaire->classe_id);
        $this->assertNotEmpty($stagiaire->matricule); // généré automatiquement
    }

    public function test_date_de_naissance_future_message_en_francais(): void
    {
        $this->post(route('stagiaires.store'), [
            'nom' => 'Camara',
            'prenom' => 'Mariame',
            'email' => 'mariame2@test.ma',
            'date_naissance' => now()->toDateString(),
            'filiere_id' => $this->classeLibre()->filiere_id,
        ])->assertSessionHasErrors(['date_naissance' => 'La date de naissance doit être antérieure à aujourd\'hui.']);
    }

    public function test_decocher_compte_actif_desactive_le_compte(): void
    {
        $stagiaire = Stagiaire::whereNotNull('user_id')->where('is_active', true)->firstOrFail();

        $this->put(route('stagiaires.update', $stagiaire), [
            'nom' => $stagiaire->nom,
            'prenom' => $stagiaire->prenom,
            'matricule' => $stagiaire->matricule,
            'email' => $stagiaire->email,
            'filiere_id' => $stagiaire->filiere_id,
            'niveau_id' => $stagiaire->niveau_id,
            'classe_id' => $stagiaire->classe_id,
            'statut' => $stagiaire->statut,
            'frais_payes' => '0',
            'is_active' => '0', // case décochée : seul le champ caché est envoyé
        ])->assertSessionHasNoErrors();

        $this->assertFalse((bool) $stagiaire->fresh()->is_active);
        $this->assertFalse((bool) $stagiaire->user->fresh()->is_active);
    }

    public function test_fiche_admin_dans_l_espace_administration(): void
    {
        $stagiaire = Stagiaire::firstOrFail();

        $this->get(route('stagiaires.show', $stagiaire))
            ->assertOk()
            ->assertSee('Fiche stagiaire')
            ->assertSee('Accès parents')
            ->assertSee(route('stagiaires.edit', $stagiaire), false)
            ->assertDontSee('Espace Stagiaire');
    }

    public function test_lier_un_parent_puis_le_relier_apres_retrait(): void
    {
        $stagiaire = Stagiaire::firstOrFail();
        $donnees = ['nom' => 'Alhassane Diané', 'email' => 'parent.test@demo.ma', 'lien' => 'pere'];

        $this->post(route('stagiaires.parents.lier', $stagiaire), $donnees)
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'mot de passe provisoire'));

        $parent = User::where('email', 'parent.test@demo.ma')->firstOrFail();
        $this->assertTrue($parent->enfants()->whereKey($stagiaire->id)->exists());

        // Retrait du seul enfant : compte désactivé, puis réactivé quand on le relie
        $this->delete(route('stagiaires.parents.delier', [$stagiaire, $parent]));
        $this->assertFalse((bool) $parent->fresh()->is_active);

        $this->post(route('stagiaires.parents.lier', $stagiaire), $donnees);
        $this->assertTrue((bool) $parent->fresh()->is_active);
    }

    public function test_page_de_modification_s_affiche(): void
    {
        $stagiaire = Stagiaire::firstOrFail();

        $this->get(route('stagiaires.edit', $stagiaire))
            ->assertOk()
            ->assertSee('Compte actif')
            ->assertSee($stagiaire->matricule);
    }
}
