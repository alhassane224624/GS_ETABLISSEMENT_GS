<?php

namespace Tests\Feature;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Creneau;
use App\Models\Matiere;
use App\Models\Planning;
use App\Models\Salle;
use App\Models\Stagiaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmploiDuTempsEtCahierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        // DemoSeeder connecte l'admin pour tracer les auteurs : on repart en visiteur
        $this->app['auth']->forgetGuards();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@demo.ma')->firstOrFail();
    }

    public function test_page_emploi_du_temps_s_affiche(): void
    {
        $classe = Creneau::firstOrFail()->classe;

        $this->actingAs($this->admin())
            ->get(route('emploi-du-temps.index', ['annee_id' => $classe->annee_scolaire_id, 'classe_id' => $classe->id]))
            ->assertOk()
            ->assertSee('Ajouter un créneau')
            ->assertSee('Générer les séances');
    }

    public function test_ajout_d_un_creneau_puis_generation_des_seances(): void
    {
        $annee = AnneeScolaire::where('is_active', true)->firstOrFail();
        $classe = Classe::where('annee_scolaire_id', $annee->id)->firstOrFail();
        $prof = User::where('role', 'professeur')->where('is_active', true)->firstOrFail();

        // Samedi soir : créneau peu susceptible d'entrer en conflit avec le jeu de démo
        $this->actingAs($this->admin())->post(route('emploi-du-temps.store'), [
            'annee_scolaire_id' => $annee->id,
            'classe_id' => $classe->id,
            'matiere_id' => Matiere::firstOrFail()->id,
            'professeur_id' => $prof->id,
            'salle_id' => Salle::firstOrFail()->id,
            'jour' => 6,
            'heure_debut' => '18:00',
            'heure_fin' => '19:00',
            'type_cours' => 'cours',
        ])->assertSessionHas('success');

        $creneau = Creneau::where('classe_id', $classe->id)->where('jour', 6)->where('heure_debut', 'like', '18:00%')->firstOrFail();

        $debut = now()->next(6)->toDateString();
        $this->post(route('emploi-du-temps.generer'), [
            'annee_scolaire_id' => $annee->id,
            'portee' => 'classe',
            'classe_id' => $classe->id,
            'date_debut' => $debut,
            'date_fin' => $debut,
        ])->assertSessionHas('success');

        $this->assertTrue(Planning::where('creneau_id', $creneau->id)->whereDate('date', $debut)->exists());
    }

    public function test_cahier_de_textes_stagiaire_s_affiche(): void
    {
        $user = User::where('email', 'stagiaire@demo.ma')->firstOrFail();
        $stagiaire = Stagiaire::where('user_id', $user->id)->firstOrFail();

        // Une séance passée renseignée et un devoir à venir, pour afficher les deux blocs
        Planning::where('classe_id', $stagiaire->classe_id)->whereDate('date', '<', now())->orderByDesc('date')->orderByDesc('heure_debut')->firstOrFail()->update([
            'appel_fait_at' => now(),
            'contenu_seance' => 'Chapitre 3 : les boucles',
            'devoirs' => 'Exercices 1 à 5',
            'devoirs_pour' => now()->addDays(2)->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('stagiaire.cahier-de-textes'))
            ->assertOk()
            ->assertSee('Chapitre 3 : les boucles')
            ->assertSee('Exercices 1 à 5');
    }

    public function test_messages_de_validation_en_francais(): void
    {
        $this->post('/login', ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email' => 'Le champ adresse e-mail est obligatoire.']);
    }

    public function test_fuseau_horaire_maroc(): void
    {
        $this->assertSame('Africa/Casablanca', config('app.timezone'));
        $this->assertSame(now('Africa/Casablanca')->format('Y-m-d H'), now()->format('Y-m-d H'));
    }
}
