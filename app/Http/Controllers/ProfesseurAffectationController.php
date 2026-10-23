<?php

namespace App\Http\Controllers;

use App\Models\Filiere;
use App\Models\Matiere;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Administration : affectation des professeurs aux filières et matières
 */
class ProfesseurAffectationController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }


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
