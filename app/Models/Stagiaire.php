<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Stagiaire extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'nom',
        'prenom',
        'matricule',
        'date_naissance',
        'lieu_naissance',
        'sexe',
        'telephone',
        'email',
        'adresse',
        'nom_tuteur',
        'telephone_tuteur',
        'email_tuteur',
        'photo',
        'filiere_id',
        'created_by',
        'is_active',
        'classe_id',
        'niveau_id',
        'date_inscription',
        'statut',
        'motif_statut',
        'frais_inscription',
        'frais_payes',
        // Nouveaux champs pour les paiements
        'total_a_payer',
        'total_paye',
        'solde_restant',
        'statut_paiement',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_inscription' => 'date',
        'is_active' => 'boolean',
        'frais_payes' => 'boolean',
        'frais_inscription' => 'decimal:2',
        'total_a_payer' => 'decimal:2',
        'total_paye' => 'decimal:2',
        'solde_restant' => 'decimal:2',
    ];

    protected static $logAttributes = [
        'nom', 'prenom', 'matricule', 'filiere_id', 'classe_id', 'statut'
    ];

    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nom', 'prenom', 'matricule', 'filiere_id', 'classe_id', 'statut'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function booted()
    {
        static::creating(function ($stagiaire) {
            if (empty($stagiaire->date_inscription)) {
                $stagiaire->date_inscription = now()->toDateString();
            }
        });
    }

    // ========================================================================
    // RELATIONS EXISTANTES
    // ========================================================================

    public function filiere()
    {
        return $this->belongsTo(Filiere::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function absences()
    {
        return $this->hasMany(Absence::class);
    }

    public function bulletins()
    {
        return $this->hasMany(Bulletin::class);
    }

    // ========================================================================
    // NOUVELLES RELATIONS - SYSTÈME DE PAIEMENT
    // ========================================================================

    /** Comptes parents / tuteurs ayant accès au suivi */
    public function parents()
    {
        return $this->belongsToMany(User::class, 'parent_stagiaire', 'stagiaire_id', 'user_id')
            ->withPivot('lien')->withTimestamps();
    }

    /**
     * Inscriptions annuelles (historique de scolarité)
     */
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class)->orderByDesc('annee_scolaire_id');
    }

    public function inscriptionActive()
    {
        return $this->hasOne(Inscription::class)
            ->whereHas('anneeScolaire', fn ($q) => $q->where('is_active', true));
    }

    /**
     * Relation avec les paiements
     */
    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * Relation avec les échéanciers
     */
    public function echeanciers()
    {
        return $this->hasMany(Echeancier::class);
    }

    /**
     * Relation avec les remises
     */
    public function remises()
    {
        return $this->hasMany(Remise::class);
    }

    /**
     * Obtenir les paiements validés
     */
    public function paiementsValides()
    {
        return $this->hasMany(Paiement::class)->where('statut', 'valide');
    }

    /**
     * Obtenir les échéanciers impayés
     */
    public function echeanciersImpayes()
    {
        return $this->hasMany(Echeancier::class)
            ->whereIn('statut', ['impaye', 'paye_partiel', 'en_retard']);
    }

    /**
     * Obtenir les échéanciers en retard
     */
    public function echeanciersEnRetard()
    {
        return $this->hasMany(Echeancier::class)
            ->where(function($q) {
                $q->where('statut', 'en_retard')
                  ->orWhere(function($sq) {
                      $sq->where('statut', 'impaye')
                         ->where('date_echeance', '<', now());
                  });
            });
    }

    // ========================================================================
    // SCOPES EXISTANTS
    // ========================================================================

    public function scopeActifs($query)
    {
        return $query->where('is_active', true)->where('statut', 'actif');
    }

    public function scopeParFiliere($query, $filiereId)
    {
        return $query->where('filiere_id', $filiereId);
    }

    public function scopeParClasse($query, $classeId)
    {
        return $query->where('classe_id', $classeId);
    }

    public function scopeParStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    // ========================================================================
    // ACCESSORS EXISTANTS
    // ========================================================================

    public function getNomCompletAttribute()
    {
        return "{$this->nom} {$this->prenom}";
    }

    public function getAgeAttribute()
    {
        return $this->date_naissance ? $this->date_naissance->age : null;
    }

    public function getStatutLibelleAttribute()
    {
        return match($this->statut) {
            'actif' => 'Actif',
            'suspendu' => 'Suspendu',
            'diplome' => 'Diplômé',
            'abandonne' => 'Abandonné',
            'transfere' => 'Transféré',
            default => 'Non défini'
        };
    }

    public function getPhotoUrlAttribute()
    {
        return $this->photo ? asset('storage/' . $this->photo) : asset('images/default-avatar.png');
    }

    // ========================================================================
    // NOUVEAUX ACCESSORS - SYSTÈME DE PAIEMENT
    // ========================================================================

    /**
     * Obtenir le statut de paiement libellé
     */
    public function getStatutPaiementLibelleAttribute()
    {
        return match($this->statut_paiement) {
            'en_attente' => 'En attente',
            'a_jour' => 'À jour',
            'en_cours' => 'En cours',
            'en_retard' => 'En retard',
            'suspendu' => 'Suspendu',
            default => 'Non défini'
        };
    }

    /**
     * Obtenir le badge couleur pour le statut de paiement
     */
    public function getStatutPaiementBadgeAttribute()
    {
        return match($this->statut_paiement) {
            'a_jour' => 'success',
            'en_cours' => 'primary',
            'en_retard' => 'danger',
            'suspendu' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Calculer le pourcentage de paiement
     */
    public function getPourcentagePaiementAttribute()
    {
        if ($this->total_a_payer == 0) {
            return 0;
        }

        return round(($this->total_paye / $this->total_a_payer) * 100, 1);
    }

    // ========================================================================
    // MÉTHODES HELPERS EXISTANTES
    // ========================================================================

    public function getMoyenneGenerale($periodeId = null)
    {
        $query = $this->notes();
        
        if ($periodeId) {
            $query->where('periode_id', $periodeId);
        }

        $notes = $query->with('matiere')->get();

        if ($notes->isEmpty()) {
            return 0;
        }

        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($notes->groupBy('matiere_id') as $notesMatiere) {
            $matiere = $notesMatiere->first()->matiere;
            $moyenne = $notesMatiere->avg('note');
            
            $totalPoints += $moyenne * $matiere->coefficient;
            $totalCoefficients += $matiere->coefficient;
        }

        return $totalCoefficients > 0 ? round($totalPoints / $totalCoefficients, 2) : 0;
    }

    public function getTotalAbsences($periodeId = null)
    {
        $query = $this->absences();
        
        if ($periodeId) {
            $periode = Periode::find($periodeId);
            if ($periode) {
                $query->whereBetween('date', [$periode->debut, $periode->fin]);
            }
        }

        return $query->count();
    }

    public function getAbsencesInjustifiees($periodeId = null)
    {
        $query = $this->absences()->where('justifiee', false);
        
        if ($periodeId) {
            $periode = Periode::find($periodeId);
            if ($periode) {
                $query->whereBetween('date', [$periode->debut, $periode->fin]);
            }
        }

        return $query->count();
    }

    // ========================================================================
    // NOUVELLES MÉTHODES - SYSTÈME DE PAIEMENT
    // ========================================================================

    /**
     * Mettre à jour le solde de paiement
     */
    public function updateSoldePaiement()
    {
        // Tout est calculé à partir des échéances (elles-mêmes alimentées par les seuls paiements validés)
        $echeances = $this->echeanciers();

        $totalAPayer = (float) (clone $echeances)->sum(\Illuminate\Support\Facades\DB::raw('montant - montant_remise'));
        $totalPaye = (float) (clone $echeances)->sum('montant_paye');
        $soldeRestant = (float) (clone $echeances)->sum('montant_restant');
        $enRetard = (clone $echeances)->where('montant_restant', '>', 0)
            ->whereDate('date_echeance', '<', now()->toDateString())
            ->exists();

        $statut = match (true) {
            $totalAPayer <= 0  => 'en_attente',
            $soldeRestant <= 0 => 'a_jour',
            $enRetard          => 'en_retard',
            default            => 'en_cours',
        };

        $this->update([
            'total_a_payer'   => $totalAPayer,
            'total_paye'      => $totalPaye,
            'solde_restant'   => $soldeRestant,
            'statut_paiement' => $statut,
        ]);

        return $this;
    }

    /**
     * Vérifier si le stagiaire a des paiements en retard
     */
    public function hasRetardsPaiement()
    {
        return $this->echeanciers()
            ->where('statut', 'en_retard')
            ->exists();
    }

    /**
     * Obtenir le prochain échéancier à payer
     */
    public function getProchainEcheancier()
    {
        return $this->echeanciers()
            ->whereIn('statut', ['impaye', 'paye_partiel', 'en_retard'])
            ->orderBy('date_echeance', 'asc')
            ->first();
    }

    /**
     * Obtenir les remises actives
     */
    public function getRemisesActives()
    {
        return $this->remises()
            ->where('is_active', true)
            ->where('date_debut', '<=', now())
            ->where(function($q) {
                $q->whereNull('date_fin')
                  ->orWhere('date_fin', '>=', now());
            })
            ->get();
    }

    /**
     * Calculer le montant total des remises
     */
    public function calculerMontantRemises($montantBase)
    {
        $remises = $this->getRemisesActives();
        $totalRemise = 0;

        foreach ($remises as $remise) {
            if ($remise->type === 'pourcentage') {
                $totalRemise += ($montantBase * $remise->valeur) / 100;
            } else {
                $totalRemise += min($remise->valeur, $montantBase);
            }
        }

        return $totalRemise;
    }

    /**
     * Vérifier si le stagiaire peut accéder aux cours
     */
    public function canAccessCours()
    {
        // Logique métier : bloquer l'accès si plus de 2 mois de retard
        $monthsRetard = $this->echeanciers()
            ->where('statut', 'en_retard')
            ->where('date_echeance', '<', now()->subMonths(2))
            ->count();

        return $monthsRetard < 2;
    }

    /**
     * Générer un rapport de paiement complet
     */
    public function genererRapportPaiement()
    {
        return [
            'stagiaire' => [
                'nom_complet' => $this->nom_complet,
                'matricule' => $this->matricule,
                'filiere' => $this->filiere->nom ?? 'N/A',
                'classe' => $this->classe->nom ?? 'N/A',
            ],
            'financier' => [
                'total_a_payer' => $this->total_a_payer,
                'total_paye' => $this->total_paye,
                'solde_restant' => $this->solde_restant,
                'pourcentage' => $this->pourcentage_paiement,
                'statut' => $this->statut_paiement_libelle,
            ],
            'echeanciers' => [
                'total' => $this->echeanciers()->count(),
                'payes' => $this->echeanciers()->where('statut', 'paye')->count(),
                'impayes' => $this->echeanciersImpayes()->count(),
                'en_retard' => $this->echeanciersEnRetard()->count(),
            ],
            'paiements' => [
                'total' => $this->paiements()->count(),
                'valides' => $this->paiementsValides()->count(),
                'en_attente' => $this->paiements()->where('statut', 'en_attente')->count(),
            ],
            'remises' => [
                'actives' => $this->getRemisesActives()->count(),
            ],
        ];
    }
}