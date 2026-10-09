<?php

namespace App\Http\Controllers;

use App\Models\Filiere;
use App\Models\Stagiaire;
use App\Models\Note;
use App\Models\User;
use App\Models\Salle;
use App\Models\Absence;
use App\Models\Planning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * ✅ Redirection intelligente vers le bon dashboard selon le rôle
     */
    public function index()
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // ✅ Log pour débogage
        Log::info('Redirection dashboard', [
            'user_id' => $user->id,
            'role' => $user->role,
            'is_active' => $user->is_active
        ]);

        // ✅ Vérifier si le compte est actif
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Votre compte a été désactivé. Contactez l\'administration.');
        }

        return match($user->role) {
            'administrateur' => redirect()->route('admin.dashboard'),
            'professeur' => redirect()->route('professeur.dashboard'),
            'stagiaire' => redirect()->route('stagiaire.dashboard'),
            default => $this->handleInvalidRole($user)
        };
    }

    // ✅ NOUVELLE MÉTHODE
    private function handleInvalidRole($user)
    {
        Log::error('Rôle invalide détecté', [
            'user_id' => $user->id,
            'role' => $user->role
        ]);

        Auth::logout();
        return redirect()->route('login')
            ->with('error', 'Rôle utilisateur invalide. Contactez l\'administrateur.');
    }

    /**
     * Dashboard Administrateur
     */
    public function adminDashboard()
    {
        // Vérification de sécurité
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Accès réservé aux administrateurs');
        }

        $data = [
            // Statistiques principales
            'total_stagiaires' => Stagiaire::count(),
            'total_filieres' => Filiere::count(),
            'total_professeurs' => User::where('role', 'professeur')->count(),
            'total_notes' => Note::count(),
            'total_salles' => Salle::count(),
            'salles_disponibles' => Salle::where('disponible', true)->count(),
            
            // Données du jour
            'absences_aujourd_hui' => Absence::whereDate('date', today())->count(),
            'cours_aujourd_hui' => Planning::whereDate('date', today())->count(),
            
            // Dernières inscriptions
            'recent_inscriptions' => Stagiaire::latest()
                ->take(5)
                ->with('filiere')
                ->get(),
            
            // Statistiques par filière
            'filieres_stats' => Filiere::withCount('stagiaires')->get(),
            
            // Statistiques des salles par type
            'salles_stats' => Salle::selectRaw('type, COUNT(*) as count, SUM(capacite) as capacite_totale')
                ->groupBy('type')
                ->get(),
            
            // Statistiques des notes
            'notes_stats' => Note::selectRaw('
                    matieres.nom as matiere,
                    COUNT(*) as total,
                    AVG(notes.note) as moyenne_generale,
                    MAX(notes.note) as note_max,
                    MIN(notes.note) as note_min
                ')
                ->join('matieres', 'notes.matiere_id', '=', 'matieres.id')
                ->groupBy('matieres.nom')
                ->orderBy('total', 'desc')
                ->get(),
        ];

        return view('dashboard.admin', compact('data'));
    }

    /**
     * Dashboard Professeur
     */
    public function professeurDashboard()
    {
        // Vérification de sécurité
        if (!Auth::user()->isProfesseur()) {
            abort(403, 'Accès réservé aux professeurs');
        }

        $user = Auth::user();

        // Récupérer les filières du professeur
        $filieres = $user->filieres()
            ->withCount(['stagiaires' => function($query) {
                $query->where('is_active', true);
            }])
            ->get();
        
        // Récupérer les matières
        $matieres = $user->matieresEnseignees()
            ->wherePivot('is_active', true)
            ->get();
        
        // Compter les stagiaires
        $mes_stagiaires = 0;
        if ($filieres->count() > 0) {
            $mes_stagiaires = Stagiaire::whereIn('filiere_id', $filieres->pluck('id'))
                ->where('is_active', true)
                ->count();
        }
        
        // Compter les notes
        $mes_notes = Note::where('created_by', $user->id)->count();
        
        // Planning du jour
        $planning_aujourd_hui = Planning::where('professeur_id', $user->id)
            ->whereDate('date', today())
            ->with([
                'salle', 
                'matiere',
                'classe.filiere',
                'classe.niveau'
            ])
            ->orderBy('heure_debut')
            ->get();
        
        // Dernières notes
        $recent_notes = Note::where('created_by', $user->id)
            ->with([
                'stagiaire.filiere',
                'stagiaire.classe',
                'matiere'
            ])
            ->latest()
            ->take(10)
            ->get();

        $data = [
            'filieres' => $filieres,
            'matieres' => $matieres,
            'total_stagiaires' => $mes_stagiaires,
            'total_notes' => $mes_notes,
            'planning_aujourd_hui' => $planning_aujourd_hui,
            'recent_notes' => $recent_notes,
        ];
        
        return view('dashboard.professeur', compact('data'));
    }

    /**
     * Dashboard Stagiaire
     */
    public function stagiaireDashboard()
    {
        // Vérification de sécurité
        if (!Auth::user()->isStagiaire()) {
            abort(403, 'Accès réservé aux stagiaires');
        }

        $user = Auth::user();
        $stagiaire = Stagiaire::where('user_id', $user->id)->firstOrFail();

        // Statistiques
        $moyenne_generale = $stagiaire->notes()->avg('note') ?? 0;
        $total_notes = $stagiaire->notes()->count();
        $total_absences = $stagiaire->absences()->count();
        $absences_injustifiees = $stagiaire->absences()->where('justifiee', false)->count();

        // Dernières notes
        $recent_notes = $stagiaire->notes()
            ->with('matiere')
            ->latest()
            ->take(5)
            ->get();

        $data = [
            'stagiaire' => $stagiaire,
            'moyenne_generale' => $moyenne_generale,
            'total_notes' => $total_notes,
            'total_absences' => $total_absences,
            'absences_injustifiees' => $absences_injustifiees,
            'recent_notes' => $recent_notes,
        ];

        // L'espace stagiaire est géré par StagiaireSpaceController
        return redirect()->route('stagiaire.dashboard');
    }
}
