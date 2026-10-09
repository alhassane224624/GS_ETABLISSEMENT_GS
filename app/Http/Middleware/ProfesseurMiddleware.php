<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ProfesseurMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier l'authentification
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Vous devez être connecté.');
        }

        $user = Auth::user();

        // Vérifier le rôle professeur
        if (!$user->isProfesseur()) {
            Log::warning('Accès professeur refusé', [
                'user_id' => $user->id,
                'role' => $user->role,
                'url' => $request->url()
            ]);

            // ✅ Rediriger vers le bon dashboard selon le rôle
            if (in_array($user->role, ['administrateur', 'comptable', 'professeur', 'stagiaire', 'parent'])) {
                return redirect()->route('dashboard')
                    ->with('error', 'Accès non autorisé pour votre rôle.');
            }

            // Si rôle inconnu, déconnecter
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Accès réservé aux professeurs.');
        }

        // Vérifier si le compte est actif
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Votre compte a été désactivé. Contactez l\'administration.');
        }

        return $next($request);
    }
}