<?php

namespace App\Http\Controllers;

use App\Models\Stagiaire;
use App\Models\Note;
use App\Models\Bulletin;
use App\Models\Planning;
use App\Models\Absence;
use App\Models\Periode;
use App\Models\Matiere;
use App\Models\Message;
use App\Models\Paiement;
use App\Models\Echeancier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class StagiaireSpaceController extends Controller
{
    public function __construct()
    {
        $this->middleware('stagiaire');
    }

    // =========================================================================
    // 🏠 DASHBOARD
    // =========================================================================
    public function dashboard()
    {
        try {
            $user = Auth::user();

            $stagiaire = Stagiaire::where('user_id', $user->id)->firstOrFail();

            $moyenne_generale = $stagiaire->notes()->avg('note') ?? 0;
            $total_notes = $stagiaire->notes()->count();
            $total_absences = $stagiaire->absences()->count();
            $absences_injustifiees = $stagiaire->absences()->where('justifiee', false)->count();
            $messages_non_lus = Message::where('receiver_id', $user->id)->where('is_read', false)->count();

            $bulletin_valide = $stagiaire->bulletins()
                ->whereNotNull('validated_at')
                ->latest('validated_at')
                ->first();

            // Nouveaux indicateurs financiers
            $total_a_payer = $stagiaire->total_a_payer ?? 0;
            $total_paye = $stagiaire->total_paye ?? 0;
            $solde_restant = $stagiaire->solde_restant ?? 0;
            $statut_paiement = $stagiaire->statut_paiement ?? 'en_attente';

            return view('stagiaires.dashboard', compact(
                'stagiaire',
                'moyenne_generale',
                'total_notes',
                'total_absences',
                'absences_injustifiees',
                'messages_non_lus',
                'bulletin_valide',
                'total_a_payer',
                'total_paye',
                'solde_restant',
                'statut_paiement'
            ));
        } catch (\Exception $e) {
            Log::error('Erreur dashboard stagiaire: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Une erreur est survenue.');
        }
    }

    // =========================================================================
    // 🧾 NOTES
    // =========================================================================
    public function mesNotes(Request $request)
    {
        $user = Auth::user();
        $stagiaire = Stagiaire::where('user_id', $user->id)->firstOrFail();

        $periodeId = $request->input('periode_id');
        $matiereId = $request->input('matiere_id');

        $query = $stagiaire->notes()->with(['matiere', 'creator', 'periode']);

        if ($periodeId) $query->where('periode_id', $periodeId);
        if ($matiereId) $query->where('matiere_id', $matiereId);

        $notes = $query->latest()->paginate(20);
        $moyenneGenerale = $stagiaire->notes()->avg('note') ?? 0;

        $notesParMatiere = $stagiaire->notes()
            ->with('matiere')
            ->get()
            ->groupBy('matiere_id')
            ->map(fn($notes) => [
                'matiere' => $notes->first()->matiere,
                'count' => $notes->count(),
                'moyenne' => $notes->avg('note')
            ]);

        $periodes = Periode::orderBy('debut', 'desc')->get();
        $matieres = Matiere::whereIn('id', $stagiaire->notes->pluck('matiere_id')->unique())->get();

        return view('stagiaires.notes', compact(
            'notes', 'moyenneGenerale', 'notesParMatiere', 'periodes', 'matieres', 'periodeId', 'matiereId'
        ));
    }

    public function telechargerReleve(Request $request)
    {
        $user = Auth::user();
        $stagiaire = Stagiaire::where('user_id', $user->id)->firstOrFail();

        $periodeId = $request->input('periode_id');
        $matiereId = $request->input('matiere_id');

        $query = $stagiaire->notes()->with(['matiere', 'periode']);
        if ($periodeId) $query->where('periode_id', $periodeId);
        if ($matiereId) $query->where('matiere_id', $matiereId);

        $notes = $query->latest()->get();
        $moyenneGenerale = $notes->avg('note') ?? 0;

        $pdf = Pdf::loadView('stagiaires.pdf.releve-notes', compact('stagiaire', 'notes', 'moyenneGenerale'));
        return $pdf->download('releve_notes_' . $stagiaire->matricule . '_' . now()->format('Y-m-d') . '.pdf');
    }

    // =========================================================================
    // 📊 BULLETINS
    // =========================================================================
    public function monBulletin(Request $request)
    {
        $user = Auth::user();
        $stagiaire = Stagiaire::where('user_id', $user->id)->firstOrFail();

        $periodeId = $request->input('periode_id');
        // Seuls les bulletins validés par l'administration sont visibles
        $query = $stagiaire->bulletins()->with('periode')->whereNotNull('validated_at');
        if ($periodeId) $query->where('periode_id', $periodeId);

        $bulletins = $query->latest()->get();
        $periodes = Periode::orderBy('debut', 'desc')->get();

        return view('stagiaires.bulletin', compact('bulletins', 'periodes', 'periodeId'));
    }

    public function telechargerBulletin(Bulletin $bulletin)
    {
        $user = Auth::user();
        $stagiaire = Stagiaire::where('user_id', $user->id)->firstOrFail();

        abort_if((int) $bulletin->stagiaire_id !== (int) $stagiaire->id, 403);
        if (!$bulletin->validated_at) {
            return back()->with('error', 'Ce bulletin n\'est pas encore validé.');
        }

        $bulletin->loadMissing(['stagiaire', 'periode.anneeScolaire', 'classe.filiere']);
        $pdf = Pdf::loadView('bulletins.pdf', compact('bulletin')); // même modèle que l'administration
        return $pdf->download('bulletin_' . $stagiaire->matricule . '_' . $bulletin->periode->nom . '.pdf');
    }

    // =========================================================================
    // 🕓 EMPLOI DU TEMPS
    // =========================================================================
    public function emploiDuTemps(Request $request)
    {
        $user = Auth::user();
        $stagiaire = Stagiaire::where('user_id', $user->id)->firstOrFail();

        if (!$stagiaire->classe_id) {
            return view('stagiaires.emploi-du-temps', [
                'message' => 'Vous n\'êtes pas encore assigné à une classe.'
            ]);
        }

        $date = $request->input('date', now()->format('Y-m-d'));
        $plannings = Planning::where('classe_id', $stagiaire->classe_id)
            ->whereDate('date', $date)
            ->with(['matiere', 'salle', 'professeur'])
            ->orderBy('heure_debut')
            ->get();

        return view('stagiaires.emploi-du-temps', compact('plannings', 'date'));
    }

    // =========================================================================
    // 🚫 ABSENCES
    // =========================================================================
    public function mesAbsences(Request $request)
    {
        $user = Auth::user();
        $stagiaire = Stagiaire::where('user_id', $user->id)->firstOrFail();

        $periodeId = $request->input('periode_id');
        $query = $stagiaire->absences()->with(['creator', 'periode']);
        if ($periodeId) $query->where('periode_id', $periodeId);

        $absences = $query->latest('date')->paginate(20);

        $statistiques = [
            'total' => $stagiaire->absences()->count(),
            'injustifiees' => $stagiaire->absences()->where('justifiee', false)->count(),
            'justifiees' => $stagiaire->absences()->where('justifiee', true)->count(),
        ];

        $periodes = Periode::orderBy('debut', 'desc')->get();

        return view('stagiaires.absences', compact('absences', 'statistiques', 'periodes', 'periodeId'));
    }

    // =========================================================================
    // 👤 PROFIL
    // =========================================================================
    public function monProfil()
    {
        $user = Auth::user();
        $stagiaire = Stagiaire::where('user_id', $user->id)
            ->with(['filiere', 'classe', 'niveau'])
            ->firstOrFail();

        return view('stagiaires.profil', compact('stagiaire'));
    }

    // =========================================================================
    // 💳 PAIEMENTS
    // =========================================================================
    public function mesPaiements()
    {
        $user = Auth::user();
        $stagiaire = Stagiaire::where('user_id', $user->id)->firstOrFail();

        $paiements = Paiement::where('stagiaire_id', $stagiaire->id)
            ->with('echeanciers')
            ->orderByDesc('date_paiement')
            ->paginate(15);

        $statistiques = [
            'total' => $paiements->sum('montant'),
            'valides' => $paiements->where('statut', 'valide')->sum('montant'),
            'attente' => $paiements->where('statut', 'en_attente')->count(),
            'refuses' => $paiements->where('statut', 'refuse')->count(),
        ];

        return view('stagiaires.paiements', compact('paiements', 'stagiaire', 'statistiques'));
    }

    public function telechargerRecu(Paiement $paiement)
    {
        $user = Auth::user();
        $stagiaire = Stagiaire::where('user_id', $user->id)->firstOrFail();

        abort_if((int) $paiement->stagiaire_id !== (int) $stagiaire->id, 403);
        abort_if($paiement->statut !== 'valide', 403, 'Reçu disponible uniquement pour les paiements validés.');

        $paiement->load(['stagiaire.filiere', 'stagiaire.classe', 'echeanciers', 'validateur', 'user']);

        // Même modèle de reçu que côté administration
        $pdf = Pdf::loadView('paiements.recu', compact('paiement'))->setPaper('a4');

        return $pdf->download('recu_' . $paiement->numero_transaction . '.pdf')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    // =========================================================================
    // 📅 ÉCHÉANCIERS
    // =========================================================================
    public function mesEcheanciers()
    {
        $user = Auth::user();
        $stagiaire = Stagiaire::where('user_id', $user->id)->firstOrFail();

        $echeanciers = Echeancier::where('stagiaire_id', $stagiaire->id)
            ->with('paiements')
            ->orderBy('date_echeance', 'asc')
            ->get();

        $stats = [
            'impayes' => $echeanciers->where('statut', 'impaye')->count(),
            'partiels' => $echeanciers->where('statut', 'paye_partiel')->count(),
            'payes' => $echeanciers->where('statut', 'paye')->count(),
            'retards' => $echeanciers->where('statut', 'en_retard')->count(),
        ];

        return view('stagiaires.echeanciers', compact('echeanciers', 'stats', 'stagiaire'));
    }

    /**
     * Cahier de textes de la classe : ce qui a été fait en cours et le travail à faire
     */
    public function cahierDeTextes(Request $request)
    {
        $stagiaire = Stagiaire::where('user_id', Auth::id())->firstOrFail();

        $aFaire = Planning::with(['matiere', 'professeur:id,name'])
            ->where('classe_id', $stagiaire->classe_id)
            ->whereNotNull('devoirs')
            ->whereDate('devoirs_pour', '>=', now()->toDateString())
            ->orderBy('devoirs_pour')
            ->get();

        $seances = Planning::with(['matiere', 'professeur:id,name'])
            ->where('classe_id', $stagiaire->classe_id)
            ->whereNotNull('appel_fait_at')
            ->where(fn ($q) => $q->whereNotNull('contenu_seance')->orWhereNotNull('devoirs'))
            ->when($request->filled('matiere_id'), fn ($q) => $q->where('matiere_id', $request->matiere_id))
            ->orderByDesc('date')->orderByDesc('heure_debut')
            ->paginate(15)
            ->withQueryString();

        $matieres = \App\Models\Matiere::whereIn('id', Planning::where('classe_id', $stagiaire->classe_id)->distinct()->pluck('matiere_id'))
            ->orderBy('nom')->get();

        return view('stagiaires.cahier-de-textes', compact('stagiaire', 'aFaire', 'seances', 'matieres'));
    }
}
