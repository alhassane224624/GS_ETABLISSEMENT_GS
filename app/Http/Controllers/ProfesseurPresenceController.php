<?php

namespace App\Http\Controllers;

use App\Models\Stagiaire;
use App\Models\Absence;
use App\Models\Classe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Espace professeur : appel et absences
 */
class ProfesseurPresenceController extends Controller
{
    public function __construct()
    {
        $this->middleware('professeur');
    }

    /**
     * Gestion des présences
     */
    public function presences(Request $request)
    {
        $user = Auth::user();
        $date = $request->input('date', now()->format('Y-m-d'));
        $classeId = $request->input('classe_id');

        $filiereIds = $user->filieresActives->pluck('id');

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
        $filiereIds = $user->filieresActives->pluck('id');
        if (!$filiereIds->contains($stagiaire->filiere_id)) {
            return back()->with('error', 'Accès refusé à ce stagiaire.');
        }

        // Pas de chevauchement (matin + après-midi possibles le même jour)
        if (Absence::chevauche($stagiaire->id, $validated['date'], $validated['type'], $validated['heure_debut'] ?? null, $validated['heure_fin'] ?? null)) {
            return back()->with('error', 'Une absence couvrant ce créneau est déjà enregistrée pour cette date.');
        }

        $absence = Absence::create([
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
        app(\App\Services\NotificationsParents::class)->absence($absence);

        return back()->with('success', 'Absence enregistrée avec succès.');
    }

    /**
     * Supprimer une absence (marquer présent)
     */
    public function supprimerAbsence($absenceId)
    {
        $user = Auth::user();
        $absence = Absence::findOrFail($absenceId);

        if ((int) $absence->created_by !== (int) $user->id) {
            return back()->with('error', 'Vous ne pouvez supprimer que vos propres enregistrements.');
        }

        $absence->delete();

        return back()->with('success', 'Absence supprimée (stagiaire marqué présent).');
    }
}
