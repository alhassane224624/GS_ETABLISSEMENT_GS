<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Echeancier;
use App\Models\Stagiaire;
use App\Models\Remise;
use App\Models\Filiere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ComptableController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->hasFinancialAccess()) {
                abort(403, 'Accès non autorisé');
            }
            return $next($request);
        });
    }

    /**
     * Dashboard comptable
     */
    public function dashboard(Request $request)
    {
        $dateDebut = $request->input('date_debut', now()->startOfMonth());
        $dateFin = $request->input('date_fin', now()->endOfMonth());

        // KPIs principaux
        $stats = [
            // Paiements du jour
            'paiements_jour' => Paiement::whereDate('date_paiement', today())
                ->where('statut', 'valide')
                ->sum('montant'),
            
            // Paiements du mois
            'paiements_mois' => Paiement::whereMonth('date_paiement', now()->month)
                ->whereYear('date_paiement', now()->year)
                ->where('statut', 'valide')
                ->sum('montant'),
            
            // En attente de validation
            'en_attente' => Paiement::where('statut', 'en_attente')->count(),
            'montant_attente' => Paiement::where('statut', 'en_attente')->sum('montant'),
            
            // Échéances
            'echeances_retard' => Echeancier::where('statut', 'en_retard')->count(),
            'montant_retard' => Echeancier::where('statut', 'en_retard')->sum('montant_restant'),
            
            // Échéances à venir (7 jours)
            'echeances_prochaines' => Echeancier::where('statut', 'impaye')
                ->whereBetween('date_echeance', [now(), now()->addDays(7)])
                ->count(),
            
            // Total impayés
            'total_impayes' => Echeancier::whereIn('statut', ['impaye', 'paye_partiel', 'en_retard'])
                ->sum('montant_restant'),
            
            // Remises actives
            'remises_actives' => Remise::where('is_active', true)->count(),
        ];

        // Paiements en attente (récents)
        $paiementsEnAttente = Paiement::with(['stagiaire.filiere', 'user'])
            ->where('statut', 'en_attente')
            ->latest('created_at')
            ->limit(10)
            ->get();

        // Derniers paiements validés
        $derniersPaiements = Paiement::with(['stagiaire.filiere', 'validateur'])
            ->where('statut', 'valide')
            ->latest('valide_at')
            ->limit(10)
            ->get();

        // Top 10 retards
        $topRetards = Echeancier::with(['stagiaire.filiere', 'stagiaire.classe'])
            ->where('statut', 'en_retard')
            ->orderBy('date_echeance', 'asc')
            ->limit(10)
            ->get();

        // Échéances à venir (7 jours)
        $echeancesProchaines = Echeancier::with(['stagiaire.filiere'])
            ->where('statut', 'impaye')
            ->whereBetween('date_echeance', [now(), now()->addDays(7)])
            ->orderBy('date_echeance', 'asc')
            ->limit(10)
            ->get();

        // Évolution des paiements (30 derniers jours)
        $evolutionLabels = [];
        $evolutionData = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $evolutionLabels[] = $date->format('d/m');
            $evolutionData[] = Paiement::where('statut', 'valide')
                ->whereDate('date_paiement', $date)
                ->sum('montant');
        }

        // Répartition par méthode (30 derniers jours)
        $parMethode = Paiement::where('statut', 'valide')
            ->where('date_paiement', '>=', now()->subDays(30))
            ->select('methode_paiement', DB::raw('SUM(montant) as total'))
            ->groupBy('methode_paiement')
            ->pluck('total', 'methode_paiement')
            ->toArray();

        // Répartition par filière
        $parFiliere = Paiement::with('stagiaire.filiere')
            ->where('statut', 'valide')
            ->where('date_paiement', '>=', now()->subDays(30))
            ->get()
            ->groupBy('stagiaire.filiere.nom')
            ->map(fn($group) => $group->sum('montant'))
            ->toArray();

        return view('comptable.dashboard', compact(
            'stats',
            'paiementsEnAttente',
            'derniersPaiements',
            'topRetards',
            'echeancesProchaines',
            'evolutionLabels',
            'evolutionData',
            'parMethode',
            'parFiliere'
        ));
    }

    /**
     * Liste des stagiaires avec soldes
     */
    public function stagiaires(Request $request)
    {
        $query = Stagiaire::with(['filiere', 'classe', 'niveau'])
            ->where('is_active', true);

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        if ($request->filled('filiere_id')) {
            $query->where('filiere_id', $request->filiere_id);
        }

        // Filtrer par statut de paiement
        if ($request->filled('statut_paiement')) {
            switch ($request->statut_paiement) {
                case 'en_retard':
                    $query->whereHas('echeanciers', function($q) {
                        $q->where('statut', 'en_retard');
                    });
                    break;
                case 'a_jour':
                    $query->where('solde_restant', '<=', 0);
                    break;
                case 'paiement_partiel':
                    $query->where('solde_restant', '>', 0)
                          ->where('total_paye', '>', 0);
                    break;
            }
        }

        $stagiaires = $query->paginate(20);
        $filieres = Filiere::orderBy('nom')->get();

        return view('comptable.stagiaires', compact('stagiaires', 'filieres'));
    }

    /**
     * Détail financier d'un stagiaire
     */
    public function stagiaireDetail(Stagiaire $stagiaire)
    {
        $stagiaire->load([
            'filiere',
            'classe',
            'niveau',
            'echeanciers' => fn($q) => $q->orderBy('date_echeance'),
            'paiements' => fn($q) => $q->latest('date_paiement'),
            'remises' => fn($q) => $q->where('is_active', true)
        ]);

        $stats = [
            'total_a_payer' => $stagiaire->total_a_payer,
            'total_paye' => $stagiaire->total_paye,
            'solde_restant' => $stagiaire->solde_restant,
            'nb_echeances' => $stagiaire->echeanciers->count(),
            'nb_retards' => $stagiaire->echeanciers->where('statut', 'en_retard')->count(),
            'nb_paiements' => $stagiaire->paiements->where('statut', 'valide')->count(),
            'remises_total' => $stagiaire->remises->sum('valeur'),
        ];

        return view('comptable.stagiaire-detail', compact('stagiaire', 'stats'));
    }

    /**
     * Rapports et statistiques
     */
    public function rapports(Request $request)
    {
        $dateDebut = $request->input('date_debut', now()->startOfMonth());
        $dateFin = $request->input('date_fin', now()->endOfMonth());
        $filiereId = $request->input('filiere_id');

        // Statistiques globales
        $stats = $this->calculerStatistiques($dateDebut, $dateFin, $filiereId);

        // Top 10 meilleurs payeurs
        $meilleursPayeurs = Stagiaire::where('is_active', true)
            ->where('total_paye', '>', 0)
            ->orderBy('total_paye', 'desc')
            ->limit(10)
            ->get();

        // Top 10 plus gros impayés
        $plusGrosImpayes = Stagiaire::where('is_active', true)
            ->where('solde_restant', '>', 0)
            ->orderBy('solde_restant', 'desc')
            ->limit(10)
            ->get();

        $filieres = Filiere::orderBy('nom')->get();

        return view('comptable.rapports', compact(
            'stats',
            'meilleursPayeurs',
            'plusGrosImpayes',
            'filieres',
            'dateDebut',
            'dateFin'
        ));
    }

    /**
     * Calcule les statistiques financières
     */
    private function calculerStatistiques($dateDebut, $dateFin, $filiereId = null)
    {
        // Total encaissé
        $totalEncaisse = Paiement::where('statut', 'valide')
            ->whereBetween('date_paiement', [$dateDebut, $dateFin])
            ->when($filiereId, function($q) use ($filiereId) {
                $q->whereHas('stagiaire', fn($sq) => $sq->where('filiere_id', $filiereId));
            })
            ->sum('montant');

        // Montant attendu
        $totalAttendu = Echeancier::whereBetween('date_echeance', [$dateDebut, $dateFin])
            ->when($filiereId, function($q) use ($filiereId) {
                $q->whereHas('stagiaire', fn($sq) => $sq->where('filiere_id', $filiereId));
            })
            ->sum('montant');

        // Impayés
        $totalImpayes = Echeancier::whereIn('statut', ['impaye', 'paye_partiel', 'en_retard'])
            ->when($filiereId, function($q) use ($filiereId) {
                $q->whereHas('stagiaire', fn($sq) => $sq->where('filiere_id', $filiereId));
            })
            ->sum('montant_restant');

        // Taux de recouvrement
        $tauxRecouvrement = $totalAttendu > 0 
            ? ($totalEncaisse / $totalAttendu) * 100 
            : 0;

        // Nombre de paiements
        $nbPaiements = Paiement::where('statut', 'valide')
            ->whereBetween('date_paiement', [$dateDebut, $dateFin])
            ->when($filiereId, function($q) use ($filiereId) {
                $q->whereHas('stagiaire', fn($sq) => $sq->where('filiere_id', $filiereId));
            })
            ->count();

        return [
            'total_encaisse' => $totalEncaisse,
            'total_attendu' => $totalAttendu,
            'total_impayes' => $totalImpayes,
            'taux_recouvrement' => round($tauxRecouvrement, 1),
            'nb_paiements' => $nbPaiements,
        ];
    }
}