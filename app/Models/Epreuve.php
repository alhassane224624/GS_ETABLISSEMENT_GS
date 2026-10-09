<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Epreuve extends Model
{
    protected $fillable = ['examen_id', 'classe_id', 'matiere_id', 'date', 'heure_debut', 'heure_fin', 'salle_id', 'surveillant_id', 'note_sur', 'planning_id'];
    protected $casts = ['date' => 'date', 'note_sur' => 'decimal:1'];

    public function examen() { return $this->belongsTo(Examen::class); }
    public function classe() { return $this->belongsTo(Classe::class); }
    public function matiere() { return $this->belongsTo(Matiere::class); }
    public function salle() { return $this->belongsTo(Salle::class); }
    public function surveillant() { return $this->belongsTo(User::class, 'surveillant_id'); }
    public function planning() { return $this->belongsTo(Planning::class); }
    public function resultats() { return $this->hasMany(ResultatEpreuve::class); }

    public function getHoraireAttribute(): string
    {
        return substr($this->heure_debut, 0, 5) . ' – ' . substr($this->heure_fin, 0, 5);
    }
}
