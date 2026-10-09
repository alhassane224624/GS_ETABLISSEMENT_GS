<?php
// =============================================================================
// FICHIER 8: PlanningController.php (MIS À JOUR avec nouvelle structure)
// =============================================================================
namespace App\Http\Controllers;

use App\Models\Planning;
use App\Models\Filiere;
use App\Models\User;
use App\Models\Salle;
use App\Models\Matiere;
use App\Models\Classe;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PlanningController extends Controller
{
    public function index(Request $request)
    {
        $semaine = $request->get('semaine', now()->format('o-W'));
        $classe_id = $request->get('classe_id');
        $professeur_id = $request->get('professeur_id');
        $salle_id = $request->get('salle_id');

        if (!preg_match('/^\d{4}-W\d{1,2}$/', $semaine)) {
            $semaine = now()->format('o-W');
        }

        $parts = explode('-W', $semaine);
        if (count($parts) !== 2) {
            $annee = now()->year;
            $numeroSemaine = now()->week;
        } else {
            [$annee, $numeroSemaine] = $parts;
        }

        $debutSemaine = Carbon::now()
            ->setISODate($annee, $numeroSemaine)
            ->startOfWeek();
        $finSemaine = $debutSemaine->copy()->endOfWeek();

        $query = Planning::whereBetween('date', [$debutSemaine, $finSemaine])
            ->with(['professeur', 'salle', 'matiere', 'classe.niveau', 'classe.filiere']);

        if ($classe_id) {
            $query->where('classe_id', $classe_id);
        }

        if ($professeur_id) {
            $query->where('professeur_id', $professeur_id);
        }

        if ($salle_id) {
            $query->where('salle_id', $salle_id);
        }

        $plannings = $query->orderBy('date')->orderBy('heure_debut')->get();

        // Organiser par jour
        $planning_semaine = [];
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        
        for ($i = 0; $i < 6; $i++) {
            $jour = $debutSemaine->copy()->addDays($i);
            $planning_semaine[$jours[$i]] = [
                'date' => $jour,
                'cours' => $plannings->filter(function($planning) use ($jour) {
                    return $planning->date->isSameDay($jour);
                })->sortBy('heure_debut')
            ];
        }

        $classes = Classe::with('niveau', 'filiere')->get();
        $professeurs = User::where('role', 'professeur')->get();
        $salles = Salle::all();

        return view('planning.index', compact(
            'planning_semaine', 
            'semaine', 
            'classes',
            'professeurs', 
            'salles',
            'classe_id',
            'professeur_id',
            'salle_id'
        ));
    }

    public function create(Request $request)
    {
        $professeurs = User::where('role', 'professeur')->get();
        $salles = Salle::where('disponible', true)->get();
        $matieres = Matiere::all();
        $classes = Classe::with('niveau', 'filiere')->get();
        
        $date = $request->get('date');
        $heure = $request->get('heure');
        
        return view('planning.create', compact('professeurs', 'salles', 'matieres', 'classes', 'date', 'heure'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'professeur_id' => 'required|exists:users,id',
            'salle_id' => 'required|exists:salles,id',
            'matiere_id' => 'required|exists:matieres,id',
            'classe_id' => 'required|exists:classes,id',
            'date' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'type_cours' => 'required|in:cours,td,tp,examen',
            'description' => 'nullable|string|max:500',
            'statut' => 'nullable|in:brouillon,valide,en_cours,termine,annule',
        ]);

        // Vérifier les conflits
        $conflits = $this->verifierConflits($validated);
        
        if ($conflits['has_conflict']) {
            return back()->withErrors(['conflit' => $conflits['message']])->withInput();
        }

        $validated['created_by'] = Auth::id();
        $validated['statut'] = $validated['statut'] ?? 'brouillon';

        Planning::create($validated);

        return redirect()->route('planning.index')
            ->with('success', 'Cours ajouté au planning avec succès.');
    }

    public function show(Planning $planning)
    {
        $planning->load(['professeur', 'salle', 'matiere', 'classe', 'creator', 'validator', 'absences.stagiaire']);

        return view('planning.show', compact('planning'));
    }

    public function edit(Planning $planning)
    {
        $professeurs = User::where('role', 'professeur')->get();
        $salles = Salle::where('disponible', true)->get();
        $matieres = Matiere::all();
        $classes = Classe::with('niveau', 'filiere')->get();
        
        return view('planning.edit', compact('planning', 'professeurs', 'salles', 'matieres', 'classes'));
    }

    public function update(Request $request, Planning $planning)
    {
        $validated = $request->validate([
            'professeur_id' => 'required|exists:users,id',
            'salle_id' => 'required|exists:salles,id',
            'matiere_id' => 'required|exists:matieres,id',
            'classe_id' => 'required|exists:classes,id',
            'date' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'type_cours' => 'required|in:cours,td,tp,examen',
            'description' => 'nullable|string|max:500',
            'statut' => 'required|in:brouillon,valide,en_cours,termine,annule',
            'motif_annulation' => 'nullable|string|max:500',
        ]);

        // Vérifier les conflits (en excluant le cours actuel)
        $conflits = $this->verifierConflits($validated, $planning->id);
        
        if ($conflits['has_conflict']) {
            return back()->withErrors(['conflit' => $conflits['message']])->withInput();
        }

        $planning->update($validated);

        return redirect()->route('planning.index')
            ->with('success', 'Cours modifié avec succès.');
    }

    public function destroy(Planning $planning)
    {
        $planning->delete();
        return response()->json(['success' => true]);
    }

    public function valider(Planning $planning)
    {
        $planning->update([
            'statut' => 'valide',
            'validated_by' => Auth::id(),
            'validated_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'Planning validé avec succès.');
    }

    public function annuler(Request $request, Planning $planning)
    {
        $validated = $request->validate([
            'motif_annulation' => 'required|string|max:500',
        ]);

        $planning->update([
            'statut' => 'annule',
            'motif_annulation' => $validated['motif_annulation'],
        ]);

        return redirect()->back()
            ->with('success', 'Planning annulé avec succès.');
    }

    private function verifierConflits($data, $excludeId = null)
    {
        $date = $data['date'];
        $heureDebut = $data['heure_debut'];
        $heureFin = $data['heure_fin'];
        $salleId = $data['salle_id'];
        $professeurId = $data['professeur_id'];
        $classeId = $data['classe_id'];

        $query = Planning::where('date', $date)
            ->whereIn('statut', ['valide', 'en_cours'])
            ->where(function($q) use ($heureDebut, $heureFin) {
                $q->where('heure_debut', '<', $heureFin)
                  ->where('heure_fin', '>', $heureDebut);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Conflit de salle
        $conflitSalle = $query->clone()->where('salle_id', $salleId)->first();
        if ($conflitSalle) {
            return [
                'has_conflict' => true,
                'message' => "Conflit: La salle {$conflitSalle->salle->nom} est déjà occupée"
            ];
        }

        // Conflit de professeur
        $conflitProfesseur = $query->clone()->where('professeur_id', $professeurId)->first();
        if ($conflitProfesseur) {
            return [
                'has_conflict' => true,
                'message' => "Conflit: Le professeur a déjà un cours à cette heure"
            ];
        }

        // Conflit de classe
        $conflitClasse = $query->clone()->where('classe_id', $classeId)->first();
        if ($conflitClasse) {
            return [
                'has_conflict' => true,
                'message' => "Conflit: La classe a déjà un cours à cette heure"
            ];
        }

        return ['has_conflict' => false];
    }
}