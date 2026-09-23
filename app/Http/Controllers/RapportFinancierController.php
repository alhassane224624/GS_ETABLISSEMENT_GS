<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Echeancier;
use App\Models\Filiere;
use App\Models\Remise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RapportFinancierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('financial');
    }

    /**
     * Affiche le dashboard financier
     */
    public function index(Request $request)
    {
        $dateDebut = $request->input('date_debut', now()->startOfMonth());
        $dateFin = $request->input('date_fin', now());
        $filiereId = $request->input('filiere_id');

        // KPIs principaux
        $stats = $this->calculerStatistiques($dateDebut, $dateFin, $filiereId);

        // Top 10 retards
        $retards = $this->getTopRetards(10);

        // Derniers paiements
        $derniers_paiements = Paiement::with('stagiaire.filiere')
            ->where('statut', 'valide')
            ->latest('date_paiement')
            ->limit(10)
            ->get();

        // Filières pour le filtre
        $filieres = Filiere::orderBy('nom')->get();

        return view('admin.rapports.financier', compact(
            'stats',
            'retards',
            'derniers_paiements',
            'filieres'
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

        // Montant attendu (échéanciers)
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

        // Nombre de retards
        $nbRetards = Echeancier::where('statut', 'en_retard')
            ->when($filiereId, function($q) use ($filiereId) {
                $q->whereHas('stagiaire', fn($sq) => $sq->where('filiere_id', $filiereId));
            })
            ->count();

        // Remises accordées
        $totalRemises = Remise::where('is_active', true)
            ->whereBetween('date_debut', [$dateDebut, $dateFin])
            ->when($filiereId, function($q) use ($filiereId) {
                $q->whereHas('stagiaire', fn($sq) => $sq->where('filiere_id', $filiereId));
            })
            ->sum(DB::raw('CASE WHEN type = "montant_fixe" THEN valeur ELSE 0 END'));

        $nbRemises = Remise::where('is_active', true)
            ->when($filiereId, function($q) use ($filiereId) {
                $q->whereHas('stagiaire', fn($sq) => $sq->where('filiere_id', $filiereId));
            })
            ->count();

        // Taux de recouvrement
        $tauxRecouvrement = $totalAttendu > 0 
            ? ($totalEncaisse / $totalAttendu) * 100 
            : 0;

        // Évolution par rapport au mois précédent
        $moisPrecedent = Paiement::where('statut', 'valide')
            ->whereBetween('date_paiement', [
                Carbon::parse($dateDebut)->subMonth(),
                Carbon::parse($dateFin)->subMonth()
            ])
            ->sum('montant');

        $evolutionEncaisse = $moisPrecedent > 0
            ? (($totalEncaisse - $moisPrecedent) / $moisPrecedent) * 100
            : 0;

        // Évolution des paiements (30 derniers jours)
        $evolutionLabels = [];
        $evolutionData = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $evolutionLabels[] = $date->format('d/m');
            $evolutionData[] = Paiement::where('statut', 'valide')
                ->whereDate('date_paiement', $date)
                ->when($filiereId, function($q) use ($filiereId) {
                    $q->whereHas('stagiaire', fn($sq) => $sq->where('filiere_id', $filiereId));
                })
                ->sum('montant');
        }

        // Répartition par méthode
        $parMethode = Paiement::where('statut', 'valide')
            ->whereBetween('date_paiement', [$dateDebut, $dateFin])
            ->when($filiereId, function($q) use ($filiereId) {
                $q->whereHas('stagiaire', fn($sq) => $sq->where('filiere_id', $filiereId));
            })
            ->select('methode_paiement', DB::raw('SUM(montant) as total'))
            ->groupBy('methode_paiement')
            ->pluck('total', 'methode_paiement')
            ->toArray();

        return [
            'total_encaisse' => $totalEncaisse,
            'total_attendu' => $totalAttendu,
            'total_impayes' => $totalImpayes,
            'nb_retards' => $nbRetards,
            'total_remises' => $totalRemises,
            'nb_remises' => $nbRemises,
            'taux_recouvrement' => round($tauxRecouvrement, 1),
            'evolution_encaisse' => round($evolutionEncaisse, 1),
            'evolution_labels' => $evolutionLabels,
            'evolution_data' => $evolutionData,
            'par_methode' => $parMethode,
        ];
    }

    /**
     * Récupère les top retards
     */
    private function getTopRetards($limit = 10)
    {
        return Echeancier::with('stagiaire.filiere')
            ->where('statut', 'en_retard')
            ->orderBy('date_echeance', 'asc')
            ->limit($limit)
            ->get();
    }

    public function exporter(Request $request)
    {
        $format = $request->input('format', 'excel');
        $dateDebut = $request->input('date_debut', now()->startOfMonth());
        $dateFin = $request->input('date_fin', now());
        $filiereId = $request->input('filiere_id');

        $stats = $this->calculerStatistiques($dateDebut, $dateFin, $filiereId);
        
        // Données détaillées
        $paiements = Paiement::with('stagiaire.filiere')
            ->where('statut', 'valide')
            ->whereBetween('date_paiement', [$dateDebut, $dateFin])
            ->when($filiereId, function($q) use ($filiereId) {
                $q->whereHas('stagiaire', fn($sq) => $sq->where('filiere_id', $filiereId));
            })
            ->get();

        $echeanciers = Echeancier::with('stagiaire.filiere')
            ->whereBetween('date_echeance', [$dateDebut, $dateFin])
            ->when($filiereId, function($q) use ($filiereId) {
                $q->whereHas('stagiaire', fn($sq) => $sq->where('filiere_id', $filiereId));
            })
            ->get();

        if ($format === 'excel') {
            return $this->exporterExcel($stats, $paiements, $echeanciers, $dateDebut, $dateFin);
        } else {
            return $this->exporterPdf($stats, $paiements, $echeanciers, $dateDebut, $dateFin);
        }
    }

    /**
     * Exporte en Excel
     */
    private function exporterExcel($stats, $paiements, $echeanciers, $dateDebut, $dateFin)
    {
        // TODO: Implémenter avec Maatwebsite Excel
        return response()->json(['message' => 'Export Excel à implémenter']);
    }

    /**
     * Exporte en PDF
     */
    private function exporterPdf($stats, $paiements, $echeanciers, $dateDebut, $dateFin)
    {
        // TODO: Implémenter avec DomPDF
        return response()->json(['message' => 'Export PDF à implémenter']);
    }

    /**
     * API pour les graphiques AJAX
     */
    public function donneesGraphique(Request $request)
    {
        $periode = $request->input('periode', 30);
        $type = $request->input('type', 'evolution');

        if ($type === 'evolution') {
            return response()->json($this->evolutionPaiements($periode));
        } elseif ($type === 'methodes') {
            return response()->json($this->repartitionMethodes($periode));
        } elseif ($type === 'filieres') {
            return response()->json($this->repartitionFilieres($periode));
        }

        return response()->json(['error' => 'Type invalide'], 400);
    }

    private function evolutionPaiements($jours)
    {
        $labels = [];
        $data = [];
        
        for ($i = $jours - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d/m');
            $data[] = Paiement::where('statut', 'valide')
                ->whereDate('date_paiement', $date)
                ->sum('montant');
        }

        return ['labels' => $labels, 'datasets' => [['label' => 'Paiements reçus', 'data' => $data]]];
    }

    private function repartitionMethodes($jours)
    {
        $data = Paiement::where('statut', 'valide')
            ->where('date_paiement', '>=', now()->subDays($jours))
            ->select('methode_paiement', DB::raw('SUM(montant) as total'))
            ->groupBy('methode_paiement')
            ->get();

        return ['labels' => $data->pluck('methode_paiement')->toArray(), 'datasets' => [['data' => $data->pluck('total')->toArray()]]];
    }

    private function repartitionFilieres($jours)
    {
        $data = Paiement::with('stagiaire.filiere')
            ->where('statut', 'valide')
            ->where('date_paiement', '>=', now()->subDays($jours))
            ->get()
            ->groupBy('stagiaire.filiere.nom')
            ->map(fn($group) => $group->sum('montant'));

        return ['labels' => $data->keys()->toArray(), 'datasets' => [['data' => $data->values()->toArray()]]];
    }
}