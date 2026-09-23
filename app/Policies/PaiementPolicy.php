<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Paiement;
use App\Models\Stagiaire;

class PaiementPolicy
{
    /**
     * Voir la liste des paiements
     */
    public function viewAny(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Voir un paiement spécifique
     */
    public function view(User $user, Paiement $paiement)
    {
        // Admin peut tout voir
        if ($user->isAdmin()) {
            return true;
        }

        // Stagiaire peut voir ses propres paiements
        if ($user->isStagiaire()) {
            $stagiaire = Stagiaire::where('user_id', $user->id)->first();
            return $stagiaire && $paiement->stagiaire_id === $stagiaire->id;
        }

        return false;
    }

    /**
     * Créer un paiement
     */
    public function create(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Modifier, valider ou refuser un paiement
     */
    public function update(User $user, Paiement $paiement)
    {
        // Autoriser les administrateurs à valider/refuser
        return $user->isAdmin();
    }

    /**
     * Valider/Refuser un paiement (ancienne méthode)
     */
    public function validate(User $user, Paiement $paiement)
    {
        return $user->isAdmin();
    }

    /**
     * Voir l'historique des paiements d'un stagiaire
     */
    public function viewHistorique(User $user, Stagiaire $stagiaire)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isStagiaire()) {
            $userStagiaire = Stagiaire::where('user_id', $user->id)->first();
            return $userStagiaire && $userStagiaire->id === $stagiaire->id;
        }

        return false;
    }
}
