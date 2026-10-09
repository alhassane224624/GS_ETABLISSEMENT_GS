<?php
// =============================================================================
// FICHIER 10: Absence.php (MIS À JOUR)
// =============================================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Absence extends Model
{
    use LogsActivity;

    protected $fillable = [
        'stagiaire_id',
        'date',
        'heure_debut',
        'heure_fin',
        'type',
        'motif',
        'justifiee',
        'document_justificatif',
        'created_by',
        'periode_id',
        'planning_id',
        'retard_minutes',
        'justification_statut',
        'justification_motif',
        'justification_soumise_at',
        'justification_traitee_by',
        'justification_traitee_at',
        'justification_commentaire',
    ];

    protected $casts = [
        'date' => 'date',
        'justifiee' => 'boolean',
        'justification_soumise_at' => 'datetime',
        'justification_traitee_at' => 'datetime',
    ];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['stagiaire_id', 'date', 'type', 'justifiee'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relations
    public function stagiaire()
    {
        return $this->belongsTo(Stagiaire::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeJustifiees($query)
    {
        return $query->where('justifiee', true);
    }

    public function scopeInjustifiees($query)
    {
        return $query->where('justifiee', false);
    }

    public function scopeParPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date', [$debut, $fin]);
    }
    public function periode()
{
    return $this->belongsTo(Periode::class);
}


    public function scopeAujourdhui($query)
    {
        return $query->whereDate('date', today());
    }

    // Accessors
    public function getTypeLibelleAttribute()
    {
        if ($this->retard_minutes !== null) {
            return 'Retard (' . $this->retard_minutes . ' min)';
        }
        return match($this->type) {
            'matin' => 'Matin',
            'apres_midi' => 'Après-midi',
            'journee' => 'Journée complète',
            'heure' => 'Par heure',
            default => 'Non défini'
        };
    }

    public function getDureeAttribute()
    {
        if ($this->retard_minutes !== null) {
            return $this->retard_minutes . ' min';
        }
        if ($this->type === 'heure' && $this->heure_debut && $this->heure_fin) {
            $debut = \Carbon\Carbon::parse($this->heure_debut);
            $fin = \Carbon\Carbon::parse($this->heure_fin);
            return $debut->diffInHours($fin) . 'h';
        }

        return match($this->type) {
            'matin' => '4h',
            'apres_midi' => '4h',
            'journee' => '8h',
            default => '-'
        };
    }

    /**
     * Vérifie si une absence chevauche une absence déjà enregistrée le même jour.
     * Matin et après-midi peuvent coexister ; "journée" bloque tout le reste ;
     * deux absences "heure" ne doivent pas se chevaucher.
     */
    public static function chevauche(int $stagiaireId, string $date, string $type, ?string $debut = null, ?string $fin = null, ?int $ignorerId = null): bool
    {
        $existantes = static::where('stagiaire_id', $stagiaireId)
            ->whereDate('date', $date)
            ->when($ignorerId, fn ($q) => $q->where('id', '!=', $ignorerId))
            ->get();

        foreach ($existantes as $a) {
            if ($type === 'journee' || $a->type === 'journee') {
                return true;
            }
            if ($type === $a->type && in_array($type, ['matin', 'apres_midi'])) {
                return true;
            }
            if ($type === 'heure' && $a->type === 'heure' && $debut && $fin && $a->heure_debut && $a->heure_fin) {
                if ($debut < substr($a->heure_fin, 0, 5) && $fin > substr($a->heure_debut, 0, 5)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Période scolaire qui contient la date (pour rattacher l'absence au bulletin)
     */
    public static function periodePourDate(string $date): ?int
    {
        return Periode::whereDate('debut', '<=', $date)->whereDate('fin', '>=', $date)->value('id');
    }

    public function planning()
    {
        return $this->belongsTo(Planning::class);
    }

    public function getEstRetardAttribute(): bool
    {
        return $this->retard_minutes !== null;
    }

    /** Le stagiaire peut-il (encore) déposer une justification ? */
    public function getPeutEtreJustifieeAttribute(): bool
    {
        return !$this->justifiee && $this->justification_statut !== 'en_attente';
    }

    public function getJustificationLibelleAttribute(): ?string
    {
        return match ($this->justification_statut) {
            'en_attente' => 'Justificatif en cours d\'examen',
            'acceptee' => 'Justificatif accepté',
            'refusee' => 'Justificatif refusé',
            default => null,
        };
    }
}
