<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Publication d'un professeur pour une classe et une matière :
 * devoir (avec rendu en ligne possible), document ou annonce de résultats.
 */
class Publication extends Model
{
    public const TYPES = [
        'devoir' => 'Devoir',
        'document' => 'Document',
        'resultats' => 'Résultats',
    ];

    public const ICONES = [
        'devoir' => 'fa-pencil-alt',
        'document' => 'fa-file-alt',
        'resultats' => 'fa-poll',
    ];

    public const COULEURS = [
        'devoir' => 'warning',
        'document' => 'info',
        'resultats' => 'success',
    ];

    // Fichiers acceptés (publications et rendus) et taille maximale en Ko
    public const MIMES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp,txt,jpg,jpeg,png,zip';
    public const TAILLE_MAX = 10240;

    protected $fillable = [
        'professeur_id', 'classe_id', 'matiere_id', 'type', 'titre', 'contenu',
        'fichier_path', 'fichier_nom', 'date_limite', 'accepte_rendu', 'notes_publiees',
    ];

    protected $casts = [
        'date_limite' => 'datetime',
        'accepte_rendu' => 'boolean',
    ];

    public function professeur() { return $this->belongsTo(User::class, 'professeur_id'); }
    public function classe() { return $this->belongsTo(Classe::class); }
    public function matiere() { return $this->belongsTo(Matiere::class); }
    public function rendus() { return $this->hasMany(Rendu::class); }

    public function getTypeLibelleAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getIconeAttribute(): string
    {
        return self::ICONES[$this->type] ?? 'fa-bullhorn';
    }

    public function getCouleurAttribute(): string
    {
        return self::COULEURS[$this->type] ?? 'secondary';
    }

    public function estDevoir(): bool
    {
        return $this->type === 'devoir';
    }

    public function echeanceDepassee(): bool
    {
        return $this->date_limite !== null && $this->date_limite->isPast();
    }
}
