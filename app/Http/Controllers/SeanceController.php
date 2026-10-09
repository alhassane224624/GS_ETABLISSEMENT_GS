<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\Planning;
use App\Models\Stagiaire;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Une séance du planning, côté professeur : faire l'appel et remplir le cahier de textes.
 */
class SeanceController extends Controller
{
    public function show(Planning $planning)
    {
        $this->autoriser($planning);

        $planning->load(['classe.filiere', 'matiere', 'salle', 'absences']);
        $stagiaires = $this->eleves($planning);

        // État actuel de chaque élève pour cette séance
        $etats = $planning->absences->keyBy('stagiaire_id');

        // Absences de la journée enregistrées ailleurs (journée, matin…) : affichées pour information
        $autres = Absence::whereIn('stagiaire_id', $stagiaires->pluck('id'))
            ->whereDate('date', $planning->date)
            ->where(fn ($q) => $q->whereNull('planning_id')->orWhere('planning_id', '!=', $planning->id))
            ->get()->groupBy('stagiaire_id');

        $commencee = $this->debut($planning)->lte(now());

        return view('professeur.seance', compact('planning', 'stagiaires', 'etats', 'autres', 'commencee'));
    }

    public function enregistrer(Request $request, Planning $planning)
    {
        $this->autoriser($planning);

        if ($this->debut($planning)->gt(now())) {
            return back()->with('error', 'L\'appel ne peut être fait qu\'à partir du début de la séance.');
        }

        $data = $request->validate([
            'presence'          => 'nullable|array',
            'presence.*'        => 'in:present,absent,retard',
            'minutes'           => 'nullable|array',
            'minutes.*'         => 'nullable|integer|min:1|max:240',
            'contenu_seance'    => 'nullable|string|max:5000',
            'devoirs'           => 'nullable|string|max:3000',
            'devoirs_pour'      => 'nullable|date|after_or_equal:' . $planning->date->toDateString(),
        ]);

        $eleves = $this->eleves($planning)->pluck('id')->all();
        $absents = 0;
        $retards = 0;

        DB::transaction(function () use ($planning, $data, $eleves, &$absents, &$retards) {
            // L'appel remplace le précédent, sauf les absences déjà justifiées ou en cours de justification
            Absence::where('planning_id', $planning->id)
                ->where('justifiee', false)
                ->whereNull('justification_statut')
                ->delete();

            foreach ($data['presence'] ?? [] as $stagiaireId => $etat) {
                $stagiaireId = (int) $stagiaireId;
                if ($etat === 'present' || !in_array($stagiaireId, $eleves, true)) {
                    continue;
                }
                if (Absence::where('planning_id', $planning->id)->where('stagiaire_id', $stagiaireId)->exists()) {
                    continue; // absence déjà justifiée conservée
                }

                $debut = substr($planning->heure_debut, 0, 5);
                $fin = substr($planning->heure_fin, 0, 5);

                // Déjà absent toute la journée / la demi-journée : rien à ajouter
                if ($etat === 'absent' && Absence::chevauche($stagiaireId, $planning->date->toDateString(), 'heure', $debut, $fin)) {
                    continue;
                }

                Absence::create([
                    'stagiaire_id'   => $stagiaireId,
                    'planning_id'    => $planning->id,
                    'periode_id'     => Absence::periodePourDate($planning->date->toDateString()),
                    'date'           => $planning->date->toDateString(),
                    'type'           => 'heure',
                    'heure_debut'    => $debut,
                    'heure_fin'      => $fin,
                    'retard_minutes' => $etat === 'retard' ? (int) ($data['minutes'][$stagiaireId] ?? 5) : null,
                    'motif'          => null,
                    'justifiee'      => false,
                    'created_by'     => Auth::id(),
                ]);

                $etat === 'retard' ? $retards++ : $absents++;
            }

            $maj = [
                'appel_fait_at'  => now(),
                'contenu_seance' => $data['contenu_seance'] ?? null,
                'devoirs'        => $data['devoirs'] ?? null,
                'devoirs_pour'   => !empty($data['devoirs']) ? ($data['devoirs_pour'] ?? null) : null,
            ];
            // Séance terminée → statut « terminé »
            if ($this->fin($planning)->lte(now()) && in_array($planning->statut, ['valide', 'en_cours'])) {
                $maj['statut'] = 'termine';
            }
            $planning->update($maj);
        });

        return redirect()->route('professeur.planning', ['date' => $planning->date->toDateString()])
            ->with('success', "Appel enregistré : {$absents} absent(s), {$retards} retard(s). Cahier de textes à jour.");
    }

    /** Le professeur de la séance (ou un administrateur), séance non annulée */
    private function autoriser(Planning $planning): void
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || (int) $planning->professeur_id === (int) $user->id, 403, 'Cette séance ne vous est pas attribuée.');
        abort_if(in_array($planning->statut, ['brouillon', 'annule']), 403, 'Séance non validée ou annulée.');
    }

    private function eleves(Planning $planning)
    {
        return Stagiaire::where('classe_id', $planning->classe_id)
            ->where('statut', 'actif')
            ->orderBy('nom')->orderBy('prenom')
            ->get(['id', 'nom', 'prenom', 'matricule', 'photo']);
    }

    private function debut(Planning $p): Carbon
    {
        return Carbon::parse($p->date->toDateString() . ' ' . $p->heure_debut);
    }

    private function fin(Planning $p): Carbon
    {
        return Carbon::parse($p->date->toDateString() . ' ' . $p->heure_fin);
    }
}
