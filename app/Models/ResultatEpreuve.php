<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultatEpreuve extends Model
{
    protected $table = 'resultats_epreuves';
    protected $fillable = ['epreuve_id', 'stagiaire_id', 'note', 'absent', 'note_id', 'saisi_par'];
    protected $casts = ['absent' => 'boolean', 'note' => 'decimal:2'];

    public function epreuve() { return $this->belongsTo(Epreuve::class); }
    public function stagiaire() { return $this->belongsTo(Stagiaire::class); }
}
