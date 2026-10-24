<?php

namespace App\Support;

use App\Models\User;

/** Layout de l'espace de l'utilisateur, pour les pages communes à tous les rôles */
class Layout
{
    public static function pour(?User $user): string
    {
        return match ($user?->role) {
            'professeur' => 'layouts.app-professeur',
            'stagiaire' => 'layouts.app-stagiaire',
            'comptable' => 'layouts.comptable',
            'parent' => 'layouts.app-parent',
            default => 'layouts.app',
        };
    }
}
