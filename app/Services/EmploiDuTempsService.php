<?php

namespace App\Services;

use App\Models\Creneau;
use App\Models\Planning;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmploiDuTempsService
{
    /**
     * Conflits d'un créneau avec les autres créneaux actifs de la même année, le même jour :
     * même classe, même professeur ou même salle sur un horaire qui se chevauche.
     *
     * @return string[] messages
     */
    public function conflits(array $c, ?int $ignorerId = null): array
    {
        $autres = Creneau::with(['classe', 'professeur', 'salle', 'matiere'])
            ->where('annee_scolaire_id', $c['annee_scolaire_id'])
            ->where('jour', $c['jour'])
            ->where('is_active', true)
            ->where('heure_debut', '<', $c['heure_fin'])
            ->where('heure_fin', '>', $c['heure_debut'])
            ->when($ignorerId, fn ($q) => $q->where('id', '!=', $ignorerId))
            ->where(fn ($q) => $q->where('classe_id', $c['classe_id'])
                ->orWhere('professeur_id', $c['professeur_id'])
                ->orWhere('salle_id', $c['salle_id']))
            ->get();

        return $autres->map(function ($a) use ($c) {
            $qui = match (true) {
                (int) $a->classe_id === (int) $c['classe_id'] => "la classe {$a->classe->nom}",
                (int) $a->professeur_id === (int) $c['professeur_id'] => "le professeur {$a->professeur->name}",
                default => "la salle {$a->salle->nom}",
            };
            return "Conflit : {$qui} a déjà {$a->matiere->nom} le {$a->jour_libelle} {$a->horaire}.";
        })->all();
    }

    /**
     * Génère les séances datées des créneaux actifs entre deux dates.
     * Les séances déjà générées ne sont pas dupliquées ; les conflits avec le planning existant sont signalés.
     *
     * @param  int[]|null  $classeIds  null = toutes les classes de l'année
     * @param  string[]    $joursExclus dates Y-m-d à ignorer (jours fériés, vacances)
     */
    public function generer(int $anneeId, string $debut, string $fin, ?array $classeIds = null, array $joursExclus = []): array
    {
        $rapport = ['creees' => 0, 'existantes' => 0, 'conflits' => []];

        $creneaux = Creneau::with(['classe', 'matiere', 'professeur', 'salle'])
            ->where('annee_scolaire_id', $anneeId)
            ->where('is_active', true)
            ->when($classeIds, fn ($q) => $q->whereIn('classe_id', $classeIds))
            ->get()
            ->groupBy('jour');

        $exclus = array_flip($joursExclus);

        DB::transaction(function () use ($creneaux, $debut, $fin, $exclus, &$rapport) {
            foreach (CarbonPeriod::create($debut, $fin) as $date) {
                $jour = $date->dayOfWeekIso; // 1 = lundi … 7 = dimanche
                if (!isset($creneaux[$jour]) || isset($exclus[$date->toDateString()])) {
                    continue;
                }

                foreach ($creneaux[$jour] as $c) {
                    $jourStr = $date->toDateString();

                    if (Planning::where('creneau_id', $c->id)->whereDate('date', $jourStr)->exists()) {
                        $rapport['existantes']++;
                        continue;
                    }

                    $conflit = Planning::whereDate('date', $jourStr)
                        ->whereIn('statut', ['brouillon', 'valide', 'en_cours'])
                        ->where('heure_debut', '<', $c->heure_fin)
                        ->where('heure_fin', '>', $c->heure_debut)
                        ->where(fn ($q) => $q->where('classe_id', $c->classe_id)
                            ->orWhere('professeur_id', $c->professeur_id)
                            ->orWhere('salle_id', $c->salle_id))
                        ->first();

                    if ($conflit) {
                        $rapport['conflits'][] = $date->format('d/m/Y') . " {$c->horaire} — {$c->classe->nom}, {$c->matiere->nom} : déjà occupé (séance #{$conflit->id}).";
                        continue;
                    }

                    Planning::create([
                        'professeur_id' => $c->professeur_id,
                        'salle_id'      => $c->salle_id,
                        'matiere_id'    => $c->matiere_id,
                        'classe_id'     => $c->classe_id,
                        'creneau_id'    => $c->id,
                        'date'          => $jourStr,
                        'heure_debut'   => $c->heure_debut,
                        'heure_fin'     => $c->heure_fin,
                        'type_cours'    => $c->type_cours,
                        'statut'        => 'valide', // emploi du temps arrêté par l'administration
                        'created_by'    => Auth::id(),
                        'validated_by'  => Auth::id(),
                        'validated_at'  => now(),
                    ]);
                    $rapport['creees']++;
                }
            }
        });

        return $rapport;
    }

    /** Supprime les séances à venir d'un créneau (non commencées). Les séances passées restent dans l'historique. */
    public function supprimerSeancesFutures(Creneau $creneau): int
    {
        return Planning::where('creneau_id', $creneau->id)
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->whereIn('statut', ['brouillon', 'valide'])
            ->delete();
    }
}
