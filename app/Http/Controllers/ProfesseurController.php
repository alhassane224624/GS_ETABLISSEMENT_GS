<?php

namespace App\Http\Controllers;

use App\Models\Stagiaire;
use App\Models\Filiere;
use App\Models\Note;
use App\Models\Matiere;
use App\Models\User;
use App\Models\Absence;
use App\Models\Planning;
use App\Models\Classe;
use App\Models\Salle;
use App\Models\Periode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\NotesExport;
use App\Exports\StagiairesExport;

class ProfesseurController extends Controller
{
    public function __construct()
    {
        // Accès professeur par défaut
        $this->middleware('professeur')->except(['editFilieres', 'updateFilieres', 'editMatieres', 'updateMatieres']);
        // Accès admin pour ces actions
        $this->middleware('admin')->only(['editFilieres', 'updateFilieres', 'editMatieres', 'updateMatieres']);
    }

    /**
     * Dashboard du professeur
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Filières du prof + nb stagiaires actifs
        $filieres = $user->filieres()
            ->withCount(['stagiaires' => function ($query) {
                $query->where('statut', 'actif')->where('is_active', true);
            }])
            ->get();

        $filiereIds = $filieres->pluck('id')->toArray();

        // ✅ CORRECTION: Ajout de la variable $matieres
        $matieres = $user->matieresEnseignees()
            ->wherePivot('is_active', true)
            ->get();

        // Stats
        $totalStagiaires = Stagiaire::whereIn('filiere_id', $filiereIds)
            ->where('statut', 'actif')
            ->where('is_active', true)
            ->count();

        $totalNotes = Note::where('created_by', $user->id)->count();

        $totalAbsences = Absence::where('created_by', $user->id)
            ->whereDate('date', '>=', now()->subDays(30))
            ->count();

        // Planning du jour
        $planningAujourdhui = Planning::where('professeur_id', $user->id)
            ->whereDate('date', now())
            ->with(['salle', 'matiere', 'classe.niveau', 'classe.filiere'])
            ->orderBy('heure_debut')
            ->get();

        // Dernières notes
        $recentNotes = Note::with(['stagiaire:id,nom,prenom', 'matiere:id,nom'])
            ->where('created_by', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        $data = [
            'filieres'             => $filieres,
            'matieres'             => $matieres, // ✅ AJOUTÉ
            'total_stagiaires'     => $totalStagiaires,
            'total_notes'          => $totalNotes,
            'total_absences'       => $totalAbsences,
            'planning_aujourd_hui' => $planningAujourdhui,
            'recent_notes'         => $recentNotes,
        ];

        return view('professeur.dashboard', compact('data'));
    }

    /**
     * Liste des stagiaires du professeur
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $search   = $request->input('search');
        $filiereId = $request->input('filiere_id');

        // Filtrer par filières du professeur
        $filiereIds = $user->filieres->pluck('id')->toArray();

        if (empty($filiereIds)) {
            // Paginator vide correct (éviter collect()->paginate())
            $empty = new LengthAwarePaginator([], 0, 15);
            $filieres = collect();

            return view('professeur.stagiaires', [
                'stagiaires' => $empty,
                'filieres'   => $filieres
            ])->with('error', 'Aucune filière ne vous est assignée. Contactez l\'administrateur.');
        }

        $query = Stagiaire::with(['filiere:id,nom', 'classe:id,nom', 'niveau:id,nom'])
            ->whereIn('filiere_id', $filiereIds)
            ->where('statut', 'actif')
            ->where('is_active', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        if ($filiereId) {
            $query->where('filiere_id', $filiereId);
        }

        $stagiaires = $query->paginate(15);
        $filieres = $user->filieres;

        return view('professeur.stagiaires', compact('stagiaires', 'filieres'));
    }

    /**
     * Afficher les notes d'un stagiaire
     */
    public function showNotes(Stagiaire $stagiaire)
    {
        $user = Auth::user();
        $filiereIds = $user->filieres()->pluck('filieres.id')->toArray();

        // Accès
        if (!in_array($stagiaire->filiere_id, $filiereIds)) {
            return redirect()->route('professeur.stagiaires')
                ->with('error', 'Accès non autorisé à ce stagiaire.');
        }

        // Notes de CE professeur pour CE stagiaire
        $notes = $stagiaire->notes()
            ->with(['creator:id,name', 'matiere:id,nom', 'periode:id,nom'])
            ->where('created_by', $user->id)
            ->latest()
            ->get();

        // Matières enseignées par le prof (actives)
        $matieres = $user->matieresEnseignees()
            ->wherePivot('is_active', true)
            ->get();

        return view('professeur.notes', compact('stagiaire', 'notes', 'matieres'));
    }

