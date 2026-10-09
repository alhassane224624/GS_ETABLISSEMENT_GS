<?php

namespace App\Services;

use App\Models\ConfigurationPaiement;
use App\Models\Echeancier;
use App\Models\Paiement;
use App\Models\Stagiaire;
use App\Notifications\PaiementRecuNotification;
use App\Notifications\PaiementRefuseNotification;
use App\Notifications\PaiementValideNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Toute la logique d'encaissement au même endroit.
 *
 * Règles :
 *  1. Un paiement ne peut pas dépasser ce qui reste à payer, moins les paiements déjà en attente.
 *  2. Espèces : validé immédiatement (paramètre validation_auto_especes).
 *     Chèque / virement / carte / mobile : en attente jusqu'à l'encaissement réel.
 *  3. Les échéances ne sont imputées QU'À LA VALIDATION : un chèque en attente ne solde rien.
 *  4. Imputation sur les échéances choisies, sinon de la plus ancienne à la plus récente.
 *  5. Refus = paiement en attente uniquement. Annulation = paiement validé (imputations retirées).
 */
class PaiementService
{
    /** Ce qui reste à payer (échéances ouvertes) */
    public function resteAPayer(int $stagiaireId): float
    {
        return (float) Echeancier::where('stagiaire_id', $stagiaireId)->ouverts()->sum('montant_restant');
    }

    /** Montant encore encaissable : reste à payer − paiements déjà en attente */
    public function montantEncaissable(int $stagiaireId, ?int $ignorerPaiementId = null): float
    {
        $enAttente = (float) Paiement::where('stagiaire_id', $stagiaireId)
            ->where('statut', 'en_attente')
            ->when($ignorerPaiementId, fn ($q) => $q->where('id', '!=', $ignorerPaiementId))
            ->sum('montant');

        return max(0, round($this->resteAPayer($stagiaireId) - $enAttente, 2));
    }

    public function enregistrer(array $data, ?UploadedFile $justificatif = null): Paiement
    {
        $stagiaireId = (int) $data['stagiaire_id'];
        $cibles = array_map('intval', $data['echeanciers'] ?? []);

        return DB::transaction(function () use ($data, $justificatif, $stagiaireId, $cibles) {
            // Verrou sur les échéances du stagiaire : deux saisies simultanées ne peuvent pas dépasser le reste
            Echeancier::where('stagiaire_id', $stagiaireId)->lockForUpdate()->get();

            $this->verifierCibles($stagiaireId, $cibles);

            $encaissable = $this->montantEncaissable($stagiaireId);
            if ($encaissable <= 0) {
                throw ValidationException::withMessages([
                    'montant' => 'Ce stagiaire n\'a aucune échéance à régler (ou tout est déjà couvert par des paiements en attente). Créez d\'abord une échéance.',
                ]);
            }
            if ((float) $data['montant'] > $encaissable) {
                throw ValidationException::withMessages([
                    'montant' => 'Le montant dépasse ce qui reste à payer : ' . number_format($encaissable, 2, ',', ' ') . ' DH maximum.',
                ]);
            }
            if ($cibles) {
                $totalCibles = (float) Echeancier::whereIn('id', $cibles)->sum('montant_restant');
                if ((float) $data['montant'] > $totalCibles) {
                    throw ValidationException::withMessages([
                        'montant' => 'Le montant dépasse le total des échéances cochées (' . number_format($totalCibles, 2, ',', ' ') . ' DH). Cochez d\'autres échéances ou aucune.',
                    ]);
                }
            }

            $paiement = Paiement::create([
                'stagiaire_id'      => $stagiaireId,
                'user_id'           => Auth::id(),
                'montant'           => $data['montant'],
                'type_paiement'     => $data['type_paiement'] ?? $this->devinerType($stagiaireId, $cibles),
                'methode_paiement'  => $data['methode_paiement'],
                'statut'            => 'en_attente',
                'date_paiement'     => $data['date_paiement'],
                'reference_externe' => $data['reference_externe'] ?? null,
                'description'       => $data['description'] ?? null,
                'notes_admin'       => $data['notes_admin'] ?? null,
                'metadata'          => array_filter([
                    'banque' => $data['banque'] ?? null,
                    'echeances_cibles' => $cibles ?: null,
                ]) ?: null,
            ]);

            if ($justificatif) {
                $paiement->update([
                    'justificatif_path' => $justificatif->store('justificatifs/' . $stagiaireId, 'public'),
                ]);
            }

            if ($data['methode_paiement'] === 'especes' && $this->validationAutoEspeces()) {
                $this->valider($paiement);
            } else {
                $this->notifier($paiement, new PaiementRecuNotification($paiement));
            }

            return $paiement->fresh();
        });
    }

