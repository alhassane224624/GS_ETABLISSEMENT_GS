<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Une échéance = une somme due par un stagiaire à une date.
 *   montant_restant = montant - montant_remise - montant_paye
 * Le statut est TOUJOURS déduit des montants et de la date (voir recalculer()).
 */
class Echeancier extends Model
{
    use HasFactory;

    protected $table = 'echeanciers';

    public const TYPES = [
        'inscription' => 'Frais d\'inscription',
        'mensualite'  => 'Mensualité',
        'examen'      => 'Frais d\'examen',
        'autre'       => 'Autre',
    ];

    public const STATUTS_OUVERTS = ['impaye', 'paye_partiel', 'en_retard'];

    protected $fillable = [
        'stagiaire_id',
        'annee_scolaire_id',
        'titre',
        'type',
        'montant',
        'montant_remise',
        'date_echeance',
        'statut',
        'montant_paye',
        'montant_restant',
        'notification_envoyee',
        'notification_sent_at',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'montant_remise' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'montant_restant' => 'decimal:2',
        'date_echeance' => 'date',
        'notification_envoyee' => 'boolean',
        'notification_sent_at' => 'datetime',
    ];

    // ------------------ RELATIONS ------------------
    public function stagiaire()
    {
        return $this->belongsTo(Stagiaire::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function paiements()
    {
        return $this->belongsToMany(Paiement::class, 'echeancier_paiement')
            ->withPivot('montant_affecte')
            ->withTimestamps();
    }

    // ------------------ SCOPES ------------------
    public function scopeOuverts($query)
    {
        return $query->whereIn('statut', self::STATUTS_OUVERTS)->where('montant_restant', '>', 0);
    }

    public function scopeImpayes($query)
    {
        return $query->ouverts();
    }

    public function scopeEnRetard($query)
    {
        return $query->ouverts()->whereDate('date_echeance', '<', now()->toDateString());
    }

    public function scopeAVenir($query)
    {
        return $query->ouverts()->whereDate('date_echeance', '>=', now()->toDateString());
    }

    // ------------------ ACCESSORS ------------------
    public function getStatutLibelleAttribute()
    {
        return match ($this->statut) {
            'impaye' => 'Impayé',
            'paye_partiel' => 'Payé partiellement',
            'paye' => 'Payé',
            'en_retard' => 'En retard',
            default => 'Inconnu',
        };
    }

    public function getStatutCouleurAttribute()
    {
        return match ($this->statut) {
            'paye' => 'success',
            'paye_partiel' => 'info',
            'en_retard' => 'danger',
            default => 'warning',
        };
    }

    public function getTypeLibelleAttribute()
    {
        return self::TYPES[$this->type] ?? 'Autre';
    }

    /** Montant réellement dû après remise */
    public function getMontantNetAttribute()
    {
        return round((float) $this->montant - (float) $this->montant_remise, 2);
    }

    public function getIsEnRetardAttribute()
    {
        return $this->montant_restant > 0 && $this->date_echeance && $this->date_echeance->lt(now()->startOfDay());
    }

    // ------------------ MÉTHODES MÉTIER ------------------

    /**
     * Recalcule montant payé (à partir des imputations de paiements VALIDÉS),
     * montant restant et statut. À appeler après toute modification.
     */
    public function recalculer(): self
    {
        $paye = (float) $this->paiements()->where('paiements.statut', 'valide')->sum('echeancier_paiement.montant_affecte');
        $remise = min((float) $this->montant_remise, max(0, (float) $this->montant - $paye));

        $this->montant_remise = $remise;
        $this->montant_paye = $paye;
        $this->montant_restant = max(0, round((float) $this->montant - $remise - $paye, 2));
        $this->statut = $this->calculerStatut();
        $this->save();

        return $this;
    }

    public function calculerStatut(): string
    {
        if ((float) $this->montant_restant <= 0) {
            return 'paye';
        }
        if ($this->date_echeance && $this->date_echeance->lt(now()->startOfDay())) {
            return 'en_retard';
        }
        return (float) $this->montant_paye > 0 ? 'paye_partiel' : 'impaye';
    }

    /** Imputation d'une partie d'un paiement validé */
    public function affecterPaiement(Paiement $paiement, $montant): void
    {
        $this->paiements()->attach($paiement->id, ['montant_affecte' => $montant]);
        $this->recalculer();
    }

    /** Retire l'imputation d'un paiement (refus tardif, annulation) */
    public function annulerAffectation(Paiement $paiement): void
    {
        $this->paiements()->detach($paiement->id);
        $this->recalculer();
    }
}
