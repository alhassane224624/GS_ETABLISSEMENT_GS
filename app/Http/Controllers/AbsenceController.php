<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\Stagiaire;
use App\Models\Filiere;
use App\Models\Periode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// 🔔 AJOUT : Importer la notification
use App\Notifications\AbsenceCreated;

class AbsenceController extends Controller
{
    // Garder toutes les méthodes index, create inchangées...

    public function index(Request $request)
    {
        $query = Absence::with(['stagiaire.filiere', 'creator']);

        if ($request->filled('filiere_id')) {
            $query->whereHas('stagiaire', function($q) use ($request) {
                $q->where('filiere_id', $request->filiere_id);
            });
        }

        if ($request->filled('date_debut')) {
            $query->where('date', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->where('date', '<=', $request->date_fin);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('justifiee')) {
            $query->where('justifiee', $request->justifiee);
        }

        $absences = $query->latest()->paginate(20);
        $filieres = Filiere::all();
        
        return view('absences.index', compact('absences', 'filieres'));
    }

    public function create(Request $request)
    {
        $stagiaires = Stagiaire::with('filiere')->get();
        $filieres = Filiere::all();
        $stagiaire_id = $request->get('stagiaire_id');
        
        return view('absences.create', compact('stagiaires', 'filieres', 'stagiaire_id'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'stagiaire_id' => 'required|exists:stagiaires,id',
            'date' => 'required|date',
            'type' => 'required|in:matin,apres_midi,journee,heure',
            'heure_debut' => 'required_if:type,heure|nullable|date_format:H:i',
            'heure_fin' => 'required_if:type,heure|nullable|date_format:H:i|after:heure_debut',
            'motif' => 'nullable|string|max:500',
            'justifiee' => 'boolean',
            'document_justificatif' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        if (Absence::chevauche((int) $request->stagiaire_id, $request->date, $request->type, $request->heure_debut, $request->heure_fin)) {
            return back()->withInput()->with('error', 'Une absence couvrant ce créneau est déjà enregistrée pour cette date.');
        }

        $data = $request->only(['stagiaire_id', 'date', 'type', 'heure_debut', 'heure_fin', 'motif']);
        $data['justifiee'] = $request->boolean('justifiee');
        $data['periode_id'] = Absence::periodePourDate($request->date);
        $data['created_by'] = Auth::id();

        if ($request->hasFile('document_justificatif')) {
            $path = $request->file('document_justificatif')->store('justificatifs', 'public');
            $data['document_justificatif'] = $path;
        }

        $absence = Absence::create($data);

        // 🔔 NOTIFICATION : Notifier le stagiaire de son absence
        $stagiaire = Stagiaire::find($request->stagiaire_id);
        if ($stagiaire && $stagiaire->user) {
            $stagiaire->user->notify(new AbsenceCreated($absence));
        }

        return redirect()->route('absences.index')
            ->with('success', 'Absence enregistrée avec succès.');
    }

    // ... Garder toutes les autres méthodes sans modification
    public function show(Absence $absence)
    {
        $absence->load(['stagiaire.filiere', 'creator']);
        return view('absences.show', compact('absence'));
    }

    public function edit(Absence $absence)
    {
        $stagiaires = Stagiaire::with('filiere')->get();
        return view('absences.edit', compact('absence', 'stagiaires'));
    }

    public function update(Request $request, Absence $absence)
    {
        $request->validate([
            'date' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'nullable|date_format:H:i|after:heure_debut',
            'type' => 'required|in:matin,apres_midi,journee,heure',
            'motif' => 'nullable|string|max:500',
            'justifiee' => 'boolean',
            'document_justificatif' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        $data = $request->except('document_justificatif');

        if ($request->hasFile('document_justificatif')) {
            if ($absence->document_justificatif) {
                Storage::disk('public')->delete($absence->document_justificatif);
            }
            
            $path = $request->file('document_justificatif')->store('justificatifs', 'public');
            $data['document_justificatif'] = $path;
        }

        $absence->update($data);

        return redirect()->route('absences.index')
            ->with('success', 'Absence mise à jour avec succès.');
    }

    public function destroy(Absence $absence)
    {
        if ($absence->document_justificatif) {
            Storage::disk('public')->delete($absence->document_justificatif);
        }

        $absence->delete();

        return response()->json(['success' => true]);
    }

    public function rapportAbsences(Request $request)
    {
        $filiere_id = $request->get('filiere_id');
        $date_debut = $request->get('date_debut', now()->startOfMonth());
        $date_fin = $request->get('date_fin', now()->endOfMonth());

        $query = Absence::whereBetween('date', [$date_debut, $date_fin])
            ->with(['stagiaire.filiere']);

        if ($filiere_id) {
            $query->whereHas('stagiaire', function($q) use ($filiere_id) {
                $q->where('filiere_id', $filiere_id);
            });
        }

        $absences = $query->get();
        
        $stats_stagiaires = $absences->groupBy('stagiaire_id')->map(function($absencesStagiaire) {
            $stagiaire = $absencesStagiaire->first()->stagiaire;
            return [
                'stagiaire' => $stagiaire,
                'total_absences' => $absencesStagiaire->count(),
                'absences_justifiees' => $absencesStagiaire->where('justifiee', true)->count(),
                'absences_injustifiees' => $absencesStagiaire->where('justifiee', false)->count(),
                'jours_absents' => $absencesStagiaire->where('type', 'journee')->count() + 
                                 ($absencesStagiaire->whereIn('type', ['matin', 'apres_midi'])->count() / 2),
            ];
        });

        $filieres = Filiere::all();

        return view('absences.rapport', compact('stats_stagiaires', 'filieres', 'date_debut', 'date_fin', 'filiere_id'));
    }

    public function exportAbsences(Request $request)
    {
        $filiere_id = $request->get('filiere_id');
        $date_debut = $request->get('date_debut', now()->startOfMonth());
        $date_fin = $request->get('date_fin', now()->endOfMonth());

        return Excel::download(
            new AbsencesExport($filiere_id, $date_debut, $date_fin),
            'absences_' . now()->format('Y_m_d') . '.xlsx'
        );
    }
}