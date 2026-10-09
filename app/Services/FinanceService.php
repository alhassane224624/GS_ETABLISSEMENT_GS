<?php

namespace App\Services;

use App\Models\Depense;
use App\Models\Paiement;
use App\Models\Planning;
use App\Models\Salaire;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceService
{
    /**
     * Séances RÉELLEMENT faites par un professeur sur un mois :
     * séance terminée ou appel fait, hors séances annulées.
     */
    public function seancesRealisees(int $professeurId, Carbon $mois): Collection
    {
        return Planning::with(['matiere:id,nom', 'classe:id,nom'])
            ->where('professeur_id', $professeurId)
            ->whereBetween('date', [$mois->copy()->startOfMonth()->toDateString(), $mois->copy()->endOfMonth()->toDateString()])
            ->where('statut', '!=', 'annule')
            ->where(fn ($q) => $q->where('statut', 'termine')->orWhereNotNull('appel_fait_at'))
            ->orderBy('date')->orderBy('heure_debut')
            ->get();
    }

    public static function duree(Planning $p): float
    {
        $d = Carbon::parse($p->heure_debut);
        $f = Carbon::parse($p->heure_fin);
        return round($d->diffInMinutes($f) / 60, 2);
    }

    /** Calcule (ou recalcule) les salaires du mois pour tous les professeurs rémunérés. Les salaires payés ne bougent pas. */
    public function calculerMois(Carbon $mois): array
    {
        $mois = $mois->copy()->startOfMonth();
        $r = ['calcules' => 0, 'payes' => 0, 'sans_tarif' => []];

        $profs = User::where('role', 'professeur')->where('is_active', true)->orderBy('name')->get();

        DB::transaction(function () use ($profs, $mois, &$r) {
            foreach ($profs as $prof) {
                if (!$prof->mode_remuneration || ($prof->mode_remuneration === 'horaire' && !$prof->taux_horaire)
                    || ($prof->mode_remuneration === 'fixe' && !$prof->salaire_fixe)) {
                    $r['sans_tarif'][] = $prof->name;
                    continue;
                }

                $salaire = Salaire::firstOrNew(['professeur_id' => $prof->id, 'mois' => $mois->toDateString()]);
                if ($salaire->exists && $salaire->estPaye()) {
                    $r['payes']++;
                    continue;
                }

                $seances = $this->seancesRealisees($prof->id, $mois);
                $heures = round($seances->sum(fn ($s) => self::duree($s)), 2);
                $base = $prof->mode_remuneration === 'fixe'
                    ? (float) $prof->salaire_fixe
                    : round($heures * (float) $prof->taux_horaire, 2);

                $salaire->fill([
                    'mode_remuneration' => $prof->mode_remuneration,
                    'heures' => $heures,
                    'nb_seances' => $seances->count(),
                    'taux_horaire' => $prof->mode_remuneration === 'horaire' ? $prof->taux_horaire : null,
                    'montant_base' => $base,
                    'primes' => $salaire->primes ?? 0,
                    'retenues' => $salaire->retenues ?? 0,
                    'statut' => 'calcule',
                    'created_by' => $salaire->created_by ?? Auth::id(),
                ]);
                $salaire->montant_net = max(0, round($base + (float) $salaire->primes - (float) $salaire->retenues, 2));
                $salaire->save();
                $r['calcules']++;
            }
        });

        return $r;
    }

    public function ajuster(Salaire $salaire, float $primes, float $retenues, ?string $observation): void
    {
        if ($salaire->estPaye()) {
            throw ValidationException::withMessages(['salaire' => 'Ce salaire est déjà payé.']);
        }
        $salaire->update([
            'primes' => $primes,
            'retenues' => $retenues,
            'observation' => $observation,
            'montant_net' => max(0, round((float) $salaire->montant_base + $primes - $retenues, 2)),
        ]);
    }

    /** Paiement du salaire → dépense « salaires » enregistrée automatiquement */
    public function payer(Salaire $salaire, string $mode, string $date, ?string $reference): void
    {
        if ($salaire->estPaye()) {
            throw ValidationException::withMessages(['salaire' => 'Ce salaire est déjà payé.']);
        }
        if ((float) $salaire->montant_net <= 0) {
            throw ValidationException::withMessages(['salaire' => 'Montant nul : rien à payer.']);
        }

        DB::transaction(function () use ($salaire, $mode, $date, $reference) {
            $salaire->update(['statut' => 'paye', 'date_paiement' => $date, 'mode_paiement' => $mode]);
            Depense::create([
                'categorie' => 'salaires',
                'libelle' => 'Salaire ' . $salaire->mois_libelle . ' — ' . ($salaire->professeur->name ?? ''),
                'montant' => $salaire->montant_net,
                'date_depense' => $date,
                'mode_paiement' => $mode,
                'reference' => $reference,
                'salaire_id' => $salaire->id,
                'created_by' => Auth::id(),
            ]);
        });
    }

    /** Annule le paiement d'un salaire (erreur) : la dépense associée est supprimée */
    public function annulerPaiement(Salaire $salaire): void
    {
        DB::transaction(function () use ($salaire) {
            $salaire->depense()?->delete();
            $salaire->update(['statut' => 'calcule', 'date_paiement' => null, 'mode_paiement' => null]);
        });
    }

    /**
     * Bilan sur une période : recettes (paiements validés), dépenses, solde, caisse espèces, détail mensuel.
     */
    public function bilan(Carbon $debut, Carbon $fin): array
    {
        $d = $debut->toDateString();
        $f = $fin->toDateString();

        $recettes = Paiement::where('statut', 'valide')->whereBetween('date_paiement', [$d, $f]);
        $depenses = Depense::whereBetween('date_depense', [$d, $f]);

        $mois = [];
        foreach (\Carbon\CarbonPeriod::create($debut->copy()->startOfMonth(), '1 month', $fin->copy()->startOfMonth()) as $m) {
            $a = $m->copy()->startOfMonth()->toDateString();
            $b = $m->copy()->endOfMonth()->toDateString();
            $mois[] = [
                'libelle' => ucfirst($m->translatedFormat('M Y')),
                'recettes' => (float) Paiement::where('statut', 'valide')->whereBetween('date_paiement', [$a, $b])->sum('montant'),
                'depenses' => (float) Depense::whereBetween('date_depense', [$a, $b])->sum('montant'),
            ];
        }

        $recettesTotal = (float) (clone $recettes)->sum('montant');
        $depensesTotal = (float) (clone $depenses)->sum('montant');

        return [
            'recettes' => $recettesTotal,
            'depenses' => $depensesTotal,
            'solde' => round($recettesTotal - $depensesTotal, 2),
            'recettes_par_mode' => (clone $recettes)->selectRaw('methode_paiement, SUM(montant) as total')->groupBy('methode_paiement')->pluck('total', 'methode_paiement')->all(),
            'depenses_par_categorie' => (clone $depenses)->selectRaw('categorie, SUM(montant) as total')->groupBy('categorie')->orderByDesc('total')->pluck('total', 'categorie')->all(),
            // Caisse : mouvements en espèces de la période
            'caisse_entrees' => (float) (clone $recettes)->where('methode_paiement', 'especes')->sum('montant'),
            'caisse_sorties' => (float) (clone $depenses)->where('mode_paiement', 'especes')->sum('montant'),
            'impayes' => (float) \App\Models\Echeancier::where('montant_restant', '>', 0)->whereDate('date_echeance', '<=', $f)->sum('montant_restant'),
            'mois' => $mois,
        ];
    }
}
