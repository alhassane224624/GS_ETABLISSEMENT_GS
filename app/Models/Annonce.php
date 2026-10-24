<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Annonce / note circulaire de l'administration ou de la comptabilité.
 *
 * Destinataires : les rôles cochés. Si une filière ou des classes sont choisies, la diffusion est limitée
 * aux stagiaires et parents concernés et aux professeurs affectés à la filière ; l'administration et la
 * comptabilité, si elles sont cochées, la reçoivent toujours.
 */
class Annonce extends Model
{
    public const IMPORTANCES = [
        'info' => 'Information',
        'important' => 'Important',
        'urgent' => 'Urgent',
    ];

    public const COULEURS = ['info' => 'info', 'important' => 'warning', 'urgent' => 'danger'];
    public const ICONES = ['info' => 'fa-info-circle', 'important' => 'fa-exclamation-circle', 'urgent' => 'fa-exclamation-triangle'];

    public const ROLES = [
        'professeur' => 'Professeurs',
        'stagiaire' => 'Stagiaires',
        'parent' => 'Parents',
        'comptable' => 'Comptabilité',
        'administrateur' => 'Administration',
    ];

    protected $fillable = [
        'auteur_id', 'titre', 'contenu', 'importance', 'roles', 'filiere_id', 'classe_ids',
        'fichier_path', 'fichier_nom', 'publie_le', 'expire_le', 'epinglee', 'notifiee_at',
    ];

    protected $casts = [
        'roles' => 'array',
        'classe_ids' => 'array',
        'publie_le' => 'datetime',
        'expire_le' => 'datetime',
        'notifiee_at' => 'datetime',
        'epinglee' => 'boolean',
    ];

    public function auteur() { return $this->belongsTo(User::class, 'auteur_id'); }
    public function filiere() { return $this->belongsTo(Filiere::class); }
    public function lectures() { return $this->hasMany(AnnonceLecture::class); }

    /** Annonces en cours : publiées et non expirées */
    public function scopeEnCours(Builder $query): Builder
    {
        return $query->where('publie_le', '<=', now())
            ->where(fn ($q) => $q->whereNull('expire_le')->orWhere('expire_le', '>', now()));
    }

    public function getCouleurAttribute(): string { return self::COULEURS[$this->importance] ?? 'secondary'; }
    public function getIconeAttribute(): string { return self::ICONES[$this->importance] ?? 'fa-bullhorn'; }
    public function getImportanceLibelleAttribute(): string { return self::IMPORTANCES[$this->importance] ?? $this->importance; }

    public function estCiblee(): bool
    {
        return !empty($this->classe_ids) || $this->filiere_id !== null;
    }

    public function estEnCours(): bool
    {
        return $this->publie_le->lte(now()) && ($this->expire_le === null || $this->expire_le->gt(now()));
    }

    /** Libellé lisible des destinataires, ex. « Stagiaires, Parents — classes GE-1A, GE-1B » */
    public function getDestinatairesLibelleAttribute(): string
    {
        $roles = collect($this->roles)->map(fn ($r) => self::ROLES[$r] ?? $r)->implode(', ');

        if (!empty($this->classe_ids)) {
            return $roles . ' — classes ' . Classe::whereIn('id', $this->classe_ids)->orderBy('nom')->pluck('nom')->implode(', ');
        }
        if ($this->filiere_id) {
            return $roles . ' — filière ' . ($this->filiere->nom ?? '?');
        }

        return $roles . ' — tout l\'établissement';
    }

    /** Comptes actifs qui reçoivent l'annonce */
    public function destinataires(): Builder
    {
        $roles = $this->roles ?? [];
        $classes = $this->classe_ids ?? [];
        $filiere = $this->filiere_id;

        // Filières concernées, pour les professeurs
        $filieres = !empty($classes)
            ? Classe::whereIn('id', $classes)->pluck('filiere_id')->unique()->all()
            : ($filiere ? [$filiere] : []);

        $limiteStagiaires = function ($q) use ($classes, $filiere) {
            if (!empty($classes)) {
                $q->whereIn('classe_id', $classes);
            } elseif ($filiere) {
                $q->where('filiere_id', $filiere);
            }
        };

        return User::query()
            ->where('is_active', true)
            ->whereIn('role', $roles)
            ->where(function ($q) use ($limiteStagiaires, $filieres) {
                if (!$this->estCiblee()) {
                    return; // tout l'établissement
                }
                $q->whereIn('role', ['administrateur', 'comptable'])
                    ->orWhere(fn ($s) => $s->where('role', 'stagiaire')->whereHas('stagiaire', $limiteStagiaires))
                    ->orWhere(fn ($p) => $p->where('role', 'parent')->whereHas('enfants', $limiteStagiaires))
                    ->orWhere(fn ($p) => $p->where('role', 'professeur')->whereHas('filieres', fn ($f) => $f->whereIn('filieres.id', $filieres)->where('professeur_filiere.is_active', true)));
            });
    }

    public function estDestinataire(User $user): bool
    {
        return $this->destinataires()->whereKey($user->id)->exists();
    }

    /** Administration et comptabilité voient toutes les annonces ; les autres, celles en cours qui les concernent */
    public function peutEtreVuePar(User $user): bool
    {
        if ($user->hasFinancialAccess()) {
            return true;
        }

        return $this->estEnCours()
            && in_array($user->role, $this->roles ?? [], true)
            && (!$this->estCiblee() || $this->estDestinataire($user));
    }

    public function estLuePar(User $user): bool
    {
        return $this->lectures()->where('user_id', $user->id)->exists();
    }

    public function marquerLuePar(User $user): void
    {
        AnnonceLecture::firstOrCreate(['annonce_id' => $this->id, 'user_id' => $user->id], ['lu_at' => now()]);
    }

    /** L'auteur, ou l'administration, peut modifier l'annonce */
    public function modifiablePar(User $user): bool
    {
        return $user->isAdmin() || (int) $this->auteur_id === (int) $user->id;
    }

    /**
     * Annonces en cours qui concernent l'utilisateur, épinglées puis plus récentes d'abord.
     * L'administration et la comptabilité voient aussi toutes celles qu'elles gèrent.
     */
    public static function pour(User $user, int $limite = 100)
    {
        return static::enCours()
            ->whereJsonContains('roles', $user->role)
            ->orderByDesc('epinglee')->orderByDesc('publie_le')
            ->limit($limite)
            ->get()
            ->filter(fn (Annonce $a) => !$a->estCiblee() || in_array($user->role, ['administrateur', 'comptable']) || $a->estDestinataire($user))
            ->values();
    }

    /** Annonces importantes ou urgentes non lues : affichées en bandeau sur toutes les pages */
    public static function aSignaler(User $user)
    {
        $lues = AnnonceLecture::where('user_id', $user->id)->pluck('annonce_id')->all();

        return static::pour($user, 30)
            ->filter(fn (Annonce $a) => in_array($a->importance, ['important', 'urgent']) && !in_array($a->id, $lues))
            ->sortBy(fn (Annonce $a) => $a->importance === 'urgent' ? 0 : 1)
            ->values();
    }

    public static function nombreNonLues(User $user): int
    {
        $lues = AnnonceLecture::where('user_id', $user->id)->pluck('annonce_id')->all();

        return static::pour($user)->reject(fn (Annonce $a) => in_array($a->id, $lues))->count();
    }
}
