<?php

namespace App\Console\Commands;

use App\Models\ConfigurationPaiement;
use App\Models\Echeancier;
use App\Models\Stagiaire;
use App\Notifications\RetardPaiementNotification;
use Illuminate\Console\Command;

/**
 * Chaque matin :
 *  1. recalcule le statut des échéances non soldées (passage « en retard ») et prévient le stagiaire UNE fois ;
 *  2. met à jour le solde de chaque stagiaire ;
 *  3. suspension automatique (si activée) et réactivation quand le retard est régularisé.
 * Les rappels avant échéance sont envoyés par paiements:rappels.
 */
class CheckRetardsPaiements extends Command
{
    protected $signature = 'paiements:check-retards';
    protected $description = 'Met à jour les retards de paiement et les soldes des stagiaires';

    private const MOTIF_SUSPENSION = 'Suspension automatique - retard de paiement';

    public function handle()
    {
        // 1. Statuts des échéances
        $nouveauxRetards = 0;
        Echeancier::with('stagiaire.user')->where('montant_restant', '>', 0)->chunkById(200, function ($echeances) use (&$nouveauxRetards) {
            foreach ($echeances as $echeance) {
                $avant = $echeance->statut;
                $echeance->recalculer();

                if ($avant !== 'en_retard' && $echeance->statut === 'en_retard') {
                    $nouveauxRetards++;
                    try {
                        $echeance->stagiaire?->user?->notify(new RetardPaiementNotification($echeance));
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        });
        $this->info("{$nouveauxRetards} échéance(s) passée(s) en retard.");

        // 2. Soldes
        Stagiaire::has('echeanciers')->chunkById(200, fn ($stagiaires) => $stagiaires->each->updateSoldePaiement());
        $this->info('Soldes des stagiaires mis à jour.');

        // 3. Suspension / réactivation automatiques (0 = désactivé)
        $jours = (int) ConfigurationPaiement::get('max_retard_avant_suspension', 0);
        if ($jours > 0) {
            $limite = now()->subDays($jours)->toDateString();

            $suspendus = Stagiaire::where('statut', 'actif')
                ->whereHas('echeanciers', fn ($q) => $q->where('montant_restant', '>', 0)->whereDate('date_echeance', '<', $limite))
                ->get();

            foreach ($suspendus as $stagiaire) {
                $stagiaire->update(['statut' => 'suspendu', 'motif_statut' => self::MOTIF_SUSPENSION . " (plus de {$jours} jours)"]);
                $stagiaire->user?->update(['is_active' => false]);
            }

            // Réactivation : seulement ceux suspendus par cette commande et désormais à jour
            $reactives = Stagiaire::where('statut', 'suspendu')
                ->where('motif_statut', 'like', self::MOTIF_SUSPENSION . '%')
                ->whereDoesntHave('echeanciers', fn ($q) => $q->where('montant_restant', '>', 0)->whereDate('date_echeance', '<', $limite))
                ->get();

            foreach ($reactives as $stagiaire) {
                $stagiaire->update(['statut' => 'actif', 'motif_statut' => null]);
                $stagiaire->user?->update(['is_active' => true]);
            }

            $this->info("{$suspendus->count()} suspension(s), {$reactives->count()} réactivation(s).");
        }

        return Command::SUCCESS;
    }
}
