<?php

namespace App\Services;

use App\Models\Echeancier;
use App\Models\Stagiaire;
use Illuminate\Support\Facades\DB;

/**
 * Applique les remises d'un stagiaire sur ses échéances.
 * Une échéance déjà payée garde sa remise : on ne descend jamais sous ce qui a été encaissé.
 */
class RemiseService
{
    public function appliquer(Stagiaire $stagiaire): void
    {
        DB::transaction(function () use ($stagiaire) {
            $remises = $stagiaire->remises()->where('is_active', true)->get();

            $echeances = Echeancier::where('stagiaire_id', $stagiaire->id)->lockForUpdate()->get();

            foreach ($echeances as $echeance) {
                // Échéance soldée : on ne touche plus à son montant
                if ($echeance->statut === 'paye' && (float) $echeance->montant_restant <= 0) {
                    continue;
                }

                $total = $remises
                    ->filter(fn ($r) => $r->concerne($echeance))
                    ->sum(fn ($r) => $r->montantPour($echeance));

                $echeance->montant_remise = min($total, (float) $echeance->montant);
                $echeance->recalculer();
            }

            $stagiaire->updateSoldePaiement();
        });
    }
}
