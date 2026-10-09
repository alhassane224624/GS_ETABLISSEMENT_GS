<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Remise accordée à un stagiaire.
 *  - pourcentage : X % de chaque échéance concernée
 *  - montant_fixe : X DH déduits de CHAQUE échéance concernée
 * Elle s'applique aux échéances dont la date tombe dans sa période,
 * aux mensualités seulement (porte = mensualite) ou à tout (porte = tout).
 * L'application est faite par App\Services\RemiseService.
 */
class Remise extends Model
{
    public const PORTEES = [
        'mensualite' => 'Mensualités uniquement',
        'tout'       => 'Toutes les échéances',
    ];

    protected $fillable = [
        'stagiaire_id',
        'created_by',
        'titre',
        'type',
        'valeur',
        'porte',
        'motif',
        'date_debut',
        'date_fin',
        'is_active',
    ];

    protected $casts = [
        'valeur' => 'decimal:2',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'is_active' => 'boolean',
    ];

    public function stagiaire()
    {
        return $this->belongsTo(Stagiaire::class);
    }

    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActives($query)
    {
        return $query->where('is_active', true)
            ->where('date_debut', '<=', now())
            ->where(fn ($q) => $q->whereNull('date_fin')->orWhere('date_fin', '>=', now()));
    }

    /** La remise concerne-t-elle cette échéance ? */
    public function concerne(Echeancier $echeance): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if (($this->porte ?? 'mensualite') === 'mensualite' && $echeance->type !== 'mensualite') {
            return false;
        }
        $date = $echeance->date_echeance;
        if ($date->lt($this->date_debut->copy()->startOfDay())) {
            return false;
        }
        return !$this->date_fin || $date->lte($this->date_fin->copy()->endOfDay());
    }

    /** Montant de remise sur une échéance donnée */
    public function montantPour(Echeancier $echeance): float
    {
        $base = (float) $echeance->montant;
        $montant = $this->type === 'pourcentage'
            ? $base * (float) $this->valeur / 100
            : (float) $this->valeur;

        return round(min($montant, $base), 2);
    }

    /** Ancien nom conservé pour compatibilité */
    public function calculerMontant($montantBase)
    {
        return $this->type === 'pourcentage'
            ? ($montantBase * $this->valeur) / 100
            : min($this->valeur, $montantBase);
    }

    public function getTypeLibelleAttribute()
    {
        return $this->type === 'pourcentage'
            ? rtrim(rtrim(number_format($this->valeur, 2, ',', ''), '0'), ',') . ' %'
            : number_format($this->valeur, 2, ',', ' ') . ' DH / échéance';
    }

    public function getPorteLibelleAttribute()
    {
        return self::PORTEES[$this->porte ?? 'mensualite'];
    }
}
