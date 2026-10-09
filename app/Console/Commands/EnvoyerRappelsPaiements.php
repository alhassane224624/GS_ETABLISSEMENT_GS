<?php

namespace App\Console\Commands;

use App\Models\ConfigurationPaiement;
use App\Models\Echeancier;
use App\Notifications\RappelEcheanceNotification;
use Illuminate\Console\Command;

/**
 * Rappel UNIQUE avant chaque échéance non soldée (délai paramétrable : delai_rappel_echeance, 7 jours par défaut).
 * Les retards sont notifiés par paiements:check-retards au moment où l'échéance passe en retard.
 */
class EnvoyerRappelsPaiements extends Command
{
    protected $signature = 'paiements:rappels {--force : Renvoyer même si un rappel a déjà été envoyé}';
    protected $description = 'Envoie un rappel avant les échéances à venir';

    public function handle()
    {
        $delai = (int) (ConfigurationPaiement::get('delai_rappel_echeance', 7) ?: 7);

        $echeances = Echeancier::with('stagiaire.user')
            ->where('montant_restant', '>', 0)
            ->whereDate('date_echeance', '>=', now()->toDateString())
            ->whereDate('date_echeance', '<=', now()->addDays($delai)->toDateString())
            ->when(!$this->option('force'), fn ($q) => $q->where('notification_envoyee', false))
            ->get();

        $envoyes = 0;
        foreach ($echeances as $echeance) {
            $user = $echeance->stagiaire?->user;
            if (!$user) {
                continue;
            }
            try {
                $user->notify(new RappelEcheanceNotification($echeance));
                $echeance->update(['notification_envoyee' => true, 'notification_sent_at' => now()]);
                $envoyes++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->info("{$envoyes} rappel(s) envoyé(s) (échéances dans les {$delai} prochains jours).");

        return Command::SUCCESS;
    }
}
