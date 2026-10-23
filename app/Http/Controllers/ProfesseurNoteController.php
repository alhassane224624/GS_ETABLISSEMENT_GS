<?php

namespace App\Http\Controllers;

use App\Models\Stagiaire;
use App\Models\Note;
use App\Models\Matiere;
use App\Models\Periode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\NotesExport;

/**
 * Espace professeur : saisie et export des notes
 */
class ProfesseurNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('professeur');
    }

    /**
     * Afficher les notes d'un stagiaire
     */
    public function showNotes(Stagiaire $stagiaire)
    {
        $user = Auth::user();
        $filiereIds = $user->filieresActives()->pluck('filieres.id')->toArray();

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
        $filiereIds = $user->filieresActives->pluck('id')->toArray();

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
            'publie_at'    => null, // visible après publication des résultats
        ]);

        return back()->with('success', "Note enregistrée. Elle reste masquée pour le stagiaire jusqu'à ce que vous publiiez les résultats (menu Publications).");
    }

    /**
     * Modifier une note
     */
    public function updateNote(Request $request, Stagiaire $stagiaire, Note $note)
    {
        $user = Auth::user();

        // Sécurité
        if ((int) $note->stagiaire_id !== (int) $stagiaire->id) {
            return back()->with('error', 'Cette note n\'appartient pas à ce stagiaire.');
        }
        if ((int) $note->created_by !== (int) $user->id) {
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

        $filiereIds = $user->filieresActives->pluck('id')->toArray();

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

        $filiereIds = $user->filieresActives->pluck('id')->toArray();

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
}
