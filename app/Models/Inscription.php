<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inscription extends Model
{
    public const DECISIONS = [
        'admis'    => 'Admis(e) en niveau supérieur',
        'redouble' => 'Redouble',
        'rattrapage' => 'Admis(e) au rattrapage',
        'diplome'  => 'Diplômé(e)',
        'exclu'    => 'Exclu(e) / non réadmis',
    ];

    public const STATUTS = [
        'inscrit' => 'Inscrit',
        'termine' => 'Année terminée',
        'abandon' => 'Abandon',
    ];

    protected $fillable = [
        'stagiaire_id', 'annee_scolaire_id', 'filiere_id', 'niveau_id', 'classe_id',
        'date_inscription', 'statut', 'decision', 'moyenne_annuelle', 'observation',
        'decide_at', 'decide_by', 'created_by',
    ];

    protected $casts = [
        'date_inscription' => 'date',
        'decide_at' => 'datetime',
        'moyenne_annuelle' => 'decimal:2',
    ];

    public function stagiaire() { return $this->belongsTo(Stagiaire::class); }
    public function anneeScolaire() { return $this->belongsTo(AnneeScolaire::class); }
    public function filiere() { return $this->belongsTo(Filiere::class); }
    public function niveau() { return $this->belongsTo(Niveau::class); }
    public function classe() { return $this->belongsTo(Classe::class); }
    public function decideur() { return $this->belongsTo(User::class, 'decide_by'); }

    public function getDecisionLibelleAttribute(): string
    {
        return self::DECISIONS[$this->decision] ?? 'En attente';
    }

    public function getDecisionCouleurAttribute(): string
    {
        return match ($this->decision) {
            'admis', 'diplome' => 'success',
            'redouble' => 'warning',
            'rattrapage' => 'info',
            'exclu' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatutLibelleAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }
}
