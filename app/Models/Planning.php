<?php
// =============================================================================
// FICHIER 8: Planning.php (MIS À JOUR)
// =============================================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Planning extends Model
{
    protected $fillable = [
        'professeur_id',
        'salle_id',
        'matiere_id',
        'classe_id',
        'creneau_id',
        'date',
        'heure_debut',
        'heure_fin',
        'type_cours',
        'description',
        'created_by',
        'validated_by',
        'validated_at',
        'statut',
        'motif_annulation',
        'appel_fait_at',
        'contenu_seance',
        'devoirs',
        'devoirs_pour',
    ];

    protected $casts = [
        'date' => 'date',
        'validated_at' => 'datetime',
        'appel_fait_at' => 'datetime',
        'devoirs_pour' => 'date',
    ];

    // Relations
    public function absences()
    {
        return $this->hasMany(Absence::class);
    }

    public function creneau()
    {
        return $this->belongsTo(Creneau::class);
    }

    public function professeur()
    {
        return $this->belongsTo(User::class, 'professeur_id');
    }

    public function salle()
    {
        return $this->belongsTo(Salle::class);
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Scopes
    public function scopeValides($query)
    {
        return $query->where('statut', 'valide');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeBrouillon($query)
    {
        return $query->where('statut', 'brouillon');
    }

    public function scopeParSemaine($query, $debut, $fin)
    {
        return $query->whereBetween('date', [$debut, $fin]);
    }

    public function scopeParProfesseur($query, $professeurId)
    {
        return $query->where('professeur_id', $professeurId);
    }

    public function scopeParClasse($query, $classeId)
    {
        return $query->where('classe_id', $classeId);
    }

    public function scopeParSalle($query, $salleId)
    {
        return $query->where('salle_id', $salleId);
    }

    public function scopeAujourdhui($query)
    {
        return $query->whereDate('date', today());
    }

    // Accessors
    public function getTypeCoursLibelleAttribute()
    {
        return match($this->type_cours) {
            'cours' => 'Cours magistral',
            'td' => 'Travaux dirigés',
            'tp' => 'Travaux pratiques',
            'examen' => 'Examen',
            default => 'Non défini'
        };
    }

    public function getStatutLibelleAttribute()
    {
        return match($this->statut) {
            'brouillon' => 'Brouillon',
            'valide' => 'Validé',
            'en_cours' => 'En cours',
            'termine' => 'Terminé',
            'annule' => 'Annulé',
            default => 'Non défini'
        };
    }

    public function getStatutColorAttribute()
    {
        return match($this->statut) {
            'brouillon' => 'secondary',
            'valide' => 'success',
            'en_cours' => 'info',
            'termine' => 'primary',
            'annule' => 'danger',
            default => 'secondary'
        };
    }

    public function getDureeAttribute()
    {
        $debut = Carbon::parse($this->heure_debut);
        $fin = Carbon::parse($this->heure_fin);
        return $debut->diffInMinutes($fin) . ' min';
    }

    public function getDureeHeuresAttribute()
    {
        $debut = Carbon::parse($this->heure_debut);
        $fin = Carbon::parse($this->heure_fin);
        $heures = $debut->diffInHours($fin);
        $minutes = $debut->diffInMinutes($fin) % 60;
        
        return $heures . 'h' . ($minutes > 0 ? $minutes : '');
    }

    // Méthodes helpers
    public function isValide()
    {
        return $this->statut === 'valide';
    }

    public function isAnnule()
    {
        return $this->statut === 'annule';
    }

    public function canBeModified()
    {
        return in_array($this->statut, ['brouillon', 'valide']);
    }

    public function canBeValidated()
    {
        return $this->statut === 'brouillon';
    }

    public function canBeAnnulated()
    {
        return in_array($this->statut, ['brouillon', 'valide', 'en_cours']);
    }
}
