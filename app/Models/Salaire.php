<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salaire extends Model
{
    protected $fillable = ['professeur_id', 'mois', 'mode_remuneration', 'heures', 'nb_seances', 'taux_horaire', 'montant_base',
        'primes', 'retenues', 'montant_net', 'statut', 'date_paiement', 'mode_paiement', 'observation', 'created_by'];

    protected $casts = ['mois' => 'date', 'date_paiement' => 'date', 'heures' => 'decimal:2', 'montant_base' => 'decimal:2',
        'primes' => 'decimal:2', 'retenues' => 'decimal:2', 'montant_net' => 'decimal:2', 'taux_horaire' => 'decimal:2'];

    public function professeur() { return $this->belongsTo(User::class, 'professeur_id'); }
    public function depense() { return $this->hasOne(Depense::class); }

    public function estPaye(): bool { return $this->statut === 'paye'; }

    public function getMoisLibelleAttribute(): string
    {
        return ucfirst($this->mois->translatedFormat('F Y'));
    }
}
