<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Models\Rendu;
use App\Models\Stagiaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Espace stagiaire : publications de sa classe et dépôt des devoirs
 */
class StagiairePublicationController extends Controller
{
    public function __construct()
    {
        $this->middleware('stagiaire');
    }

    public function index(Request $request)
    {
        $stagiaire = $this->stagiaire();

        $publications = Publication::with(['matiere:id,nom', 'professeur:id,name'])
            ->where('classe_id', $stagiaire->classe_id)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $mesRendus = Rendu::where('stagiaire_id', $stagiaire->id)
            ->whereIn('publication_id', $publications->pluck('id'))
            ->get()
            ->keyBy('publication_id');

        // Devoirs à rendre en ligne, pas encore déposés
        $aRendre = Publication::with('matiere:id,nom')
            ->where('classe_id', $stagiaire->classe_id)
            ->where('type', 'devoir')
            ->where('accepte_rendu', true)
            ->whereDoesntHave('rendus', fn ($q) => $q->where('stagiaire_id', $stagiaire->id))
            ->orderBy('date_limite')
            ->get();

        return view('stagiaires.publications.index', compact('publications', 'mesRendus', 'aRendre', 'stagiaire'));
    }

    public function show(Publication $publication)
    {
        $stagiaire = $this->stagiaire();
        $this->verifierClasse($publication, $stagiaire);

        $publication->load(['matiere', 'professeur:id,name']);
        $rendu = $publication->rendus()->where('stagiaire_id', $stagiaire->id)->first();

        return view('stagiaires.publications.show', compact('publication', 'rendu'));
    }

    public function deposer(Request $request, Publication $publication)
    {
        $stagiaire = $this->stagiaire();
        $this->verifierClasse($publication, $stagiaire);
        abort_unless($publication->estDevoir() && $publication->accepte_rendu, 403, 'Ce devoir ne se rend pas en ligne.');

        $data = $request->validate([
            'fichier'     => 'required|file|mimes:' . Publication::MIMES . '|max:' . Publication::TAILLE_MAX,
            'commentaire' => 'nullable|string|max:1000',
        ]);

        $ancien = $publication->rendus()->where('stagiaire_id', $stagiaire->id)->first();
        $chemin = $request->file('fichier')->store('rendus/' . $publication->id, 'local');

        Rendu::updateOrCreate(
            ['publication_id' => $publication->id, 'stagiaire_id' => $stagiaire->id],
            [
                'fichier_path' => $chemin,
                'fichier_nom'  => $request->file('fichier')->getClientOriginalName(),
                'commentaire'  => $data['commentaire'] ?? null,
                'rendu_at'     => now(),
            ]
        );

        if ($ancien) {
            Storage::disk('local')->delete($ancien->fichier_path);
        }

        $message = $ancien ? 'Votre copie a été remplacée.' : 'Votre copie a bien été déposée.';
        if ($publication->echeanceDepassee()) {
            $message .= ' Attention : la date limite est dépassée, le dépôt est marqué « en retard ».';
        }

        return back()->with('success', $message);
    }

    // -------------------------------------------------------------------------

    private function stagiaire(): Stagiaire
    {
        return Stagiaire::where('user_id', Auth::id())->firstOrFail();
    }

    private function verifierClasse(Publication $publication, Stagiaire $stagiaire): void
    {
        abort_unless((int) $publication->classe_id === (int) $stagiaire->classe_id, 403, 'Cette publication ne concerne pas votre classe.');
    }
}