    /**
     * Ajouter une note à un stagiaire
     */
    public function storeNote(Request $request, Stagiaire $stagiaire)
    {
        $user = Auth::user();
        $filiereIds = $user->filieres->pluck('id')->toArray();

        // Accès
        if (!in_array($stagiaire->filiere_id, $filiereIds)) {
            return redirect()->route('professeur.stagiaires')
                ->with('error', 'Accès non autorisé à ce stagiaire.');
        }

        $request->merge(['note_sur' => $request->input('note_sur') ?: 20]);
        $validated = $request->validate([
            'matiere_id'  => 'required|exists:matieres,id',
            'note'        => 'required|numeric|min:0|lte:note_sur',
            'type_note'   => 'required|in:ds,cc,examen,tp,projet',
            'note_sur'    => 'required|numeric|min:1|max:99',
            'commentaire' => 'nullable|string|max:1000',
        ]);

        // Vérifier habilitation matière
        if (!$user->canTeachMatiere($validated['matiere_id'])) {
            return back()->with('error', 'Vous n\'êtes pas autorisé à ajouter une note pour cette matière.');
        }

        // Période active
        $periodeActive = Periode::where('is_active', true)->first();
        if (!$periodeActive) {
            return back()->withInput()->with('error', 'Aucune période active : demandez à l\'administration d\'activer la période en cours.');
        }
        if (Note::periodeVerrouillee($stagiaire->id, $periodeActive->id)) {
            return back()->with('error', 'Le bulletin de cette période est validé : la note ne peut plus être modifiée. Dévalidez d\'abord le bulletin si une correction est nécessaire.');
        }

        Note::create([
            'stagiaire_id' => $stagiaire->id,
            'matiere_id'   => $validated['matiere_id'],
            'classe_id'    => $stagiaire->classe_id,
            'periode_id'   => $periodeActive?->id,
            'note'         => $validated['note'],
            'type_note'    => $validated['type_note'],
            'note_sur'     => $validated['note_sur'] ?? 20,
            'commentaire'  => $validated['commentaire'] ?? null,
            'created_by'   => $user->id,
        ]);

        return back()->with('success', 'Note ajoutée avec succès.');
    }

    /**
     * Modifier une note
     */
    public function updateNote(Request $request, Stagiaire $stagiaire, Note $note)
    {
        $user = Auth::user();

        // Sécurité
        if ($note->stagiaire_id !== $stagiaire->id) {
            return back()->with('error', 'Cette note n\'appartient pas à ce stagiaire.');
        }
        if ($note->created_by !== $user->id) {
            return back()->with('error', 'Vous n\'êtes pas autorisé à modifier cette note.');
        }

        $request->merge(['note_sur' => $request->input('note_sur') ?: 20]);
        $validated = $request->validate([
            'matiere_id'  => 'required|exists:matieres,id',
            'note'        => 'required|numeric|min:0|lte:note_sur',
            'type_note'   => 'required|in:ds,cc,examen,tp,projet',
            'note_sur'    => 'required|numeric|min:1|max:99',
            'commentaire' => 'nullable|string|max:1000',
        ]);

        if (!$user->canTeachMatiere($validated['matiere_id'])) {
            return back()->with('error', 'Vous n\'êtes pas autorisé à modifier une note pour cette matière.');
        }

        if ($note->estVerrouillee()) {
            return back()->with('error', 'Le bulletin de cette période est validé : la note ne peut plus être modifiée. Dévalidez d\'abord le bulletin si une correction est nécessaire.');
        }

        $note->update([
            'matiere_id'  => $validated['matiere_id'],
            'note'        => $validated['note'],
            'type_note'   => $validated['type_note'],
            'note_sur'    => $validated['note_sur'] ?? 20,
            'commentaire' => $validated['commentaire'] ?? null,
        ]);

        return back()->with('success', 'Note modifiée avec succès.');
    }

