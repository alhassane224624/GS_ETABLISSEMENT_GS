<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\User;
use App\Notifications\Alerte;

/**
 * Administration : contrôle des notes saisies par les professeurs mais pas encore publiées.
 * Seuls les volumes sont affichés, jamais les notes elles-mêmes.
 */
class NotesEnAttenteController extends Controller
{
    public function index()
    {
        $groupes = self::requete()
            ->with(['creator:id,name,email', 'classe:id,nom', 'matiere:id,nom'])
            ->selectRaw('created_by, classe_id, matiere_id, COUNT(*) as total, MIN(created_at) as plus_ancienne')
            ->groupBy('created_by', 'classe_id', 'matiere_id')
            ->orderBy('plus_ancienne')
            ->get();

        return view('notes.en-attente', [
            'parProf' => $groupes->groupBy('created_by'),
            'total' => $groupes->sum('total'),
        ]);
    }

    public function relancer(User $professeur)
    {
        abort_unless($professeur->role === 'professeur', 404);

        $nombre = self::requete()->where('created_by', $professeur->id)->count();
        if ($nombre === 0) {
            return back()->with('error', "{$professeur->name} n'a plus de note en attente.");
        }

        $professeur->notify(new Alerte(
            'Notes à publier',
            "L'administration vous rappelle que {$nombre} note(s) saisie(s) ne sont pas encore publiées. Elles ne comptent pas dans les bulletins tant qu'elles ne le sont pas.",
            route('professeur.publications.index'),
            'fas fa-paper-plane',
            'warning'
        ));

        return back()->with('success', "Rappel envoyé à {$professeur->name}.");
    }

    /** Nombre total de notes en attente (bandeau de la page des bulletins) */
    public static function nombre(): int
    {
        return self::requete()->count();
    }

    private static function requete()
    {
        return Note::withoutGlobalScope('publiees')->whereNull('publie_at');
    }
}
