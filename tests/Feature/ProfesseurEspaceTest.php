<?php

namespace Tests\Feature;

use App\Models\Absence;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\Stagiaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Parcourt l'espace professeur et les écrans d'affectation sur le jeu de démonstration
 */
class ProfesseurEspaceTest extends TestCase
{
    use RefreshDatabase;

    // Seed explicite : la propriété $seed n'agit que si cette classe déclenche la migration initiale
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function professeur(): User
    {
        return User::where('role', 'professeur')->has('filieres')->firstOrFail();
    }

    private function stagiaireDe(User $prof): Stagiaire
    {
        return Stagiaire::whereIn('filiere_id', $prof->filieresActives()->pluck('filieres.id'))->firstOrFail();
    }

    public function test_pages_professeur_s_affichent(): void
    {
        $prof = $this->professeur();
        $stagiaire = $this->stagiaireDe($prof);

        $this->actingAs($prof);
        foreach ([
            route('professeur.dashboard'),
            route('professeur.stagiaires'),
            route('professeur.stagiaires.notes', $stagiaire),
            route('professeur.notes-par-matiere'),
            route('professeur.presences'),
            route('professeur.planning'),
            route('professeur.planning.create'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_exports_professeur(): void
    {
        $this->actingAs($this->professeur());

        $this->get(route('professeur.notes.export-pdf'))->assertOk();
        $this->get(route('professeur.notes.export-excel'))->assertOk();
        $this->get(route('professeur.stagiaires.export-pdf'))->assertOk();
        $this->get(route('professeur.stagiaires.export-excel'))->assertOk();
    }

    public function test_affectation_inactive_retire_l_acces_au_stagiaire(): void
    {
        $prof = $this->professeur();
        $stagiaire = $this->stagiaireDe($prof);

        DB::table('professeur_filiere')
            ->where('professeur_id', $prof->id)
            ->where('filiere_id', $stagiaire->filiere_id)
            ->update(['is_active' => false]);

        $this->actingAs($prof)
            ->get(route('professeur.stagiaires.notes', $stagiaire))
            ->assertRedirect(route('professeur.stagiaires'));
    }

    public function test_professeur_ne_peut_pas_modifier_la_note_d_un_collegue(): void
    {
        $note = Note::whereNotNull('created_by')->firstOrFail();
        $autre = User::where('role', 'professeur')->where('id', '!=', $note->created_by)->firstOrFail();
        $avant = $note->note;

        $this->actingAs($autre)->put(route('professeur.stagiaires.notes.update', [$note->stagiaire_id, $note]), [
            'matiere_id' => $note->matiere_id,
            'note' => 0,
            'type_note' => 'ds',
            'note_sur' => 20,
        ]);

        $this->assertEquals($avant, $note->fresh()->note);
    }

    public function test_professeur_ne_peut_pas_supprimer_l_absence_d_un_collegue(): void
    {
        $absence = Absence::whereNotNull('created_by')->firstOrFail();
        $autre = User::where('role', 'professeur')->where('id', '!=', $absence->created_by)->firstOrFail();

        $this->actingAs($autre)->delete(route('professeur.presences.supprimer', $absence));

        $this->assertNotNull($absence->fresh());
    }

    public function test_affectations_reservees_a_l_admin(): void
    {
        $prof = $this->professeur();
        $admin = User::where('role', 'administrateur')->firstOrFail();

        $this->actingAs($admin)->get(route('professeurs.filieres.edit', $prof))->assertOk();
        $this->actingAs($admin)->get(route('professeurs.matieres.edit', $prof))->assertOk();

        $this->actingAs($prof)->get(route('professeurs.filieres.edit', $prof))->assertRedirect(route('dashboard'));
    }
}
