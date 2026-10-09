<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cycle de vie d'un paiement :
 *   en_attente ──valider──▶ valide ──annuler──▶ annule
 *        └──────refuser──▶ refuse
 * Seul un paiement VALIDÉ est imputé sur les échéances (voir App\Services\PaiementService).
 */
class Paiement extends Model
{
    use HasFactory;

    public const TYPES = [
        'inscription' => 'Frais d\'inscription',
        'mensualite'  => 'Mensualité',
        'examen'      => 'Frais d\'examen',
        'autre'       => 'Autre',
    ];

    public const METHODES = [
        'especes'      => 'Espèces',
        'cheque'       => 'Chèque',
        'virement'     => 'Virement bancaire',
        'carte'        => 'Carte bancaire',
        'mobile_money' => 'Mobile Money',
    ];

    protected $fillable = [
        'stagiaire_id',
        'user_id',
        'numero_transaction',
        'montant',
        'type_paiement',
        'methode_paiement',
        'statut',
        'date_paiement',
        'reference_externe',
        'gateway',
        'description',
        'metadata',
        'date_echeance',
        'valide_at',
        'valide_by',
        'recu_path',
        'justificatif_path',
        'notes_admin',
        'notes_stagiaire',
    ];

    protected $casts = [
        'date_paiement' => 'date',
        'date_echeance' => 'date',
        'valide_at' => 'datetime',
        'montant' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        // Numéro provisoire unique, remplacé juste après l'insertion par PAY-AAAA-000ID
        static::creating(function ($paiement) {
            if (empty($paiement->numero_transaction)) {
                $paiement->numero_transaction = 'TMP-' . Str::uuid();
            }
        });

        static::created(function ($paiement) {
            if (str_starts_with($paiement->numero_transaction, 'TMP-')) {
                $numero = 'PAY-' . now()->format('Y') . '-' . str_pad((string) $paiement->id, 6, '0', STR_PAD_LEFT);
                $paiement->numero_transaction = $numero;
                static::whereKey($paiement->id)->update(['numero_transaction' => $numero]);
            }
        });
    }

    /* ------------------------- RELATIONS ------------------------- */

    public function stagiaire()
    {
        return $this->belongsTo(Stagiaire::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function validateur()
    {
        return $this->belongsTo(User::class, 'valide_by');
    }

    public function echeanciers()
    {
        return $this->belongsToMany(Echeancier::class, 'echeancier_paiement')
            ->withPivot('montant_affecte')
            ->withTimestamps();
    }

    /* ------------------------- SCOPES ------------------------- */

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeValides($query)
    {
        return $query->where('statut', 'valide');
    }

    public function scopeRefuses($query)
    {
        return $query->where('statut', 'refuse');
    }

    /* ------------------------- ACCESSORS ------------------------- */

    public function getTypeLibelleAttribute()
    {
        return self::TYPES[$this->type_paiement] ?? 'Non défini';
    }

    public function getMethodeLibelleAttribute()
    {
        return self::METHODES[$this->methode_paiement] ?? 'Non défini';
    }

    public function getStatutLibelleAttribute()
    {
        return match ($this->statut) {
            'en_attente' => 'En attente',
            'valide' => 'Validé',
            'refuse' => 'Refusé',
            'annule', 'rembourse' => 'Annulé',
            default => 'Non défini'
        };
    }

    public function getStatutCouleurAttribute()
    {
        return match ($this->statut) {
            'valide' => 'success',
            'en_attente' => 'warning',
            'refuse' => 'danger',
            default => 'secondary',
        };
    }

    /** Échéances choisies à la saisie (imputées à la validation) */
    public function getEcheancesCiblesAttribute(): array
    {
        return $this->metadata['echeances_cibles'] ?? [];
    }

    public function estValide(): bool
    {
        return $this->statut === 'valide';
    }

    /**
     * Situation financière du stagiaire pour l'année scolaire concernée (reçu PDF).
     */
    public function situationFinanciere(): array
    {
        $this->loadMissing('echeanciers.anneeScolaire');

        $annee = optional($this->echeanciers->first())->anneeScolaire
            ?? AnneeScolaire::where('is_active', true)->first();

        $echeances = Echeancier::where('stagiaire_id', $this->stagiaire_id)
            ->when($annee, fn ($q) => $q->where('annee_scolaire_id', $annee->id));

        $totalDu = (float) (clone $echeances)->sum(DB::raw('montant - montant_remise'));
        $totalPaye = (float) (clone $echeances)->sum('montant_paye');

        return [
            'annee'      => $annee,
            'total_du'   => $totalDu,
            'total_paye' => $totalPaye,
            'reste'      => max(0, round($totalDu - $totalPaye, 2)),
            'prochaine'  => (clone $echeances)->where('montant_restant', '>', 0)->orderBy('date_echeance')->first(),
        ];
    }
}