    /**
     * Afficher toutes les notes par matière
     */
    public function notesParMatiere(Request $request)
    {
        $user = Auth::user();
        $matiereId = $request->input('matiere_id');

        $filiereIds = $user->filieres->pluck('id')->toArray();

        $query = Note::with(['stagiaire.filiere:id,nom', 'stagiaire.classe:id,nom', 'matiere:id,nom', 'creator:id,name'])
            ->where('created_by', $user->id)
            ->whereHas('stagiaire', function ($q) use ($filiereIds) {
                $q->whereIn('filiere_id', $filiereIds);
            });

        // Restreindre aux matières enseignées
        $matieresIds = $user->matieresEnseignees()
            ->wherePivot('is_active', true)
            ->pluck('matieres.id');

        $query->whereIn('matiere_id', $matieresIds);

        if ($matiereId) {
            $query->where('matiere_id', $matiereId);
        }

        $notes = $query->latest()->paginate(20);

        $matieres = $user->matieresEnseignees()
            ->wherePivot('is_active', true)
            ->get();

        return view('professeur.notes-par-matiere', compact('notes', 'matieres', 'matiereId'));
    }

    /**
     * Export des notes en PDF
     */
    public function exportNotesPdf(Request $request)
    {
        $user = Auth::user();

        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', '120');

        $matiereId = $request->input('matiere_id');
        $limit     = (int) ($request->input('limit', 300));
        $limit     = max(1, min($limit, 800));

        $filiereIds = $user->filieres->pluck('id')->toArray();

        $query = Note::query()
            ->select(['id','stagiaire_id','matiere_id','note','note_sur','type_note','created_at','created_by'])
            ->where('created_by', $user->id)
            ->whereHas('stagiaire', function ($q) use ($filiereIds) {
                $q->whereIn('filiere_id', $filiereIds);
            })
            ->with([
                'stagiaire:id,nom,prenom,filiere_id,classe_id',
                'stagiaire.filiere:id,nom',
                'stagiaire.classe:id,nom',
                'matiere:id,nom',
            ])
            ->latest();

        if ($matiereId) {
            $query->where('matiere_id', $matiereId);
        }

        $notes = $query->limit($limit)->get();
        $matiere = $matiereId ? Matiere::select('id','nom')->find($matiereId) : null;

        try {
            $pdf = Pdf::loadView('professeur.exports.notes-pdf', [
                    'notes'   => $notes,
                    'matiere' => $matiere,
                    'user'    => $user,
                    'cap'     => $limit,
                ])
                ->setPaper('a4', 'portrait');

            return $pdf->download('notes_' . now()->format('Y-m-d_H-i') . '.pdf');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur lors de la génération du PDF (notes) : ' . $e->getMessage());
        }
    }

