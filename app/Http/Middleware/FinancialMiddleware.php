<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FinancialMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Autoriser admin ET comptable
        if (!in_array($user->role, ['administrateur', 'comptable'])) {
            abort(403, 'Accès réservé aux administrateurs et comptables');
        }

        return $next($request);
    }
}