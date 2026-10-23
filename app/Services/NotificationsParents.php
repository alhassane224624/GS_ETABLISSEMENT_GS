<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\Bulletin;
use App\Models\Note;
use App\Models\Stagiaire;
use App\Notifications\Alerte;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Prévient les parents (portail, et e-mail si activé) des événements concernant leur enfant.
 * Les envois partent après la validation de la transaction en cours : rien n'est annoncé
 * si l'enregistrement échoue, et un e-mail en échec ne bloque jamais l'action.
 */
class NotificationsParents
{
    public function absence(Absence $absence): void
    {
        $absence->loadMissing('stagiaire', 'planning.matiere');
        $s = $absence->stagiaire;
        if (!$s) {
            return;
        }

        $retard = $absence->retard_minutes !== null;
        $quand = $absence->date->isoFormat('dddd D MMMM');
        $detail = $absence->planning?->matiere
            ? ' (cours de ' . $absence->planning->matiere->nom . ', ' . substr($absence->heure_debut, 0, 5) . ')'
            : ' (' . mb_strtolower($absence->type_libelle) . ')';

        $message = $retard
            ? "{$s->prenom} est arrivé(e) en retard de {$absence->retard_minutes} min le {$quand}{$detail}."
            : "{$s->prenom} a été noté(e) absent(e) le {$quand}{$detail}. Un justificatif peut être déposé depuis son espace.";

        $this->envoyer($s, $retard ? 'Retard' : 'Absence', $message, 'fas fa-user-clock', 'warning');
    }

    /** @param iterable<Note> $notes notes rendues visibles en même temps (une publication de résultats) */
    public function resultats(iterable $notes): void
    {
        EloquentCollection::make($notes)->loadMissing('matiere')->groupBy('stagiaire_id')->each(function (Collection $notesEleve) {
            $s = Stagiaire::find($notesEleve->first()->stagiaire_id);
            if (!$s) {
                return;
            }
            $liste = $notesEleve->take(3)
                ->map(fn (Note $n) => ($n->matiere->nom ?? 'Matière') . ' : ' . rtrim(rtrim(number_format($n->note, 2, ',', ''), '0'), ',') . '/' . (int) $n->note_sur)
                ->implode(' · ');
            $plus = $notesEleve->count() > 3 ? ' (et ' . ($notesEleve->count() - 3) . ' autre(s))' : '';

            $this->envoyer($s, 'Nouveaux résultats', "Nouvelles notes de {$s->prenom} — {$liste}{$plus}.", 'fas fa-poll', 'info');
        });
    }

    public function bulletin(Bulletin $bulletin): void
    {
        $bulletin->loadMissing('stagiaire', 'periode');
        if (!$bulletin->stagiaire) {
            return;
        }

        $moyenne = $bulletin->moyenne_generale !== null ? ' Moyenne générale : ' . number_format($bulletin->moyenne_generale, 2, ',', '') . '/20.' : '';
        $this->envoyer(
            $bulletin->stagiaire,
            'Bulletin disponible',
            "Le bulletin de {$bulletin->stagiaire->prenom} pour « " . ($bulletin->periode->nom ?? 'la période') . " » est disponible.{$moyenne}",
            'fas fa-file-alt',
            'success'
        );
    }

    private function envoyer(Stagiaire $stagiaire, string $titre, string $message, string $icone, string $niveau): void
    {
        DB::afterCommit(function () use ($stagiaire, $titre, $message, $icone, $niveau) {
            $parents = $stagiaire->parents()->where('is_active', true)->get();
            $url = route('parent.enfant', $stagiaire);

            foreach ($parents as $parent) {
                try {
                    $parent->notify(new Alerte($titre, $message, $url, $icone, $niveau, (bool) config('services.parents.mail')));
                } catch (\Throwable $e) {
                    Log::warning('Notification parent non envoyée', [
                        'parent_id' => $parent->id, 'stagiaire_id' => $stagiaire->id, 'erreur' => $e->getMessage(),
                    ]);
                }
            }
        });
    }
}