    public function valider(Paiement $paiement, ?string $notes = null): Paiement
    {
        return DB::transaction(function () use ($paiement, $notes) {
            $paiement = Paiement::whereKey($paiement->id)->lockForUpdate()->firstOrFail();

            if ($paiement->statut !== 'en_attente') {
                throw ValidationException::withMessages(['paiement' => 'Seul un paiement en attente peut être validé.']);
            }

            $echeances = Echeancier::where('stagiaire_id', $paiement->stagiaire_id)
                ->lockForUpdate()->get();

            $reste = (float) $echeances->filter(fn ($e) => (float) $e->montant_restant > 0)->sum('montant_restant');
            if ((float) $paiement->montant > $reste + 0.001) {
                throw ValidationException::withMessages([
                    'paiement' => 'Impossible de valider : le montant dépasse ce qui reste à payer (' . number_format($reste, 2, ',', ' ') . ' DH). Les échéances ont changé depuis la saisie.',
                ]);
            }

            $paiement->update([
                'statut'      => 'valide',
                'valide_at'   => now(),
                'valide_by'   => Auth::id(),
                'notes_admin' => $notes ?: $paiement->notes_admin,
            ]);

            $this->imputer($paiement);
            $paiement->stagiaire->updateSoldePaiement();

            $this->notifier($paiement, new PaiementValideNotification($paiement));

            return $paiement;
        });
    }

    public function refuser(Paiement $paiement, string $motif): Paiement
    {
        if ($paiement->statut !== 'en_attente') {
            throw ValidationException::withMessages(['paiement' => 'Seul un paiement en attente peut être refusé. Pour un paiement validé, utilisez « Annuler ».']);
        }

        $paiement->update(['statut' => 'refuse', 'notes_admin' => $motif]);
        $this->notifier($paiement, new PaiementRefuseNotification($paiement, $motif));

        return $paiement;
    }

    /** Annule un paiement validé (erreur de saisie, chèque impayé…) : les échéances redeviennent dues. */
    public function annuler(Paiement $paiement, string $motif): Paiement
    {
        return DB::transaction(function () use ($paiement, $motif) {
            if ($paiement->statut !== 'valide') {
                throw ValidationException::withMessages(['paiement' => 'Seul un paiement validé peut être annulé.']);
            }

            $paiement->update([
                'statut' => 'annule',
                'notes_admin' => trim(($paiement->notes_admin ? $paiement->notes_admin . "\n" : '') .
                    'Annulé le ' . now()->format('d/m/Y H:i') . ' par ' . (Auth::user()->name ?? 'système') . ' : ' . $motif),
            ]);

            foreach ($paiement->echeanciers()->get() as $echeance) {
                $echeance->annulerAffectation($paiement);
            }

            $paiement->stagiaire->updateSoldePaiement();

            return $paiement;
        });
    }

    /** Imputation d'un paiement validé : échéances choisies d'abord, puis les plus anciennes */
    private function imputer(Paiement $paiement): void
    {
        $reste = (float) $paiement->montant;
        $cibles = $paiement->echeances_cibles;

        $ouvertes = Echeancier::where('stagiaire_id', $paiement->stagiaire_id)
            ->where('montant_restant', '>', 0)
            ->orderBy('date_echeance')->orderBy('id')
            ->get();

        $ordre = $cibles
            ? $ouvertes->sortBy(fn ($e) => in_array($e->id, $cibles) ? 0 : 1)->values()
            : $ouvertes;

        $types = [];
        foreach ($ordre as $echeance) {
            if ($reste <= 0) {
                break;
            }
            $part = round(min($reste, (float) $echeance->montant_restant), 2);
            if ($part <= 0) {
                continue;
            }
            $echeance->affecterPaiement($paiement, $part);
            $types[] = $echeance->type;
            $reste = round($reste - $part, 2);
        }

        // La nature du paiement suit les échéances réellement réglées
        $types = array_unique($types);
        if (count($types) === 1 && array_key_exists($types[0], Paiement::TYPES)) {
            $paiement->update(['type_paiement' => $types[0]]);
        }
    }

    /** Nature provisoire du paiement (corrigée à l'imputation) */
    private function devinerType(int $stagiaireId, array $cibles): string
    {
        $types = $cibles
            ? Echeancier::whereIn('id', $cibles)->distinct()->pluck('type')->all()
            : [Echeancier::where('stagiaire_id', $stagiaireId)->where('montant_restant', '>', 0)
                ->orderBy('date_echeance')->value('type')];

        $types = array_values(array_filter($types));
        return count($types) === 1 && array_key_exists($types[0], Paiement::TYPES) ? $types[0] : 'autre';
    }

    private function verifierCibles(int $stagiaireId, array $cibles): void
    {
        if (!$cibles) {
            return;
        }
        $valides = Echeancier::whereIn('id', $cibles)
            ->where('stagiaire_id', $stagiaireId)
            ->where('montant_restant', '>', 0)
            ->count();

        if ($valides !== count(array_unique($cibles))) {
            throw ValidationException::withMessages([
                'echeanciers' => 'Certaines échéances cochées n\'appartiennent pas à ce stagiaire ou sont déjà soldées.',
            ]);
        }
    }

    private function validationAutoEspeces(): bool
    {
        $valeur = ConfigurationPaiement::get('validation_auto_especes', true);
        return $valeur === null ? true : (bool) $valeur;
    }

    private function notifier(Paiement $paiement, $notification): void
    {
        try {
            $paiement->stagiaire?->user?->notify($notification);
        } catch (\Throwable $e) {
            Log::warning('Notification paiement non envoyée', ['paiement_id' => $paiement->id, 'erreur' => $e->getMessage()]);
        }
    }
}