    /**
     * Export des notes en Excel
     */
    public function exportNotesExcel(Request $request)
    {
        $user = Auth::user();
        $matiereId = $request->input('matiere_id');

        return Excel::download(
            new NotesExport($user->id, $matiereId),
            'notes_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export des stagiaires en PDF
     */
    public function exportStagiairesPdf()
    {
        $user = Auth::user();

        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', '120');

        $filiereIds = $user->filieres->pluck('id')->toArray();

        $stagiaires = Stagiaire::select(['id','nom','prenom','matricule','filiere_id','classe_id','niveau_id'])
            ->with([
                'filiere:id,nom',
                'classe:id,nom',
                'niveau:id,nom',
            ])
            ->whereIn('filiere_id', $filiereIds)
            ->where('statut', 'actif')
            ->where('is_active', true)
            ->limit(800)
            ->get();

        try {
            $pdf = Pdf::loadView('professeur.exports.stagiaires-pdf', [
                    'stagiaires' => $stagiaires,
                    'user'       => $user,
                ])
                ->setPaper('a4', 'portrait');

            return $pdf->download('stagiaires_' . now()->format('Y-m-d_H-i') . '.pdf');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur lors de la génération du PDF (stagiaires) : ' . $e->getMessage());
        }
    }

    /**
     * Export des stagiaires en Excel
     */
    public function exportStagiairesExcel()
    {
        $user = Auth::user();

        return Excel::download(
            new StagiairesExport($user->id),
            'stagiaires_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Gestion des présences
     */
    public function presences(Request $request)
    {
        $user = Auth::user();
        $date = $request->input('date', now()->format('Y-m-d'));
        $classeId = $request->input('classe_id');

        $filiereIds = $user->filieres->pluck('id');

        $classes = Classe::whereIn('filiere_id', $filiereIds)
            ->with(['niveau:id,nom', 'filiere:id,nom'])
            ->get();

        $stagiaires = collect();
        $absences   = collect();

        if ($classeId) {
            $classe = Classe::findOrFail($classeId);

            if (!$filiereIds->contains($classe->filiere_id)) {
                abort(403, 'Accès refusé à cette classe.');
            }

            $stagiaires = $classe->stagiaires()
                ->where('statut', 'actif')
                ->where('is_active', true)
                ->with(['filiere:id,nom'])
                ->get();

            $absences = Absence::whereIn('stagiaire_id', $stagiaires->pluck('id'))
                ->whereDate('date', $date)
                ->get()
                ->keyBy('stagiaire_id');
        }

        return view('professeur.presences', compact('classes', 'stagiaires', 'absences', 'date', 'classeId'));
    }

    /**
     * Marquer une absence
     */
    public function marquerAbsence(Request $request)
    {
        $validated = $request->validate([
            'stagiaire_id' => 'required|exists:stagiaires,id',
            'date'         => 'required|date',
            'type'         => 'required|in:matin,apres_midi,journee,heure',
            'heure_debut'  => 'required_if:type,heure|nullable|date_format:H:i',
            'heure_fin'    => 'required_if:type,heure|nullable|date_format:H:i|after:heure_debut',
            'motif'        => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $stagiaire = Stagiaire::findOrFail($validated['stagiaire_id']);

        // Accès
        $filiereIds = $user->filieres->pluck('id');
        if (!$filiereIds->contains($stagiaire->filiere_id)) {
            return back()->with('error', 'Accès refusé à ce stagiaire.');
        }

        // Pas de chevauchement (matin + après-midi possibles le même jour)
        if (Absence::chevauche($stagiaire->id, $validated['date'], $validated['type'], $validated['heure_debut'] ?? null, $validated['heure_fin'] ?? null)) {
            return back()->with('error', 'Une absence couvrant ce créneau est déjà enregistrée pour cette date.');
        }

        Absence::create([
            'stagiaire_id' => $stagiaire->id,
            'periode_id'   => Absence::periodePourDate($validated['date']),
            'date'         => $validated['date'],
            'type'         => $validated['type'],
            'heure_debut'  => $validated['heure_debut'] ?? null,
            'heure_fin'    => $validated['heure_fin'] ?? null,
            'motif'        => $validated['motif'] ?? null,
            'justifiee'    => false,
            'created_by'   => $user->id,
        ]);

        return back()->with('success', 'Absence enregistrée avec succès.');
    }

    /**
     * Supprimer une absence (marquer présent)
     */
    public function supprimerAbsence($absenceId)
    {
        $user = Auth::user();
        $absence = Absence::findOrFail($absenceId);

        if ($absence->created_by !== $user->id) {
            return back()->with('error', 'Vous ne pouvez supprimer que vos propres enregistrements.');
        }

        $absence->delete();

        return back()->with('success', 'Absence supprimée (stagiaire marqué présent).');
    }

    /**
     * Afficher le planning du professeur
     */
    public function monPlanning(Request $request)
    {
        $user = Auth::user();
        $date = $request->input('date', now()->format('Y-m-d'));

        $plannings = Planning::where('professeur_id', $user->id)
            ->whereDate('date', $date)
            ->with(['salle:id,nom', 'matiere:id,nom', 'classe.niveau:id,nom', 'classe.filiere:id,nom'])
            ->orderBy('heure_debut')
            ->get();

        return view('professeur.planning', compact('plannings', 'date'));
    }

    /**
     * Formulaire de création de planning
     */
    public function createPlanning()
    {
        $user = Auth::user();
        $filiereIds = $user->filieres->pluck('id');

        $classes = Classe::whereIn('filiere_id', $filiereIds)
            ->with('filiere:id,nom', 'niveau:id,nom')
            ->get();

        $matieres = $user->matieresEnseignees()
            ->wherePivot('is_active', true)
            ->get();

        $salles = Salle::where('disponible', true)->get();

        return view('professeur.planning-create', compact('classes', 'matieres', 'salles'));
    }

    /**
     * Enregistrer un planning
     */
    public function storePlanning(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'classe_id'   => 'required|exists:classes,id',
            'matiere_id'  => 'required|exists:matieres,id',
            'salle_id'    => 'required|exists:salles,id',
            'date'        => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin'   => 'required|date_format:H:i|after:heure_debut',
            'type_cours'  => 'required|in:cours,td,tp,examen',
            'description' => 'nullable|string|max:1000',
        ]);

        // Accès à la classe
        $classe = Classe::findOrFail($validated['classe_id']);
        $filiereIds = $user->filieres->pluck('id');

        if (!$filiereIds->contains($classe->filiere_id)) {
            return back()->with('error', 'Vous n\'avez pas accès à cette classe.');
        }

        // Matière enseignée par le professeur
        if (!$user->canTeachMatiere($validated['matiere_id'])) {
            return back()->withInput()->with('error', 'Vous ne pouvez planifier que les matières qui vous sont affectées.');
        }

        // Disponibilité de la salle
        $salle = Salle::findOrFail($validated['salle_id']);
        $isDisponible = $salle->isDisponible(
            $validated['date'],
            $validated['heure_debut'],
            $validated['heure_fin']
        );

        if (!$isDisponible) {
            return back()->with('error', 'Cette salle n\'est pas disponible à cet horaire.');
        }

        Planning::create([
            'professeur_id' => $user->id,
            'classe_id'     => $validated['classe_id'],
            'matiere_id'    => $validated['matiere_id'],
            'salle_id'      => $validated['salle_id'],
            'date'          => $validated['date'],
            'heure_debut'   => $validated['heure_debut'],
            'heure_fin'     => $validated['heure_fin'],
            'type_cours'    => $validated['type_cours'],
            'description'   => $validated['description'] ?? null,
            'statut'        => 'brouillon',
            'created_by'    => $user->id,
        ]);

        return redirect()->route('professeur.planning')
            ->with('success', 'Planning créé avec succès. En attente de validation.');
    }

    // ==================== MÉTHODES ADMIN ====================

    public function editFilieres(User $professeur)
    {
        $professeur->load('filieres');
        $filieres = Filiere::orderBy('nom')->get();
        return view('professeurs.filieres', compact('professeur', 'filieres'));
    }

    public function updateFilieres(Request $request, User $professeur)
    {
        $request->validate([
            'filieres'   => 'nullable|array',
            'filieres.*' => 'exists:filieres,id',
        ]);

        abort_unless($professeur->role === 'professeur', 404);

        $sync = [];
        foreach ($request->input('filieres', []) as $filiereId) {
            $sync[$filiereId] = [
                'created_by'       => Auth::id(),
                'is_active'        => true,
                'date_assignation' => now()->toDateString(),
            ];
        }

        // syncWithoutDetaching ne touche pas aux pivots existants ; on détache ensuite les filières retirées
        $professeur->filieres()->syncWithoutDetaching($sync);
        $professeur->filieres()->detach(
            $professeur->filieres()->pluck('filieres.id')->diff(array_keys($sync))->all()
        );

        return redirect()->route('users.index')
            ->with('success', 'Filières mises à jour avec succès.');
    }

    public function editMatieres(User $professeur)
    {
        $professeur->load('matieresEnseignees');
        $matieres = Matiere::all();
        $filieres = Filiere::all();

        return view('professeurs.matieres', compact('professeur', 'matieres', 'filieres'));
    }

    public function updateMatieres(Request $request, User $professeur)
    {
        $request->validate([
            'matieres'   => 'nullable|array',
            'matieres.*' => 'exists:matieres,id',
            'filiere_id' => 'required|exists:filieres,id',
        ]);

        $sync = [];
        foreach ($request->input('matieres', []) as $matiereId) {
            $sync[$matiereId] = [
                'filiere_id'      => $request->filiere_id,
                'assigned_by'     => Auth::id(),
                'is_active'       => true,
                'date_assignation'=> now()->toDateString(),
            ];
        }

        abort_unless($professeur->role === 'professeur', 404);

        // On ne remplace que les affectations de la filière choisie
        $professeur->matieresEnseignees()
            ->wherePivot('filiere_id', $request->filiere_id)
            ->sync($sync);

        // Le professeur doit aussi être rattaché à la filière
        if (!empty($sync)) {
            $professeur->filieres()->syncWithoutDetaching([
                $request->filiere_id => [
                    'created_by'       => Auth::id(),
                    'is_active'        => true,
                    'date_assignation' => now()->toDateString(),
                ],
            ]);
        }

        return redirect()->route('professeurs.matieres.edit', [$professeur, 'filiere_id' => $request->filiere_id])
            ->with('success', 'Matières mises à jour avec succès.');
    }
}