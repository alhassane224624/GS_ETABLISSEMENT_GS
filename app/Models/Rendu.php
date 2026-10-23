<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Copie déposée par un stagiaire pour un devoir */
class Rendu extends Model
{
    protected $fillable = ['publication_id', 'stagiaire_id', 'fichier_path', 'fichier_nom', 'commentaire', 'rendu_at'];

    protected $casts = ['rendu_at' => 'datetime'];

    public function publication() { return $this->belongsTo(Publication::class); }
    public function stagiaire() { return $this->belongsTo(Stagiaire::class); }

    public function estEnRetard(): bool
    {
        $limite = $this->publication->date_limite;

        return $limite !== null && $this->rendu_at->gt($limite);
    }
}
