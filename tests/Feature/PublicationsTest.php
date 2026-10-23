<?php

namespace Tests\Feature;

use App\Models\Classe;
use App\Models\Note;
use App\Models\Periode;
use App\Models\Publication;
use App\Models\Rendu;
use App\Models\Stagiaire;
use App\Models\User;
use App\Notifications\PublicationCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicationsTest extends TestCase
{
    use RefreshDatabase;

    private User $prof;
    private Classe $classe;
    private int $matiereId;
    private Stagiaire $stagiaire;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->app['auth']->forgetGuards();
        Storage::fake('local');

        // Un professeur, une classe de l'année active dans sa filière, une matière qu'il y enseigne, un stagiaire de la classe
        $affectation = DB::table('professeur_matiere')->where('is_active', true)->get()->first(function ($a) {
            return Classe::where('filiere_id', $a->filiere_id)
                ->whereHas('anneeScolaire', fn ($q) => $q->where('is_active', true))
                ->whereHas('stagiaires', fn ($q) => $q->where('is_active', true)->whereNotNull('user_id'))
                ->exists();
        });
        $this->prof = User::findOrFail($affectation->professeur_id);
        $this->matiereId = $affectation->matiere_id;
        $this->classe = Classe::where('filiere_id', $affectation->filiere_id)
            ->whereHas('anneeScolaire', fn ($q) => $q->where('is_active', true))
            ->whereHas('stagiaires', fn ($q) => $q->where('is_active', true)->whereNotNull('user_id'))
            ->firstOrFail();
        $this->stagiaire = Stagiaire::where('classe_id', $this->classe->id)->where('is_active', true)->whereNotNull('user_id')->firstOrFail();
    }

    private function publierDevoir(array $extra = []): Publication
    {
        $this->actingAs($this->prof)->post(route('professeur.publications.store'), array_merge([
            'type' => 'devoir',
            'classe_id' => $this->classe->id,
            'matiere_id' => $this->matiereId,
            'titre' => 'Exercices chapitre 3',
            'contenu' => 'Faire les exercices 1 à 5.',
            'fichier' => UploadedFile::fake()->create('enonce.pdf', 120, 'application/pdf'),
            'date_limite' => now()->addWeek()->format('Y-m-d\TH:i'),
            'accepte_rendu' => '1',
        ], $extra))->assertSessionHasNoErrors();

        return Publication::latest('id')->firstOrFail();
    }

    public function test_pages_du_module_s_affichent(): void
    {
        $this->actingAs($this->prof)->get(route('professeur.publications.index'))->assertOk();
        $this->actingAs($this->prof)->get(route('professeur.publications.create'))->assertOk()->assertSee($this->classe->nom);
        $this->actingAs($this->stagiaire->user)->get(route('stagiaire.publications.index'))->assertOk();
    }

    public function test_devoir_publie_visible_et_notifie_a_la_classe(): void
    {
        Notification::fake();
        $devoir = $this->publierDevoir();

        Notification::assertSentTo($this->stagiaire->user, PublicationCreated::class);
        Storage::disk('local')->assertExists($devoir->fichier_path);

        $this->actingAs($this->stagiaire->user)->get(route('stagiaire.publications.index'))->assertSee('Exercices chapitre 3');
        $this->actingAs($this->stagiaire->user)->get(route('stagiaire.publications.show', $devoir))->assertOk()->assertSee('Déposer ma copie');
        $this->actingAs($this->stagiaire->user)->get(route('publications.fichier', $devoir))->assertOk();
    }

    public function test_un_stagiaire_d_une_autre_classe_n_y_a_pas_acces(): void
    {
        $devoir = $this->publierDevoir();
        $autre = Stagiaire::where('classe_id', '!=', $this->classe->id)->whereNotNull('classe_id')->whereNotNull('user_id')->firstOrFail();

        $this->actingAs($autre->user)->get(route('stagiaire.publications.show', $devoir))->assertForbidden();
        $this->actingAs($autre->user)->get(route('publications.fichier', $devoir))->assertForbidden();
    }

    public function test_depot_remplacement_et_suivi_par_le_prof(): void
    {
        $devoir = $this->publierDevoir();

        $this->actingAs($this->stagiaire->user)->post(route('stagiaire.publications.rendu', $devoir), [
            'fichier' => UploadedFile::fake()->create('copie-v1.pdf', 50, 'application/pdf'),
        ])->assertSessionHas('success', 'Votre copie a bien été déposée.');
        $premier = Rendu::firstOrFail()->fichier_path;

        $this->actingAs($this->stagiaire->user)->post(route('stagiaire.publications.rendu', $devoir), [
            'fichier' => UploadedFile::fake()->create('copie-v2.pdf', 50, 'application/pdf'),
        ])->assertSessionHas('success', 'Votre copie a été remplacée.');

        $rendu = Rendu::firstOrFail();
        $this->assertSame(1, Rendu::count());
        $this->assertSame('copie-v2.pdf', $rendu->fichier_nom);
        Storage::disk('local')->assertMissing($premier);

        $this->actingAs($this->prof)->get(route('professeur.publications.show', $devoir))
            ->assertOk()->assertSee('1 / ')->assertSee('Rendu');
        $this->actingAs($this->prof)->get(route('rendus.fichier', $rendu))->assertOk();
    }

    public function test_depot_apres_la_date_limite_marque_en_retard(): void
    {
        $devoir = $this->publierDevoir();
        $devoir->update(['date_limite' => now()->subHour()]);

        $this->actingAs($this->stagiaire->user)->post(route('stagiaire.publications.rendu', $devoir), [
            'fichier' => UploadedFile::fake()->create('copie.pdf', 50, 'application/pdf'),
        ])->assertSessionHas('success', fn ($m) => str_contains($m, 'en retard'));

        $this->assertTrue(Rendu::firstOrFail()->estEnRetard());
    }

    public function test_notes_du_prof_masquees_jusqu_a_la_publication_des_resultats(): void
    {
        Periode::query()->update(['is_active' => false]);
        Periode::whereHas('anneeScolaire', fn ($q) => $q->where('is_active', true))->firstOrFail()->update(['is_active' => true]);
        // Bulletin non validé pour pouvoir saisir
        DB::table('bulletins')->where('stagiaire_id', $this->stagiaire->id)->update(['validated_at' => null]);

        $this->actingAs($this->prof)->post(route('professeur.stagiaires.notes.store', $this->stagiaire), [
            'matiere_id' => $this->matiereId, 'note' => 17.5, 'type_note' => 'ds', 'note_sur' => 20,
        ])->assertSessionHas('success');

        $note = Note::where('stagiaire_id', $this->stagiaire->id)->where('note', 17.5)->firstOrFail();
        $this->assertNull($note->publie_at);
        $this->assertFalse($this->stagiaire->notesPubliees()->whereKey($note->id)->exists());

        $this->actingAs($this->prof)->post(route('professeur.publications.store'), [
            'type' => 'resultats', 'classe_id' => $this->classe->id, 'matiere_id' => $this->matiereId,
            'titre' => 'Résultats du DS n°1',
        ])->assertSessionHasNoErrors();

        $this->assertNotNull($note->fresh()->publie_at);
        $this->assertGreaterThanOrEqual(1, Publication::where('type', 'resultats')->firstOrFail()->notes_publiees);
    }

    public function test_l_admin_ne_voit_la_note_qu_apres_publication(): void
    {
        Periode::query()->update(['is_active' => false]);
        Periode::whereHas('anneeScolaire', fn ($q) => $q->where('is_active', true))->firstOrFail()->update(['is_active' => true]);
        DB::table('bulletins')->where('stagiaire_id', $this->stagiaire->id)->update(['validated_at' => null]);

        $this->actingAs($this->prof)->post(route('professeur.stagiaires.notes.store', $this->stagiaire), [
            'matiere_id' => $this->matiereId, 'note' => 3.25, 'type_note' => 'cc', 'note_sur' => 20,
        ]);
        $noteId = Note::withoutGlobalScope('publiees')->where('note', 3.25)->value('id');

        $admin = User::where('email', 'admin@demo.ma')->firstOrFail();
        $this->actingAs($admin);
        $this->assertFalse(Note::whereKey($noteId)->exists(), 'Invisible pour l\'admin avant publication');
        $this->get(route('notes.show', $noteId))->assertNotFound();

        $this->actingAs($this->prof)->post(route('professeur.publications.store'), [
            'type' => 'resultats', 'classe_id' => $this->classe->id, 'matiere_id' => $this->matiereId, 'titre' => 'Résultats CC',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin);
        $this->assertTrue(Note::whereKey($noteId)->exists(), 'Visible pour l\'admin après publication');
        $this->get(route('notes.show', $noteId))->assertOk();
    }

    public function test_resultats_sans_note_en_attente_refuses(): void
    {
        $this->actingAs($this->prof)->post(route('professeur.publications.store'), [
            'type' => 'resultats', 'classe_id' => $this->classe->id, 'matiere_id' => $this->matiereId,
            'titre' => 'Résultats',
        ])->assertSessionHasErrors('matiere_id');
    }

    public function test_un_prof_ne_publie_pas_hors_de_ses_affectations(): void
    {
        $autreClasse = Classe::where('filiere_id', '!=', $this->classe->filiere_id)->first()
            ?? $this->markTestSkipped('Pas de classe hors filière dans le jeu de démo.');

        $this->actingAs($this->prof)->post(route('professeur.publications.store'), [
            'type' => 'document', 'classe_id' => $autreClasse->id, 'matiere_id' => $this->matiereId,
            'titre' => 'Intrus', 'contenu' => 'x',
        ])->assertSessionHasErrors('matiere_id');
        $this->assertSame(0, Publication::count());
    }

    public function test_un_prof_ne_voit_pas_la_publication_d_un_collegue(): void
    {
        $devoir = $this->publierDevoir();
        $collegue = User::where('role', 'professeur')->where('id', '!=', $this->prof->id)->firstOrFail();

        $this->actingAs($collegue)->get(route('professeur.publications.show', $devoir))->assertForbidden();
        $this->actingAs($collegue)->delete(route('professeur.publications.destroy', $devoir))->assertForbidden();
    }
}
