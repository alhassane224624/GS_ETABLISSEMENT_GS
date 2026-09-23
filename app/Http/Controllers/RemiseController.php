<?php

namespace App\Http\Controllers;

use App\Models\Remise;
use App\Models\Stagiaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// ============================================================================
// REMISE CONTROLLER
// ============================================================================

class RemiseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // ✅ MODIFIÉ - Accès admin ET comptable
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->hasFinancialAccess()) {
                abort(403, 'Accès réservé aux comptables et administrateurs');
            }
            return $next($request);
        });
    }

    /**
     * Liste des remises
     */
    public function index(Request $request)
    {
        $query = Remise::with(['stagiaire.filiere', 'createur']);

        if ($request->filled('stagiaire_id')) {
            $query->where('stagiaire_id', $request->stagiaire_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $remises = $query->latest()->paginate(15);

        $stats = [
            'total_remises' => Remise::count(),
            'remises_actives' => Remise::where('is_active', true)->count(),
            'montant_total_remises' => Remise::where('is_active', true)
                ->where('type', 'montant_fixe')
                ->sum('valeur'),
        ];

        return view('remises.index', compact('remises', 'stats'));
    }

    /**
     * Formulaire de création
     */
    public function create(Request $request)
    {
        $stagiaireId = $request->input('stagiaire_id');
        $stagiaire = $stagiaireId ? Stagiaire::findOrFail($stagiaireId) : null;
        
        $stagiaires = Stagiaire::actifs()
            ->with('filiere')
            ->orderBy('nom')
            ->get();

        return view('remises.create', compact('stagiaires', 'stagiaire'));
    }

    /**
     * Enregistrer une remise
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'stagiaire_id' => 'required|exists:stagiaires,id',
            'titre' => 'required|string|max:255',
            'type' => 'required|in:pourcentage,montant_fixe',
            'valeur' => 'required|numeric|min:0',
            'motif' => 'required|string|max:1000',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'is_active' => 'boolean',
        ]);

        // Validation supplémentaire pour pourcentage
        if ($validated['type'] === 'pourcentage' && $validated['valeur'] > 100) {
            return back()->withErrors(['valeur' => 'Le pourcentage ne peut pas dépasser 100%'])->withInput();
        }

        $remise = Remise::create([
            'stagiaire_id' => $validated['stagiaire_id'],
            'created_by' => Auth::id(),
            'titre' => $validated['titre'],
            'type' => $validated['type'],
            'valeur' => $validated['valeur'],
            'motif' => $validated['motif'],
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('remises.index')
            ->with('success', 'Remise créée avec succès.');
    }

    /**
     * Afficher une remise
     */
    public function show(Remise $remise)
    {
        $remise->load(['stagiaire.filiere', 'stagiaire.classe', 'createur']);

        return view('remises.show', compact('remise'));
    }

    /**
     * Formulaire de modification
     */
    public function edit(Remise $remise)
    {
        $stagiaires = Stagiaire::actifs()
            ->with('filiere')
            ->orderBy('nom')
            ->get();

        return view('remises.edit', compact('remise', 'stagiaires'));
    }

    /**
     * Mettre à jour une remise
     */
    public function update(Request $request, Remise $remise)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'type' => 'required|in:pourcentage,montant_fixe',
            'valeur' => 'required|numeric|min:0',
            'motif' => 'required|string|max:1000',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'is_active' => 'boolean',
        ]);

        // Validation supplémentaire pour pourcentage
        if ($validated['type'] === 'pourcentage' && $validated['valeur'] > 100) {
            return back()->withErrors(['valeur' => 'Le pourcentage ne peut pas dépasser 100%'])->withInput();
        }

        $remise->update($validated);

        return redirect()->route('remises.index')
            ->with('success', 'Remise mise à jour avec succès.');
    }

    /**
     * Supprimer une remise
     */
    public function destroy(Remise $remise)
    {
        $remise->delete();

        return redirect()->route('remises.index')
            ->with('success', 'Remise supprimée avec succès.');
    }

    /**
     * Activer/Désactiver une remise
     */
    public function toggleActive(Remise $remise)
    {
        $remise->update([
            'is_active' => !$remise->is_active
        ]);

        $status = $remise->is_active ? 'activée' : 'désactivée';

        return redirect()->back()
            ->with('success', "Remise {$status} avec succès.");
    }
}

