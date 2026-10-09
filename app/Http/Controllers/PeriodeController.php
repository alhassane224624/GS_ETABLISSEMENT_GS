<?php

namespace App\Http\Controllers;

use App\Models\Periode;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;

class PeriodeController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $annees = AnneeScolaire::with('periodes')->orderBy('debut', 'desc')->get();
        return view('periodes.index', compact('annees'));
    }

    public function create()
    {
        $annees = AnneeScolaire::all();
        return view('periodes.create', compact('annees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|in:semestre,trimestre,periode',
            'debut' => 'required|date',
            'fin' => 'required|date|after:debut',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'is_active' => 'boolean'
        ]);

        // Si cette période doit être active, désactiver les autres de la même année
        if ($request->is_active) {
            Periode::where('annee_scolaire_id', $request->annee_scolaire_id)
                ->update(['is_active' => false]);
        }

        Periode::create($validated);

        return redirect()->route('periodes.index')
            ->with('success', 'Période créée avec succès.');
    }

    public function show($periode)
    {
        $periode = Periode::with('anneeScolaire')
            ->withCount(['notes', 'bulletins'])
            ->findOrFail($periode);

        $stats = [
            'bulletins_valides' => $periode->bulletins()->whereNotNull('validated_at')->count(),
            'absences'          => \App\Models\Absence::where('periode_id', $periode->id)->count(),
            'moyenne'           => $periode->bulletins()->avg('moyenne_generale'),
        ];

        return view('periodes.show', compact('periode', 'stats'));
    }

    public function edit($periode)
    {
        $periode = Periode::findOrFail($periode);
        $annees = AnneeScolaire::all();
        
        return view('periodes.edit', compact('periode', 'annees'));
    }

    public function update(Request $request, $periode)
    {
        $periode = Periode::findOrFail($periode);
        
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|in:semestre,trimestre,periode',
            'debut' => 'required|date',
            'fin' => 'required|date|after:debut',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'is_active' => 'boolean'
        ]);

        // Si cette période devient active, désactiver les autres de la même année
        if ($request->is_active) {
            Periode::where('annee_scolaire_id', $request->annee_scolaire_id)
                ->where('id', '!=', $periode->id)
                ->update(['is_active' => false]);
        }

        $periode->update($validated);

        return redirect()->route('periodes.index')
            ->with('success', 'Période mise à jour avec succès.');
    }

    public function destroy($periode)
    {
        $periode = Periode::findOrFail($periode);
        
        // Vérifier s'il y a des notes associées
        if ($periode->notes()->exists()) {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer cette période car elle contient des notes.');
        }

        $periode->delete();

        return redirect()->route('periodes.index')
            ->with('success', 'Période supprimée avec succès.');
    }

    public function activerPeriode($periode)
    {
        $periode = Periode::findOrFail($periode);
        
        // Désactiver toutes les autres périodes de la même année
        Periode::where('annee_scolaire_id', $periode->annee_scolaire_id)
            ->update(['is_active' => false]);

        // Activer cette période
        $periode->update(['is_active' => true]);

        return redirect()->back()
            ->with('success', "Période {$periode->nom} activée avec succès.");
    }
}