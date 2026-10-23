<?php

namespace App\Http\Controllers;

use App\Models\Planning;
use App\Models\Classe;
use App\Models\Salle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Espace professeur : planning et demandes de séances
 */
class ProfesseurPlanningController extends Controller
{
    public function __construct()
    {
        $this->middleware('professeur');
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
        $filiereIds = $user->filieresActives->pluck('id');

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
        $filiereIds = $user->filieresActives->pluck('id');

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
}
