<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Note;
use App\Models\Publication;
use App\Models\Stagiaire;
use App\Notifications\PublicationCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Espace professeur : publications (devoirs, documents, résultats) et suivi des rendus
 */
class ProfesseurPublicationController extends Controller
{
    public function __construct()
    {
        $this->middleware('professeur');
    }

    public function index(Request $request)
    {
        $publications = Publication::with(['classe:id,nom', 'matiere:id,nom'])
            ->withCount('rendus')
            ->where('professeur_id', Auth::id())
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Effectif de chaque classe, pour afficher « x / y rendus »
        $effectifs = Stagiaire::whereIn('classe_id', $publications->pluck('classe_id')->unique())
            ->where('is_active', true)
            ->selectRaw('classe_id, COUNT(*) as total')
            ->groupBy('classe_id')
            ->pluck('total', 'classe_id');

        return view('professeur.publications.index', [
            'publications' => $publications,
            'effectifs' => $effectifs,
            'enAttente' => $this->notesEnAttente(),
        ]);
    }

    public function create(Request $request)
    {
        return view('professeur.publications.create', [
            'classes' => $this->classesAutorisees(),
            'enAttente' => $this->notesEnAttente(),
            'type' => in_array($request->type, array_keys(Publication::TYPES)) ? $request->type : 'devoir',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'          => ['required', Rule::in(array_keys(Publication::TYPES))],
            'classe_id'     => 'required|exists:classes,id',
            'matiere_id'    => 'required|exists:matieres,id',
            'titre'         => 'required|string|max:255',
            'contenu'       => 'nullable|string|max:5000',
            'fichier'       => 'nullable|file|mimes:' . Publication::MIMES . '|max:' . Publication::TAILLE_MAX,
            'date_limite'   => 'required_if:type,devoir|nullable|date|after:now',
            'accepte_rendu' => 'boolean',
        ], [
            'date_limite.required_if' => 'Indiquez la date limite du devoir.',
            'date_limite.after' => 'La date limite doit être dans le futur.',
        ]);

        $classe = Classe::findOrFail($data['classe_id']);
        $this->autoriser($classe, (int) $data['matiere_id']);

        if ($data['type'] === 'document' && !$request->hasFile('fichier') && empty($data['contenu'])) {
            throw ValidationException::withMessages(['fichier' => 'Joignez un fichier ou rédigez un contenu pour ce document.']);
        }

        // Résultats : on publie les notes encore masquées de ce professeur pour cette classe et cette matière
        $notesAPublier = Note::where('created_by', Auth::id())
            ->where('classe_id', $classe->id)
            ->where('matiere_id', $data['matiere_id'])
            ->whereNull('publie_at');

        if ($data['type'] === 'resultats' && !(clone $notesAPublier)->exists()) {
            throw ValidationException::withMessages(['matiere_id' => 'Aucune note non publiée pour cette classe et cette matière : saisissez d\'abord les notes.']);
        }

        $fichierPath = $request->hasFile('fichier')
            ? $request->file('fichier')->store('publications/' . $classe->id, 'local')
            : null;

        $idsNotes = $data['type'] === 'resultats' ? (clone $notesAPublier)->pluck('id') : collect();

        $publication = DB::transaction(function () use ($data, $request, $fichierPath, $notesAPublier) {
            $publication = Publication::create([
                'professeur_id' => Auth::id(),
                'classe_id'     => $data['classe_id'],
                'matiere_id'    => $data['matiere_id'],
                'type'          => $data['type'],
                'titre'         => $data['titre'],
                'contenu'       => $data['contenu'] ?? null,
                'fichier_path'  => $fichierPath,
                'fichier_nom'   => $fichierPath ? $request->file('fichier')->getClientOriginalName() : null,
                'date_limite'   => $data['type'] === 'devoir' ? $data['date_limite'] : null,
                'accepte_rendu' => $data['type'] === 'devoir' && $request->boolean('accepte_rendu'),
            ]);

            if ($data['type'] === 'resultats') {
                $publication->update(['notes_publiees' => $notesAPublier->update(['publie_at' => now()])]);
            }

            return $publication;
        });

        $this->notifierClasse($publication);
        if ($idsNotes->isNotEmpty()) {
            app(\App\Services\NotificationsParents::class)->resultats(Note::whereIn('id', $idsNotes)->get());
        }

        $message = match ($publication->type) {
            'resultats' => "Résultats publiés : {$publication->notes_publiees} note(s) sont maintenant visibles par les stagiaires.",
            'devoir' => 'Devoir publié. Les stagiaires de la classe ont été notifiés.',
            default => 'Document publié. Les stagiaires de la classe ont été notifiés.',
        };

        return redirect()->route('professeur.publications.show', $publication)->with('success', $message);
    }

