<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Depense extends Model
{
    public const CATEGORIES = [
        'salaires'     => 'Salaires',
        'loyer'        => 'Loyer',
        'energie'      => 'Eau / électricité / internet',
        'fournitures'  => 'Fournitures et matériel',
        'entretien'    => 'Entretien et réparations',
        'marketing'    => 'Publicité / marketing',
        'impots'       => 'Impôts et taxes',
        'autre'        => 'Autre',
    ];

    public const MODES = ['especes' => 'Espèces', 'cheque' => 'Chèque', 'virement' => 'Virement', 'carte' => 'Carte'];

    protected $fillable = ['categorie', 'libelle', 'montant', 'date_depense', 'mode_paiement', 'reference', 'fournisseur', 'justificatif', 'salaire_id', 'created_by'];
    protected $casts = ['date_depense' => 'date', 'montant' => 'decimal:2'];

    public function salaire() { return $this->belongsTo(Salaire::class); }
    public function auteur() { return $this->belongsTo(User::class, 'created_by'); }

    public function getCategorieLibelleAttribute(): string { return self::CATEGORIES[$this->categorie] ?? $this->categorie; }
    public function getModeLibelleAttribute(): string { return self::MODES[$this->mode_paiement] ?? $this->mode_paiement; }
}
