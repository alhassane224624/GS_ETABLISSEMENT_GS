<?php

namespace Tests\Feature;

use App\Models\Bulletin;
use App\Models\Note;
use App\Models\Periode;
use App\Models\Stagiaire;
use App\Models\User;
use App\Notifications\Alerte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Suivi des parents (notes publiées, notifications) et contrôle des notes en attente par l'administration
 */
class SuiviParentsTest extends TestCase
{
    use RefreshDatabase;

    private User $prof;
    private User $parent;
    private Stagiaire $stagiaire;
    private int $matiereId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->app['auth']->forgetGuards();

        // Un professeur qui enseigne une matière dans la filière d'un stagiaire actif rattaché à une classe
        $aff = DB::table('professeur_matiere')->where('is_active', true)->get()->first(
            fn ($a) => Stagiaire::where('filiere_id', $a->filiere_id)->whereNotNull('classe_id')->whereNotNull('user_id')->where('is_active', true)->exists()
        );
        $this->prof = User::findOrFail($aff->professeur_id);
        $this->matiereId = $aff->matiere_id;
        $this->stagiaire = Stagiaire::where('filiere_id', $aff->filiere_id)->whereNotNull('classe_id')->whereNotNull('user_id')->where('is_active', true)->firstOrFail();

        $this->parent = User::where('email', 'parent@demo.ma')->firstOrFail();
        $this->parent->update(['is_active' => true]);
        $this->parent->enfants()->syncWithoutDetaching([$this->stagiaire->id => ['lien' => 'mere']]);

        // Période active, bulletin de la période non validé : le prof peut saisir
        Periode::query()->update(['is_active' => false]);
        Periode::whereHas('anneeScolaire', fn ($q) => $q->where('is_active', true))->firstOrFail()->update(['is_active' => true]);
        DB::table('bulletins')->where('stagiaire_id', $this->stagiaire->id)->update(['validated_at' => null]);
    }

    private function saisirNote(float $valeur): void
    {
        $this->actingAs($this->prof)->post(route('professeur.stagiaires.notes.store', $this->stagiaire), [
            'matiere_id' => $this->matiereId, 'note' => $valeur, 'type_note' => 'ds', 'note_sur' => 20,
        ])->assertSessionHas('success');
    }

    private function publier(): void
    {
        $this->actingAs($this->prof)->post(route('professeur.publications.store'), [
            'type' => 'resultats', 'classe_id' => $this->stagiaire->classe_id, 'matiere_id' => $this->matiereId, 'titre' => 'Résultats DS',
        ])->assertSessionHasNoErrors();
    }

    public function test_le_parent_voit_les_notes_publiees_seulement(): void
    {
        $this->saisirNote(13.75);

        $this->actingAs($this->parent)->get(route('parent.enfant', $this->stagiaire))
            ->assertOk()->assertDontSee('13,75');

        $this->publier();

        $this->actingAs($this->parent)->get(route('parent.enfant', $this->stagiaire))
            ->assertOk()->assertSee('13,75');
    }

    public function test_le_parent_est_notifie_des_resultats_publies(): void
    {
        Notification::fake();
        $this->saisirNote(15);
        Notification::assertNotSentTo($this->parent, Alerte::class);

        $this->publier();

        Notification::assertSentTo($this->parent, Alerte::class, fn (Alerte $n) => $n->titre === 'Nouveaux résultats' && str_contains($n->message, '15/20'));
    }

    public function test_le_parent_est_notifie_d_une_absence(): void
    {
        Notification::fake();

        $this->actingAs($this->prof)->post(route('professeur.presences.marquer'), [
            'stagiaire_id' => $this->stagiaire->id, 'date' => now()->toDateString(), 'type' => 'journee',
        ])->assertSessionHas('success');

        Notification::assertSentTo($this->parent, Alerte::class, fn (Alerte $n) => $n->titre === 'Absence' && !$n->parMail);
    }

    public function test_le_parent_est_notifie_d_un_bulletin_valide(): void
    {
        Notification::fake();
        $bulletin = Bulletin::where('stagiaire_id', $this->stagiaire->id)->firstOrFail();
        $bulletin->update(['validated_at' => null]);

        $this->actingAs(User::where('email', 'admin@demo.ma')->firstOrFail())
            ->patch(route('bulletins.validate', $bulletin))->assertSessionHas('success');

        Notification::assertSentTo($this->parent, Alerte::class, fn (Alerte $n) => $n->titre === 'Bulletin disponible');
    }

    public function test_e_mail_envoye_seulement_si_active(): void
    {
        Notification::fake();
        config(['services.parents.mail' => true]);

        $this->actingAs($this->prof)->post(route('professeur.presences.marquer'), [
            'stagiaire_id' => $this->stagiaire->id, 'date' => now()->toDateString(), 'type' => 'journee',
        ]);

        Notification::assertSentTo($this->parent, Alerte::class, fn (Alerte $n, array $canaux) => in_array('mail', $canaux));
    }

    public function test_page_notifications_du_parent(): void
    {
        $this->actingAs($this->prof)->post(route('professeur.presences.marquer'), [
            'stagiaire_id' => $this->stagiaire->id, 'date' => now()->toDateString(), 'type' => 'journee',
        ]);

        $this->actingAs($this->parent)->get(route('parent.notifications'))
            ->assertOk()->assertSee('Absence')->assertSee($this->stagiaire->prenom);
        $this->assertSame(0, $this->parent->unreadNotifications()->count(), 'Marquées comme lues après consultation');

        $this->actingAs($this->parent)->get(route('notifications.index'))->assertRedirect(route('parent.notifications'));
    }

    public function test_ecran_admin_des_notes_en_attente_et_relance(): void
    {
        $this->saisirNote(9);
        $admin = User::where('email', 'admin@demo.ma')->firstOrFail();

        $this->actingAs($admin)->get(route('notes.en-attente'))
            ->assertOk()->assertSee($this->prof->name)->assertSee('Relancer')
            ->assertDontSee('9,00'); // le volume seulement, pas la note

        $this->actingAs($admin)->get(route('bulletins.index'))->assertSee('ne sont pas encore publiées');

        $this->actingAs($admin)->post(route('notes.en-attente.relancer', $this->prof))->assertSessionHas('success');
        $this->assertSame('Notes à publier', $this->prof->notifications()->firstOrFail()->data['title']);

        // Le professeur lit le rappel dans son propre espace
        $this->actingAs($this->prof)->get(route('notifications.index'))
            ->assertOk()->assertSee('Notes à publier')->assertSee(route('professeur.publications.index'), false);

        $this->publier();
        $this->actingAs($admin)->get(route('notes.en-attente'))->assertSee('Aucune note en attente');
    }

    public function test_le_stagiaire_consulte_ses_notifications_dans_son_espace(): void
    {
        $this->actingAs($this->stagiaire->user)->get(route('notifications.index'))
            ->assertOk()->assertSee('Espace Stagiaire', false);
    }
}
