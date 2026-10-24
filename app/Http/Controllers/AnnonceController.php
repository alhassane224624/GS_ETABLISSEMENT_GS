<?php

namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Models\AnnonceLecture;
use App\Support\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Consultation des annonces par tous les utilisateurs, dans leur propre espace
 */
class AnnonceController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return view('annonces.index', [
            'annonces' => Annonce::pour($user),
            'lues' => AnnonceLecture::where('user_id', $user->id)->pluck('annonce_id')->flip(),
            'layout' => Layout::pour($user),
        ]);
    }

    public function show(Annonce $annonce)
    {
        $user = Auth::user();
        abort_unless($annonce->peutEtreVuePar($user), 404);

        // Lecture enregistrée seulement pour les destinataires
        if (in_array($user->role, $annonce->roles ?? [], true) && $annonce->estEnCours()) {
            $annonce->marquerLuePar($user);
        }

        return view('annonces.show', ['annonce' => $annonce->load('auteur:id,name,role'), 'layout' => Layout::pour($user)]);
    }

    public function fichier(Annonce $annonce)
    {
        abort_unless($annonce->peutEtreVuePar(Auth::user()), 404);
        abort_unless($annonce->fichier_path && Storage::disk('local')->exists($annonce->fichier_path), 404, 'Fichier introuvable.');

        return Storage::disk('local')->download($annonce->fichier_path, $annonce->fichier_nom);
    }
}
