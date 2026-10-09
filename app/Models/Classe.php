<?php
// =============================================================================
// FICHIER 6: Classe.php (NOUVEAU)
// =============================================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    protected $fillable = [
        'nom',
        'niveau_id',
        'filiere_id',
        'annee_scolaire_id',
        'effectif_max',
        'effectif_actuel',
    ];

    protected $casts = [
        'effectif_max' => 'integer',
        'effectif_actuel' => 'integer',
    ];

    // Relations
    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function filiere()
    {
        return $this->belongsTo(Filiere::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    /** Inscriptions annuelles dans cette classe */
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function stagiaires()
    {
        return $this->hasMany(Stagiaire::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function plannings()
    {
        return $this->hasMany(Planning::class);
    }

    public function bulletins()
    {
        return $this->hasMany(Bulletin::class);
    }

    // Scopes
    public function scopeParFiliere($query, $filiereId)
    {
        return $query->where('filiere_id', $filiereId);
    }

    public function scopeParNiveau($query, $niveauId)
    {
        return $query->where('niveau_id', $niveauId);
    }

    public function scopeParAnneeScolaire($query, $anneeScolaireId)
    {
        return $query->where('annee_scolaire_id', $anneeScolaireId);
    }

    public function scopeAvecPlacesDisponibles($query)
    {
        return $query->whereColumn('effectif_actuel', '<', 'effectif_max');
    }

    // Accessors
    public function getNomCompletAttribute()
    {
        return "{$this->nom} - {$this->niveau->nom} - {$this->filiere->nom}";
    }

    public function getPlacesDisponiblesAttribute()
    {
        return max(0, $this->effectif_max - $this->effectif_actuel);
    }

    public function getTauxRemplissageAttribute()
    {
        return $this->effectif_max > 0 ? 
            round(($this->effectif_actuel / $this->effectif_max) * 100, 1) : 0;
    }

    public function getIsFullAttribute()
    {
        return $this->effectif_actuel >= $this->effectif_max;
    }

    // Méthodes helpers
    public function canAddStagiaire()
    {
        return $this->effectif_actuel < $this->effectif_max;
    }

    public function incrementEffectif()
    {
        if ($this->canAddStagiaire()) {
            $this->increment('effectif_actuel');
            return true;
        }
        return false;
    }

    public function decrementEffectif()
    {
        if ($this->effectif_actuel > 0) {
            $this->decrement('effectif_actuel');
            return true;
        }
        return false;
    }
}