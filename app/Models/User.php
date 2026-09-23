<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'created_by',
        'is_active',
        'activated_at',
        'activated_by',
        'specialite',
        'bio',
        'telephone',
        // 'email_verified_at' retiré du fillable (géré automatiquement)
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'activated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // =========================================================================
    // RELATIONS
    // =========================================================================

    /**
     * ✅ Relation avec le profil stagiaire (CRITIQUE)
     */
    public function stagiaire()
    {
        return $this->hasOne(Stagiaire::class, 'user_id');
    }

    /**
     * ✅ Vérifie si l'utilisateur a un profil stagiaire
     */
    public function hasStagiaireProfile()
    {
        return $this->stagiaire()->exists();
    }

    /**
     * Utilisateur qui a créé ce compte
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Utilisateur qui a activé ce compte
     */
    public function activatedBy()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    /**
     * Utilisateurs créés par cet utilisateur
     */
    public function usersCreated()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    /**
     * Filières assignées au professeur
     */
    public function filieres()
    {
        return $this->belongsToMany(Filiere::class, 'professeur_filiere', 'professeur_id', 'filiere_id')
            ->withPivot('created_by', 'is_active', 'date_assignation', 'remarques')
            ->withTimestamps();
    }

    /**
     * Matières enseignées par le professeur
     */
    public function matieresEnseignees()
    {
        return $this->belongsToMany(Matiere::class, 'professeur_matiere', 'professeur_id', 'matiere_id')
            ->withPivot('filiere_id', 'assigned_by', 'is_active', 'date_assignation', 'competences')
            ->withTimestamps();
    }

    /**
     * Stagiaires créés par cet utilisateur
     */
    public function stagiairesCreated()
    {
        return $this->hasMany(Stagiaire::class, 'created_by');
    }

    /**
     * Notes créées par cet utilisateur
     */
    public function notesCreated()
    {
        return $this->hasMany(Note::class, 'created_by');
    }

    /**
     * Plannings créés par cet utilisateur
     */
    public function planningsCreated()
    {
        return $this->hasMany(Planning::class, 'created_by');
    }

    /**
     * Plannings validés par cet utilisateur
     */
    public function planningsValidated()
    {
        return $this->hasMany(Planning::class, 'validated_by');
    }

    /**
     * Planning du professeur
     */
    public function planningsAsProfesseur()
    {
        return $this->hasMany(Planning::class, 'professeur_id');
    }

    /**
     * Absences créées par cet utilisateur
     */
    public function absencesCreated()
    {
        return $this->hasMany(Absence::class, 'created_by');
    }

    /**
     * Bulletins créés par cet utilisateur
     */
    public function bulletinsCreated()
    {
        return $this->hasMany(Bulletin::class, 'created_by');
    }

    /**
     * Bulletins validés par cet utilisateur
     */
    public function bulletinsValidated()
    {
        return $this->hasMany(Bulletin::class, 'validated_by');
    }

    /**
     * Actions administratives
     */
    public function adminActionsLog()
    {
        return $this->hasMany(AdminActionLog::class, 'admin_id');
    }

    /**
     * Messages envoyés
     */
    public function messagesSent()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Messages reçus
     */
    public function messagesReceived()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    // =========================================================================
    // MÉTHODES DE VÉRIFICATION DE RÔLE
    // =========================================================================

    /**
     * Vérifie si l'utilisateur est administrateur
     */
    public function isAdmin(): bool
    {
        return $this->role === 'administrateur';
    }

    /**
     * ✅ NOUVEAU - Vérifie si l'utilisateur est comptable
     */
    public function isComptable(): bool
    {
        return $this->role === 'comptable';
    }

    /**
     * Vérifie si l'utilisateur est stagiaire
     */
    public function isStagiaire(): bool
    {
        return $this->role === 'stagiaire';
    }

    /**
     * Vérifie si l'utilisateur est professeur
     */
    public function isProfesseur(): bool
    {
        return $this->role === 'professeur';
    }

    // =========================================================================
    // MÉTHODES DE PERMISSIONS
    // =========================================================================

    /**
     * ✅ NOUVEAU - Vérifie si l'utilisateur a des droits financiers (admin ou comptable)
     */
       public function hasFinancialAccess(): bool
{
    return in_array($this->role, ['administrateur', 'comptable']);
}


    /**
     * ✅ NOUVEAU - Vérifie si l'utilisateur peut valider des paiements
     */
    public function canValidatePayments(): bool
    {
        return in_array($this->role, ['administrateur', 'comptable']);
    }

    /**
     * Vérifie si le professeur peut enseigner une matière
     */
    public function canTeachMatiere($matiereId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (!$this->isProfesseur()) {
            return false;
        }

        return $this->matieresEnseignees()
            ->where('matiere_id', $matiereId)
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Vérifie si l'utilisateur a accès à une filière
     * ✅ MODIFIÉ - Ajout accès comptable
     */
    public function hasAccessToFiliere($filiereId): bool
    {
        // Admin et comptable ont accès à toutes les filières
        if ($this->isAdmin() || $this->isComptable()) {
            return true;
        }

        // Professeur : vérifier les filières assignées
        if ($this->isProfesseur()) {
            return $this->filieres()
                ->where('filiere_id', $filiereId)
                ->wherePivot('is_active', true)
                ->exists();
        }

        return false;
    }

    // =========================================================================
    // ACCESSEURS (ATTRIBUTES)
    // =========================================================================

    /**
     * Obtient les filières actives du professeur
     */
    public function getActiveFilieresAttribute()
    {
        return $this->filieres()->wherePivot('is_active', true)->get();
    }

    /**
     * Obtient les matières actives du professeur
     */
    public function getActiveMatieresAttribute()
    {
        return $this->matieresEnseignees()->wherePivot('is_active', true)->get();
    }

    /**
     * ✅ NOUVEAU - Obtient le libellé du rôle
     */
    public function getRoleLibelleAttribute(): string
    {
        return match($this->role) {
            'administrateur' => 'Administrateur',
            'comptable' => 'Comptable',
            'professeur' => 'Professeur',
            'stagiaire' => 'Stagiaire',
            default => 'Utilisateur',
        };
    }

    /**
     * ✅ NOUVEAU - Obtient les initiales
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($this->name, 0, 2));
    }

    // =========================================================================
    // MÉTHODES MESSAGERIE
    // =========================================================================

    /**
     * Compte les messages non lus
     */
    public function getUnreadMessagesCount(): int
    {
        return $this->messagesReceived()->where('is_read', false)->count();
    }

    /**
     * Obtient les conversations de l'utilisateur
     */
    public function getConversations()
    {
        return Message::getConversationsFor($this->id);
    }

    /**
     * Obtient une conversation avec un utilisateur spécifique
     */
    public function getConversationWith($userId)
    {
        return Message::between($this->id, $userId)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Envoie un message à un utilisateur
     */
    public function sendMessageTo($receiverId, $message, $subject = null)
    {
        return Message::create([
            'sender_id' => $this->id,
            'receiver_id' => $receiverId,
            'message' => $message,
            'subject' => $subject,
        ]);
    }

    // =========================================================================
    // MÉTHODES PROFESSEUR
    // =========================================================================

    /**
     * Obtient les stagiaires accessibles par le professeur
     */
    public function getStagiaires()
    {
        if (!$this->isProfesseur()) {
            return collect();
        }

        $filiereIds = $this->filieres()->wherePivot('is_active', true)->pluck('filieres.id');
        
        return Stagiaire::whereIn('filiere_id', $filiereIds)
            ->where('is_active', true)
            ->with(['filiere', 'classe', 'niveau'])
            ->get();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope pour les utilisateurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les utilisateurs par rôle
     */
    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope pour les administrateurs
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'administrateur');
    }

    /**
     * ✅ NOUVEAU - Scope pour les comptables
     */
    public function scopeComptables($query)
    {
        return $query->where('role', 'comptable');
    }

    /**
     * Scope pour les professeurs
     */
    public function scopeProfesseurs($query)
    {
        return $query->where('role', 'professeur');
    }

    /**
     * Scope pour les stagiaires
     */
    public function scopeStagiaires($query)
    {
        return $query->where('role', 'stagiaire');
    }

    /**
     * ✅ NOUVEAU - Scope pour les utilisateurs avec accès financier
     */
    public function scopeFinancialAccess($query)
    {
        return $query->whereIn('role', ['administrateur', 'comptable']);
    }
}