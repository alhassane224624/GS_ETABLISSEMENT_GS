<?php

// =============================================================================
// FICHIER 7: Note.php (MIS À JOUR)
// =============================================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = [
        'stagiaire_id',
        'matiere_id',
        'classe_id',
        'note',
        'semestre',
        'periode_id',
        'type_note',
        'note_sur',
        'commentaire',
        'created_by',
    ];

    protected $casts = [
        'note' => 'float',
        'note_sur' => 'decimal:1',
    ];

    // Relations
    public function stagiaire()
    {
        return $this->belongsTo(Stagiaire::class);
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeParStagiaire($query, $stagiaireId)
    {
        return $query->where('stagiaire_id', $stagiaireId);
    }

    public function scopeParMatiere($query, $matiereId)
    {
        return $query->where('matiere_id', $matiereId);
    }

    public function scopeParClasse($query, $classeId)
    {
        return $query->where('classe_id', $classeId);
    }

    public function scopeParPeriode($query, $periodeId)
    {
        return $query->where('periode_id', $periodeId);
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type_note', $type);
    }

    // Accessors
    public function getTypeNoteLibelleAttribute()
    {
        return match($this->type_note) {
            'ds' => 'Devoir Surveillé',
            'cc' => 'Contrôle Continu',
            'examen' => 'Examen',
            'tp' => 'Travaux Pratiques',
            'projet' => 'Projet',
            default => 'Non défini'
        };
    }

    public function getNoteSur20Attribute()
    {
        if ($this->note_sur == 20) {
            return $this->note;
        }
        return round(($this->note / $this->note_sur) * 20, 2);
    }

    public function getAppreciationAttribute()
    {
        $noteSur20 = $this->note_sur_20;
        
        return match(true) {
            $noteSur20 >= 18 => 'Excellent',
            $noteSur20 >= 16 => 'Très bien',
            $noteSur20 >= 14 => 'Bien',
            $noteSur20 >= 12 => 'Assez bien',
            $noteSur20 >= 10 => 'Passable',
            default => 'Insuffisant'
        };
    }

    // Méthodes helpers
    public function isReussite()
    {
        return $this->note_sur_20 >= 10;
    }

    /**
     * Le bulletin du stagiaire pour cette période est-il validé ?
     * Si oui, ses notes de la période ne doivent plus bouger.
     */
    public static function periodeVerrouillee($stagiaireId, $periodeId): bool
    {
        if (!$stagiaireId || !$periodeId) {
            return false;
        }

        return Bulletin::where('stagiaire_id', $stagiaireId)
            ->where('periode_id', $periodeId)
            ->whereNotNull('validated_at')
            ->exists();
    }

    public function estVerrouillee(): bool
    {
        return self::periodeVerrouillee($this->stagiaire_id, $this->periode_id);
    }
}
