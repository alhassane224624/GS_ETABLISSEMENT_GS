<?php

namespace App\Http\Controllers;

use App\Models\Stagiaire;
use App\Models\Note;
use App\Models\Absence;
use App\Models\Planning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StagiairesExport;

/**
 * Espace professeur : tableau de bord et liste de ses stagiaires
 */
class ProfesseurController extends Controller
{
    public function __construct()
    {
        $this->middleware('professeur');
    }

    /**
     * Dashboard du professeur
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Filières du prof + nb stagiaires actifs
        $filieres = $user->filieresActives()
            ->withCount(['stagiaires' => function ($query) {
                $query->where('statut', 'actif')->where('is_active', true);
            }])
            ->get();

        $filiereIds = $filieres->pluck('id')->toArray();

        // ✅ CORRECTION: Ajout de la variable $matieres
        $matieres = $user->matieresEnseignees()
            ->wherePivot('is_active', true)
            ->get();

        // Stats
        $totalStagiaires = Stagiaire::whereIn('filiere_id', $filiereIds)
            ->where('statut', 'actif')
            ->where('is_active', true)
            ->count();

        $totalNotes = Note::where('created_by', $user->id)->count();

        $totalAbsences = Absence::where('created_by', $user->id)
            ->whereDate('date', '>=', now()->subDays(30))
            ->count();

        // Planning du jour
        $planningAujourdhui = Planning::where('professeur_id', $user->id)
            ->whereDate('date', now())
            ->with(['salle', 'matiere', 'classe.niveau', 'classe.filiere'])
            ->orderBy('heure_debut')
            ->get();

        // Dernières notes
        $recentNotes = Note::with(['stagiaire:id,nom,prenom', 'matiere:id,nom'])
            ->where('created_by', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        $data = [
            'filieres'             => $filieres,
            'matieres'             => $matieres, // ✅ AJOUTÉ
            'total_stagiaires'     => $totalStagiaires,
            'total_notes'          => $totalNotes,
            'total_absences'       => $totalAbsences,
            'planning_aujourd_hui' => $planningAujourdhui,
            'recent_notes'         => $recentNotes,
        ];

        return view('professeur.dashboard', compact('data'));
    }

    /**
     * Liste des stagiaires du professeur
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $search   = $request->input('search');
        $filiereId = $request->input('filiere_id');

        // Filtrer par filières du professeur
        $filiereIds = $user->filieresActives->pluck('id')->toArray();

        if (empty($filiereIds)) {
            // Paginator vide correct (éviter collect()->paginate())
            $empty = new LengthAwarePaginator([], 0, 15);
            $filieres = collect();

            return view('professeur.stagiaires', [
                'stagiaires' => $empty,
                'filieres'   => $filieres
            ])->with('error', 'Aucune filière ne vous est assignée. Contactez l\'administrateur.');
        }

        $query = Stagiaire::with(['filiere:id,nom', 'classe:id,nom', 'niveau:id,nom'])
            ->whereIn('filiere_id', $filiereIds)
            ->where('statut', 'actif')
            ->where('is_active', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        if ($filiereId) {
            $query->where('filiere_id', $filiereId);
        }

        $stagiaires = $query->paginate(15);
        $filieres = $user->filieresActives;

        return view('professeur.stagiaires', compact('stagiaires', 'filieres'));
    }

    /**
     * Export des stagiaires en PDF
     */
    public function exportStagiairesPdf()
    {
        $user = Auth::user();

        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', '120');

        $filiereIds = $user->filieresActives->pluck('id')->toArray();

        $stagiaires = Stagiaire::select(['id','nom','prenom','matricule','filiere_id','classe_id','niveau_id'])
            ->with([
                'filiere:id,nom',
                'classe:id,nom',
                'niveau:id,nom',
            ])
            ->whereIn('filiere_id', $filiereIds)
            ->where('statut', 'actif')
            ->where('is_active', true)
            ->limit(800)
            ->get();

        try {
            $pdf = Pdf::loadView('professeur.exports.stagiaires-pdf', [
                    'stagiaires' => $stagiaires,
                    'user'       => $user,
                ])
                ->setPaper('a4', 'portrait');

            return $pdf->download('stagiaires_' . now()->format('Y-m-d_H-i') . '.pdf');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur lors de la génération du PDF (stagiaires) : ' . $e->getMessage());
        }
    }

    /**
     * Export des stagiaires en Excel
     */
    public function exportStagiairesExcel()
    {
        $user = Auth::user();

        return Excel::download(
            new StagiairesExport($user->id),
            'stagiaires_' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
