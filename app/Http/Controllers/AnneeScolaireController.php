<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Periode;
use Illuminate\Http\Request;

class AnneeScolaireController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $annees = AnneeScolaire::with('periodes')
                              ->withCount('periodes')
                              ->orderBy('debut', 'desc')
                              ->get();
        
        return view('annees-scolaires.index', compact('annees'));
    }

    public function create()
    {
        return view('annees-scolaires.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:annee_scolaires,nom',
            'debut' => 'required|date',
            'fin' => 'required|date|after:debut',
            'is_active' => 'boolean'
        ]);

        // Si cette année doit être active, désactiver les autres
        if ($validated['is_active'] ?? false) {
            AnneeScolaire::where('is_active', true)->update(['is_active' => false]);
        }

        $annee = AnneeScolaire::create($validated);

        // Créer automatiquement des périodes par défaut si demandé
        if ($request->has('create_default_periods')) {
            $this->createDefaultPeriods($annee);
        }

        return redirect()->route('annees-scolaires.index')
                        ->with('success', 'Année scolaire créée avec succès.');
    }

    public function show($annees_scolaire)
    {
        $anneeScolaire = AnneeScolaire::with('periodes')->findOrFail($annees_scolaire);
        
        // Statistiques de l'année
        $stats = [
            'total_periodes' => $anneeScolaire->periodes->count(),
            'periode_active' => $anneeScolaire->periodes->where('is_active', true)->first(),
            'duree_totale' => ($anneeScolaire->debut && $anneeScolaire->fin)
                ? $anneeScolaire->debut->diffInDays($anneeScolaire->fin)
                : 0,
            'progression' => $this->calculateProgression($anneeScolaire),
        ];

        return view('annees-scolaires.show', compact('anneeScolaire', 'stats'));
    }

    public function edit($annees_scolaire)
    {
        $anneeScolaire = AnneeScolaire::findOrFail($annees_scolaire);
        return view('annees-scolaires.edit', compact('anneeScolaire'));
    }

    public function update(Request $request, $annees_scolaire)
    {
        $anneeScolaire = AnneeScolaire::findOrFail($annees_scolaire);
        
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:annee_scolaires,nom,' . $anneeScolaire->id,
            'debut' => 'required|date',
            'fin' => 'required|date|after:debut',
            'is_active' => 'boolean'
        ]);

        // Si cette année doit être active, désactiver les autres
        if ($validated['is_active'] ?? false) {
            AnneeScolaire::where('id', '!=', $anneeScolaire->id)
                         ->where('is_active', true)
                         ->update(['is_active' => false]);
        }

        $anneeScolaire->update($validated);

        return redirect()->route('annees-scolaires.index')
                        ->with('success', 'Année scolaire mise à jour avec succès.');
    }

    public function destroy($annees_scolaire)
    {
        $anneeScolaire = AnneeScolaire::findOrFail($annees_scolaire);
        
        // Vérifier s'il y a des périodes avec des notes
        $hasNotes = $anneeScolaire->periodes()
                                 ->whereHas('notes')
                                 ->exists();

        if ($hasNotes) {
            return redirect()->back()
                           ->with('error', 'Impossible de supprimer cette année car elle contient des notes.');
        }

        $anneeScolaire->delete();

        return redirect()->route('annees-scolaires.index')
                        ->with('success', 'Année scolaire supprimée avec succès.');
    }

    public function activate($annees_scolaire)
    {
        $anneeScolaire = AnneeScolaire::findOrFail($annees_scolaire);
        
        // Désactiver toutes les autres années
        AnneeScolaire::where('is_active', true)->update(['is_active' => false]);
        
        // Activer cette année
        $anneeScolaire->update(['is_active' => true]);

        // Chaque stagiaire passe dans sa classe de la nouvelle année (inscriptions)
        $r = app(\App\Services\InscriptionService::class)->synchroniserAnneeActive($anneeScolaire);

        $message = "Année {$anneeScolaire->nom} activée : {$r['bascules']} stagiaire(s) placé(s) dans leur classe de l'année.";
        if ($r['sans_inscription'] > 0) {
            $message .= " {$r['sans_inscription']} stagiaire(s) actif(s) ne sont pas inscrits pour cette année (voir Inscriptions).";
        }

        return redirect()->back()->with('success', $message);
    }

    public function duplicate($annees_scolaire)
    {
        $anneeScolaire = AnneeScolaire::with('periodes')->findOrFail($annees_scolaire);
        
        $nextYear = $anneeScolaire->debut->copy()->addYear();
        $newNom = $nextYear->format('Y') . '-' . $nextYear->copy()->addYear()->format('Y');
        
        $nouvelleAnnee = AnneeScolaire::create([
            'nom' => $newNom,
            'debut' => $anneeScolaire->debut->copy()->addYear(),
            'fin' => $anneeScolaire->fin->copy()->addYear(),
            'is_active' => false
        ]);

        // Dupliquer les périodes avec les nouvelles dates
        foreach ($anneeScolaire->periodes as $periode) {
            Periode::create([
                'nom' => $periode->nom,
                'type' => $periode->type,
                'debut' => $periode->debut->copy()->addYear(),
                'fin' => $periode->fin->copy()->addYear(),
                'annee_scolaire_id' => $nouvelleAnnee->id,
                'is_active' => false
            ]);
        }

        return redirect()->route('annees-scolaires.show', $nouvelleAnnee->id)
                        ->with('success', "Année {$newNom} créée par duplication avec ses périodes.");
    }

    private function createDefaultPeriods(AnneeScolaire $annee)
    {
        // Créer 2 semestres par défaut
        $milieu = $annee->debut->copy()->addMonths(6);

        Periode::create([
            'nom' => 'Semestre 1',
            'type' => 'semestre',
            'debut' => $annee->debut,
            'fin' => $milieu->copy()->subDay(),
            'annee_scolaire_id' => $annee->id,
            'is_active' => true
        ]);

        Periode::create([
            'nom' => 'Semestre 2',
            'type' => 'semestre',
            'debut' => $milieu,
            'fin' => $annee->fin,
            'annee_scolaire_id' => $annee->id,
            'is_active' => false
        ]);
    }

    private function calculateProgression(AnneeScolaire $annee)
    {
        if (!$annee->debut || !$annee->fin) {
            return 0; // Empêche l'erreur si une des dates est null
        }

        // Convertir en objets Carbon si nécessaire
        $debut = \Carbon\Carbon::parse($annee->debut);
        $fin = \Carbon\Carbon::parse($annee->fin);
        $now = now();

        $totalJours = $debut->diffInDays($fin);
        $joursEcoules = max(0, min($totalJours, $debut->diffInDays($now)));

        return $totalJours > 0 ? round(($joursEcoules / $totalJours) * 100) : 0;
    }

    public function getActive()
    {
        $annee = AnneeScolaire::where('is_active', true)->first();
        
        if (!$annee) {
            return response()->json([
                'error' => 'Aucune année scolaire active'
            ], 404);
        }

        return response()->json($annee);
    }
}