    public function show(Publication $publication)
    {
        $this->verifierProprietaire($publication);
        $publication->load(['classe', 'matiere']);

        $stagiaires = collect();
        $rendus = collect();
        if ($publication->estDevoir()) {
            $stagiaires = Stagiaire::where('classe_id', $publication->classe_id)
                ->where('is_active', true)
                ->orderBy('nom')->orderBy('prenom')
                ->get();
            $rendus = $publication->rendus()->get()->keyBy('stagiaire_id');
        }

        return view('professeur.publications.show', compact('publication', 'stagiaires', 'rendus'));
    }

    public function destroy(Publication $publication)
    {
        $this->verifierProprietaire($publication);

        $fichiers = $publication->rendus()->pluck('fichier_path')->push($publication->fichier_path)->filter()->all();
        $publication->delete(); // les rendus partent avec (cascade)
        Storage::disk('local')->delete($fichiers);

        $message = $publication->type === 'resultats'
            ? 'Annonce supprimée. Les notes déjà publiées restent visibles.'
            : 'Publication supprimée.';

        return redirect()->route('professeur.publications.index')->with('success', $message);
    }

    // -------------------------------------------------------------------------

    /** Classes de l'année active dont le professeur enseigne la filière, avec les matières qu'il peut y publier */
    private function classesAutorisees()
    {
        $user = Auth::user();
        $matieresParFiliere = $user->matieresEnseignees()
            ->wherePivot('is_active', true)
            ->orderBy('nom')
            ->get()
            ->groupBy('pivot.filiere_id');

        return Classe::with('filiere:id,nom')
            ->whereIn('filiere_id', $user->filieresActives()->pluck('filieres.id'))
            ->whereHas('anneeScolaire', fn ($q) => $q->where('is_active', true))
            ->orderBy('nom')
            ->get()
            ->each(fn ($c) => $c->setRelation('matieresProf', $matieresParFiliere->get($c->filiere_id, collect())->unique('id')->values()))
            ->filter(fn ($c) => $c->matieresProf->isNotEmpty())
            ->values();
    }

    private function autoriser(Classe $classe, int $matiereId): void
    {
        $user = Auth::user();
        $ok = $user->filieresActives()->where('filieres.id', $classe->filiere_id)->exists()
            && $user->matieresEnseignees()
                ->wherePivot('is_active', true)
                ->wherePivot('filiere_id', $classe->filiere_id)
                ->where('matieres.id', $matiereId)
                ->exists();

        if (!$ok) {
            throw ValidationException::withMessages(['matiere_id' => 'Vous n\'enseignez pas cette matière dans cette classe.']);
        }
    }

    private function verifierProprietaire(Publication $publication): void
    {
        abort_unless((int) $publication->professeur_id === (int) Auth::id(), 403, 'Cette publication ne vous appartient pas.');
    }

    /** Notes saisies par le professeur et pas encore publiées, regroupées par classe et matière */
    private function notesEnAttente()
    {
        return Note::with(['classe:id,nom', 'matiere:id,nom'])
            ->where('created_by', Auth::id())
            ->whereNull('publie_at')
            ->whereNotNull('classe_id')
            ->selectRaw('classe_id, matiere_id, COUNT(*) as total, MAX(created_at) as derniere')
            ->groupBy('classe_id', 'matiere_id')
            ->get();
    }

    private function notifierClasse(Publication $publication): void
    {
        try {
            $users = Stagiaire::with('user')
                ->where('classe_id', $publication->classe_id)
                ->where('is_active', true)
                ->get()
                ->pluck('user')
                ->filter();
            Notification::send($users, new PublicationCreated($publication->loadMissing('matiere')));
        } catch (\Throwable $e) {
            Log::warning('Notification de publication non envoyée', ['publication_id' => $publication->id, 'erreur' => $e->getMessage()]);
        }
    }
}
