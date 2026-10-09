<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StagiaireMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // ✅ Vérifier si l'utilisateur est connecté
        if (!Auth::check()) {
            Log::warning('Tentative d\'accès stagiaire sans authentification');
            return redirect()->route('login')
                ->with('error', 'Vous devez être connecté pour accéder à cette page.');
        }

        $user = Auth::user();

        // ✅ Vérifier si l'utilisateur a le rôle stagiaire
        if ($user->role !== 'stagiaire') {
            Log::warning('Accès stagiaire refusé', [
                'user_id' => $user->id,
                'role' => $user->role,
                'url' => $request->url()
            ]);

            // ✅ Rediriger vers le bon dashboard selon le rôle
            if (in_array($user->role, ['administrateur', 'comptable', 'professeur', 'stagiaire', 'parent'])) {
                return redirect()->route('dashboard')
                    ->with('error', 'Accès non autorisé pour votre rôle.');
            }

            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Rôle utilisateur invalide.');
        }

        // ✅ Vérifier si le compte est actif
        if (!$user->is_active) {
            Log::warning('Compte stagiaire inactif', ['user_id' => $user->id]);
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Votre compte est désactivé. Contactez l\'administration.');
        }

        // ✅ Vérifier si le profil stagiaire existe
        $stagiaire = \App\Models\Stagiaire::where('user_id', $user->id)->first();
        
        if (!$stagiaire) {
            Log::error('Stagiaire introuvable dans la table stagiaires', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Profil stagiaire introuvable. Contactez l\'administration.');
        }

        // ✅ Tout est OK, continuer
        return $next($request);
    }
}