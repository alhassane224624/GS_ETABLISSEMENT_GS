<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\ConfigurationPaiement;
use App\Models\Echeancier;
use App\Models\Inscription;
use App\Models\Niveau;
use App\Models\Stagiaire;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cycle annuel :
 *  1. inscription dans une classe de l'année (création du stagiaire, changement de classe) ;
 *  2. délibération de fin d'année : moyenne annuelle (bulletins validés) → admis / redouble / diplômé / exclu ;
 *  3. passage : création des inscriptions de l'année suivante selon la décision ;
 *  4. activation de la nouvelle année : la classe courante de chaque stagiaire bascule.
 * L'effectif d'une classe = nombre d'inscriptions « inscrit » dans cette classe.
 */
class InscriptionService
{
    /** Seuil d'admission (paramètre « seuil_admission », 10 par défaut) */
    /** Seuil d'accès au rattrapage (paramètre « seuil_rattrapage ») ; null = pas de proposition automatique */
    public function seuilRattrapage(): ?float
    {
        $v = ConfigurationPaiement::get('seuil_rattrapage');
        return ($v === null || $v === '') ? null : (float) $v;
    }

    public function seuil(): float
    {
        $v = ConfigurationPaiement::get('seuil_admission', 10);
        return $v === null || $v === '' ? 10.0 : (float) $v;
    }

    // ------------------------------------------------------------------
    // Inscription courante (création / modification d'un stagiaire)
    // ------------------------------------------------------------------

    /** Aligne l'inscription de l'année sur la classe actuelle du stagiaire */
    public function enregistrerCourante(Stagiaire $stagiaire): ?Inscription
    {
        $anneeId = $stagiaire->classe_id
            ? Classe::whereKey($stagiaire->classe_id)->value('annee_scolaire_id')
            : AnneeScolaire::where('is_active', true)->value('id');

        if (!$anneeId) {
            return null;
        }

        $inscription = Inscription::firstOrNew([
            'stagiaire_id' => $stagiaire->id,
            'annee_scolaire_id' => $anneeId,
        ]);
        $ancienneClasse = $inscription->classe_id;

        $inscription->fill([
            'filiere_id' => $stagiaire->filiere_id,
            'niveau_id' => $stagiaire->niveau_id,
            'classe_id' => $stagiaire->classe_id,
            'date_inscription' => $inscription->date_inscription ?? ($stagiaire->date_inscription ?? now()),
            'statut' => in_array($stagiaire->statut, ['abandonne', 'transfere'])
                ? 'abandon'
                : (in_array($inscription->statut, [null, 'abandon'], true) ? 'inscrit' : $inscription->statut),
            'created_by' => $inscription->created_by ?? Auth::id(),
        ])->save();

        $this->recompter($ancienneClasse);
        $this->recompter($stagiaire->classe_id);

        return $inscription;
    }

    /** Places libres dans une classe (en ignorant éventuellement un stagiaire déjà dedans) */
    public function placesLibres(int $classeId, ?int $stagiaireId = null): int
    {
        $classe = Classe::findOrFail($classeId);
        $inscrits = Inscription::where('classe_id', $classeId)->where('statut', 'inscrit')
            ->when($stagiaireId, fn ($q) => $q->where('stagiaire_id', '!=', $stagiaireId))
            ->count();

        return max(0, $classe->effectif_max - $inscrits);
    }

    public function recompter(?int $classeId): void
    {
        if (!$classeId) {
            return;
        }
        $n = Inscription::where('classe_id', $classeId)->where('statut', 'inscrit')->count();
        Classe::whereKey($classeId)->update(['effectif_actuel' => $n]);
    }

    // ------------------------------------------------------------------
    // Délibération
    // ------------------------------------------------------------------

    /** Moyenne annuelle = moyenne des bulletins VALIDÉS des périodes de l'année */
    public function moyenneAnnuelle(int $stagiaireId, int $anneeId): ?float
    {
        // Après une session de rattrapage, les meilleures notes de rattrapage remplacent les moyennes de matière
        $apresRattrapage = app(ExamenService::class)->moyenneApresRattrapage($stagiaireId, $anneeId);
        if ($apresRattrapage !== null) {
            return $apresRattrapage;
        }

        $moyenne = Bulletin::where('stagiaire_id', $stagiaireId)
            ->whereNotNull('validated_at')
            ->whereHas('periode', fn ($q) => $q->where('annee_scolaire_id', $anneeId))
            ->avg('moyenne_generale');

        return $moyenne === null ? null : round((float) $moyenne, 2);
    }

    public function niveauSuivant(?Niveau $niveau): ?Niveau
    {
        if (!$niveau) {
            return null;
        }
        return Niveau::where('filiere_id', $niveau->filiere_id)
            ->where('ordre', '>', $niveau->ordre)
            ->orderBy('ordre')
            ->first();
    }

