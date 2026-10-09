<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentDelivre extends Model
{
    protected $table = 'documents_delivres';

    public const TYPES = [
        'attestation' => 'Attestation de scolarité',
        'certificat'  => 'Certificat d\'inscription',
        'carte'       => 'Carte de stagiaire',
    ];

    public const PREFIXES = ['attestation' => 'ATT', 'certificat' => 'CER', 'carte' => 'CAR'];

    protected $fillable = ['type', 'numero', 'code_verification', 'stagiaire_id', 'annee_scolaire_id', 'contenu', 'delivre_par', 'annule_at'];

    protected $casts = ['contenu' => 'array', 'annule_at' => 'datetime'];

    public function stagiaire() { return $this->belongsTo(Stagiaire::class); }
    public function anneeScolaire() { return $this->belongsTo(AnneeScolaire::class); }
    public function auteur() { return $this->belongsTo(User::class, 'delivre_par'); }

    public function getTypeLibelleAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function estValide(): bool
    {
        return $this->annule_at === null;
    }

    public function getUrlVerificationAttribute(): string
    {
        return route('documents.verifier', $this->code_verification);
    }
}
