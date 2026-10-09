<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Examen extends Model
{
    public const TYPES = ['normale' => 'Session normale', 'rattrapage' => 'Session de rattrapage'];

    protected $fillable = ['annee_scolaire_id', 'periode_id', 'nom', 'type', 'date_debut', 'date_fin', 'statut', 'created_by'];
    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date'];

    public function anneeScolaire() { return $this->belongsTo(AnneeScolaire::class); }
    public function periode() { return $this->belongsTo(Periode::class); }
    public function epreuves() { return $this->hasMany(Epreuve::class)->orderBy('date')->orderBy('heure_debut'); }

    public function estCloturee(): bool { return $this->statut === 'cloturee'; }
    public function estRattrapage(): bool { return $this->type === 'rattrapage'; }
    public function getTypeLibelleAttribute(): string { return self::TYPES[$this->type] ?? $this->type; }
}
