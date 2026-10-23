<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Models\Rendu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Téléchargement des fichiers privés (pièce jointe d'une publication, copie rendue),
 * après vérification des droits : ces fichiers ne sont pas accessibles par une URL publique.
 */
class PublicationFichierController extends Controller
{
    public function publication(Publication $publication)
    {
        $user = Auth::user();
        $autorise = $user->isAdmin()
            || (int) $publication->professeur_id === (int) $user->id
            || ($user->role === 'stagiaire' && (int) optional($user->stagiaire)->classe_id === (int) $publication->classe_id);

        abort_unless($autorise, 403);
        abort_unless($publication->fichier_path && Storage::disk('local')->exists($publication->fichier_path), 404, 'Fichier introuvable.');

        return Storage::disk('local')->download($publication->fichier_path, $publication->fichier_nom);
    }

    public function rendu(Rendu $rendu)
    {
        $user = Auth::user();
        $rendu->loadMissing('publication', 'stagiaire');
        $autorise = $user->isAdmin()
            || (int) $rendu->publication->professeur_id === (int) $user->id
            || (int) $rendu->stagiaire->user_id === (int) $user->id;

        abort_unless($autorise, 403);
        abort_unless(Storage::disk('local')->exists($rendu->fichier_path), 404, 'Fichier introuvable.');

        // Nom lisible pour le professeur : NOM_Prenom_fichier-original
        $nom = str_replace(' ', '_', $rendu->stagiaire->nom . '_' . $rendu->stagiaire->prenom) . '_' . $rendu->fichier_nom;

        return Storage::disk('local')->download($rendu->fichier_path, $nom);
    }
}