    /** Lignes de délibération d'une classe : inscription, moyenne, décision proposée */
    public function propositions(Classe $classe): Collection
    {
        $seuil = $this->seuil();
        $classe->loadMissing('niveau');
        $dernierNiveau = $this->niveauSuivant($classe->niveau) === null;

        return Inscription::with('stagiaire')
            ->where('classe_id', $classe->id)
            ->whereIn('statut', ['inscrit', 'termine'])
            ->get()
            ->sortBy(fn ($i) => $i->stagiaire->nom . ' ' . $i->stagiaire->prenom)
            ->map(function (Inscription $i) use ($classe, $seuil, $dernierNiveau) {
                $moyenne = $this->moyenneAnnuelle($i->stagiaire_id, $classe->annee_scolaire_id);
                $aPasseRattrapage = (bool) app(ExamenService::class)->notesRattrapage($i->stagiaire_id, $classe->annee_scolaire_id);
                $seuilRattrapage = $this->seuilRattrapage();

                $proposition = match (true) {
                    $moyenne === null => null,
                    $moyenne >= $seuil => $dernierNiveau ? 'diplome' : 'admis',
                    !$aPasseRattrapage && $seuilRattrapage !== null && $moyenne >= $seuilRattrapage => 'rattrapage',
                    default => 'redouble',
                };

                return ['inscription' => $i, 'moyenne' => $moyenne, 'proposition' => $proposition];
            })->values();
    }

    /** @param array $decisions [inscription_id => ['decision' => ..., 'observation' => ...]] */
    public function enregistrerDecisions(Classe $classe, array $decisions): int
    {
        $n = 0;
        DB::transaction(function () use ($classe, $decisions, &$n) {
            foreach ($decisions as $id => $d) {
                $inscription = Inscription::where('classe_id', $classe->id)->find($id);
                if (!$inscription || empty($d['decision'])) {
                    continue;
                }
                $inscription->update([
                    'decision' => $d['decision'],
                    'observation' => $d['observation'] ?? null,
                    'moyenne_annuelle' => $this->moyenneAnnuelle($inscription->stagiaire_id, $classe->annee_scolaire_id),
                    'decide_at' => now(),
                    'decide_by' => Auth::id(),
                ]);
                $n++;
            }
        });
        return $n;
    }

    // ------------------------------------------------------------------
    // Passage vers l'année suivante
    // ------------------------------------------------------------------

