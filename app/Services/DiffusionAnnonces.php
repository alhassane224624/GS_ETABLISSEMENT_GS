<?php

namespace App\Services;

use App\Models\Annonce;
use App\Notifications\Alerte;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Notifie les destinataires d'une annonce importante ou urgente, une seule fois, à sa date de publication.
 * Les annonces « information » ne sont pas notifiées : elles s'affichent dans la page Annonces.
 */
class DiffusionAnnonces
{
    /** @return int nombre de personnes notifiées */
    public function diffuser(Annonce $annonce): int
    {
        if ($annonce->notifiee_at || $annonce->publie_le->isFuture()) {
            return 0;
        }

        // Marquée avant l'envoi : pas de double notification si deux exécutions se chevauchent
        $annonce->update(['notifiee_at' => now()]);

        if ($annonce->importance === 'info') {
            return 0;
        }

        $notification = new Alerte(
            ($annonce->importance === 'urgent' ? 'Annonce urgente : ' : 'Annonce : ') . $annonce->titre,
            Str::limit(strip_tags($annonce->contenu), 200),
            route('annonces.show', $annonce),
            'fas ' . $annonce->icone,
            $annonce->importance === 'urgent' ? 'danger' : 'warning',
            (bool) config('services.annonces.mail'),
        );

        $total = 0;
        $annonce->destinataires()->chunkById(200, function ($users) use ($notification, $annonce, &$total) {
            foreach ($users as $user) {
                try {
                    $user->notify($notification);
                    $total++;
                } catch (\Throwable $e) {
                    Log::warning('Notification d\'annonce non envoyée', ['annonce_id' => $annonce->id, 'user_id' => $user->id, 'erreur' => $e->getMessage()]);
                }
            }
        });

        return $total;
    }

    /** Annonces programmées arrivées à leur date (tâche planifiée) */
    public function diffuserEnAttente(): int
    {
        $total = 0;
        Annonce::whereNull('notifiee_at')->where('publie_le', '<=', now())->get()
            ->each(function (Annonce $a) use (&$total) {
                $total += $this->diffuser($a);
            });

        return $total;
    }
}
