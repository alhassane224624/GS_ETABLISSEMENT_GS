<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }
        if ($user->role !== 'parent') {
            return redirect()->route('dashboard')->with('error', 'Accès réservé aux parents.');
        }
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Votre compte a été désactivé.');
        }
        return $next($request);
    }
}