    /**
     * @param array $affectations [classe_source_id => ['admis' => classe_cible_id, 'redouble' => classe_cible_id]]
     * @return array rapport : créés, déjà inscrits, diplômés, sortis, erreurs[]
     */
    public function passage(AnneeScolaire $source, AnneeScolaire $cible, array $affectations, float $fraisReinscription = 0): array
    {
        if ($source->id === $cible->id) {
            throw ValidationException::withMessages(['annee_cible' => 'L\'année cible doit être différente de l\'année source.']);
        }

        $rapport = ['crees' => 0, 'deja' => 0, 'diplomes' => 0, 'sortis' => 0, 'erreurs' => []];

        DB::transaction(function () use ($source, $cible, $affectations, $fraisReinscription, &$rapport) {
            $inscriptions = Inscription::with(['stagiaire', 'niveau', 'classe'])
                ->where('annee_scolaire_id', $source->id)
                ->whereNotNull('decision')
                ->where('statut', '!=', 'abandon')
                ->get();

            foreach ($inscriptions as $i) {
                $nom = $i->stagiaire->nom . ' ' . $i->stagiaire->prenom;

                // Fin de parcours
                if (in_array($i->decision, ['diplome', 'exclu'])) {
                    $i->update(['statut' => 'termine']);
                    $i->stagiaire->update([
                        'statut' => $i->decision === 'diplome' ? 'diplome' : 'abandonne',
                        'motif_statut' => $i->decision === 'diplome' ? 'Diplômé(e) ' . $source->nom : 'Non réadmis(e) à l\'issue de ' . $source->nom,
                    ]);
                    if ($i->decision === 'exclu') {
                        $i->stagiaire->user?->update(['is_active' => false]);
                    }
                    $rapport[$i->decision === 'diplome' ? 'diplomes' : 'sortis']++;
                    continue;
                }

                if ($i->decision === 'rattrapage') {
                    $rapport['erreurs'][] = "{$nom} : décision provisoire « rattrapage » — délibérez après la session de rattrapage.";
                    continue;
                }

                if (Inscription::where('stagiaire_id', $i->stagiaire_id)->where('annee_scolaire_id', $cible->id)->exists()) {
                    $rapport['deja']++;
                    continue;
                }

                $cibleId = $affectations[$i->classe_id][$i->decision] ?? null;
                if (!$cibleId) {
                    $rapport['erreurs'][] = "{$nom} : aucune classe cible choisie pour « {$i->decision_libelle} » ({$i->classe?->nom}).";
                    continue;
                }

                $classeCible = Classe::find($cibleId);
                $niveauAttendu = $i->decision === 'admis' ? $this->niveauSuivant($i->niveau) : $i->niveau;

                if (!$classeCible || (int) $classeCible->annee_scolaire_id !== (int) $cible->id) {
                    $rapport['erreurs'][] = "{$nom} : la classe cible n'appartient pas à l'année {$cible->nom}.";
                    continue;
                }
                if ((int) $classeCible->filiere_id !== (int) $i->filiere_id
                    || ($niveauAttendu && (int) $classeCible->niveau_id !== (int) $niveauAttendu->id)) {
                    $rapport['erreurs'][] = "{$nom} : {$classeCible->nom} ne correspond pas à la filière / au niveau attendu"
                        . ($niveauAttendu ? " ({$niveauAttendu->nom})" : '') . '.';
                    continue;
                }
                if ($this->placesLibres($classeCible->id) <= 0) {
                    $rapport['erreurs'][] = "{$nom} : la classe {$classeCible->nom} est complète.";
                    continue;
                }

                Inscription::create([
                    'stagiaire_id' => $i->stagiaire_id,
                    'annee_scolaire_id' => $cible->id,
                    'filiere_id' => $classeCible->filiere_id,
                    'niveau_id' => $classeCible->niveau_id,
                    'classe_id' => $classeCible->id,
                    'date_inscription' => now()->toDateString(),
                    'statut' => 'inscrit',
                    'created_by' => Auth::id(),
                ]);
                $this->recompter($classeCible->id);
                $i->update(['statut' => 'termine']);

                if ($fraisReinscription > 0) {
                    Echeancier::create([
                        'stagiaire_id' => $i->stagiaire_id,
                        'annee_scolaire_id' => $cible->id,
                        'titre' => 'Frais de réinscription ' . $cible->nom,
                        'type' => 'inscription',
                        'montant' => $fraisReinscription,
                        'montant_remise' => 0,
                        'montant_paye' => 0,
                        'montant_restant' => $fraisReinscription,
                        'date_echeance' => $cible->debut,
                        'statut' => 'impaye',
                    ])->recalculer();
                    $i->stagiaire->updateSoldePaiement();
                }

                $rapport['crees']++;
            }

            // Les classes de l'année source ne comptent plus d'élèves « en cours »
            Classe::where('annee_scolaire_id', $source->id)->pluck('id')->each(fn ($id) => $this->recompter($id));
        });

        return $rapport;
    }

    /** Recrée dans l'année cible les classes de l'année source (même nom, filière, niveau, capacité) */
    public function copierClasses(AnneeScolaire $source, AnneeScolaire $cible): int
    {
        $n = 0;
        foreach (Classe::where('annee_scolaire_id', $source->id)->get() as $classe) {
            $existe = Classe::where('annee_scolaire_id', $cible->id)->where('nom', $classe->nom)->exists();
            if (!$existe) {
                Classe::create([
                    'nom' => $classe->nom,
                    'filiere_id' => $classe->filiere_id,
                    'niveau_id' => $classe->niveau_id,
                    'annee_scolaire_id' => $cible->id,
                    'effectif_max' => $classe->effectif_max,
                    'effectif_actuel' => 0,
                ]);
                $n++;
            }
        }
        return $n;
    }

    // ------------------------------------------------------------------
    // Activation d'une année
    // ------------------------------------------------------------------

    /** La classe courante de chaque stagiaire devient celle de son inscription dans l'année activée */
    public function synchroniserAnneeActive(AnneeScolaire $annee): array
    {
        $bascules = 0;
        $sansInscription = 0;

        DB::transaction(function () use ($annee, &$bascules, &$sansInscription) {
            Inscription::where('annee_scolaire_id', $annee->id)->where('statut', 'inscrit')
                ->chunkById(300, function ($inscriptions) use (&$bascules) {
                    foreach ($inscriptions as $i) {
                        Stagiaire::whereKey($i->stagiaire_id)->update([
                            'filiere_id' => $i->filiere_id,
                            'niveau_id' => $i->niveau_id,
                            'classe_id' => $i->classe_id,
                        ]);
                        $bascules++;
                    }
                });

            // Stagiaires actifs non réinscrits : plus de classe pour cette année
            $sansInscription = Stagiaire::where('statut', 'actif')
                ->whereDoesntHave('inscriptions', fn ($q) => $q->where('annee_scolaire_id', $annee->id))
                ->update(['classe_id' => null]);
        });

        return ['bascules' => $bascules, 'sans_inscription' => $sansInscription];
    }
}
