<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Stagiaire;
use App\Models\Echeancier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

// Notifications
use App\Notifications\PaiementRecuNotification;
use App\Notifications\PaiementValideNotification;
use App\Notifications\PaiementRefuseNotification;

class PaiementController extends Controller
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
     * Liste paginée des paiements + filtres
     */
    public function index(Request $request)
    {
        $q = Paiement::with(['stagiaire.filiere', 'echeanciers'])
            ->when($request->filled('search'), function ($qq) use ($request) {
                $s = $request->string('search');
                $qq->where('numero_transaction', 'like', "%{$s}%")
                   ->orWhereHas('stagiaire', fn ($sq) =>
                        $sq->where('nom', 'like', "%{$s}%")
                           ->orWhere('prenom', 'like', "%{$s}%")
                           ->orWhere('matricule', 'like', "%{$s}%")
                    );
            })
            ->when($request->filled('statut'), fn ($qq) => $qq->where('statut', $request->statut))
            ->when($request->filled('type_paiement'), fn ($qq) => $qq->where('type_paiement', $request->type_paiement))
            ->when($request->filled('methode_paiement'), fn ($qq) => $qq->where('methode_paiement', $request->methode_paiement))
            ->when($request->filled('date_debut'), fn ($qq) => $qq->whereDate('date_paiement', '>=', $request->date_debut))
            ->when($request->filled('date_fin'), fn ($qq) => $qq->whereDate('date_paiement', '<=', $request->date_fin))
            ->latest('date_paiement');

        $paiements = $q->paginate(20);

        $stats = [
            'total_paiements' => Paiement::where('statut', 'valide')->sum('montant'),
            'en_attente'      => Paiement::where('statut', 'en_attente')->count(),
            'refuses'         => Paiement::where('statut', 'refuse')->count(),
        ];

        return view('paiements.index', [
            'paiements' => $paiements,
            'stats' => $stats,
        ]);
    }

    /**
     * Formulaire de création
     */
    public function create(Request $request)
    {
        $stagiaire = $request->filled('stagiaire_id')
            ? Stagiaire::with('echeanciers')->findOrFail($request->stagiaire_id)
            : null;

        $stagiaires = Stagiaire::actifs()->with('filiere')->orderBy('nom')->get();

        return view('paiements.create', compact('stagiaire', 'stagiaires'));
    }

    /**
     * Enregistrer un paiement (statut = en_attente)
     * + affectation FIFO sur les échéances impayées
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'stagiaire_id'     => ['required', 'exists:stagiaires,id'],
            'montant'          => ['required', 'numeric', 'min:0.01'],
            'type_paiement'    => ['required', 'string', 'in:inscription,mensualite,examen,autre'],
            'methode_paiement' => ['required', 'string', 'in:especes,virement,cheque,carte,mobile_money'],
            'date_paiement'    => ['required', 'date'],
            'description'      => ['nullable', 'string', 'max:1000'],
            'notes_admin'      => ['nullable', 'string', 'max:1000'],
            'justificatif'     => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'echeanciers'      => ['nullable', 'array'],
            'echeanciers.*'    => ['exists:echeanciers,id'],
        ]);

        // Refus de sur-paiement
        $totalRestant = $this->getTotalRestantStagiaire($validated['stagiaire_id']);
        if ((float)$validated['montant'] > (float)$totalRestant) {
            return back()
                ->withErrors(['montant' => "Le montant dépasse le total restant du stagiaire (" . number_format($totalRestant, 2) . " DH)."])
                ->withInput();
        }

        $paiement = null;

        DB::transaction(function () use ($request, $validated, &$paiement) {
            // Création du paiement
            $paiement = Paiement::create([
                'stagiaire_id'      => $validated['stagiaire_id'],
                'user_id'           => auth()->id(),
                'montant'           => $validated['montant'],
                'type_paiement'     => $validated['type_paiement'],
                'methode_paiement'  => $validated['methode_paiement'],
                'statut'            => $validated['methode_paiement'] === 'especes' ? 'valide' : 'en_attente',
                'date_paiement'     => $validated['date_paiement'],
                'description'       => $validated['description'] ?? null,
                'notes_admin'       => $validated['notes_admin'] ?? null,
                'valide_at'         => $validated['methode_paiement'] === 'especes' ? now() : null,
                'valide_by'         => $validated['methode_paiement'] === 'especes' ? auth()->id() : null,
            ]);

            // Stockage du justificatif (public pour téléchargement)
            if ($request->hasFile('justificatif')) {
                $path = $request->file('justificatif')
                    ->store('justificatifs/' . $paiement->stagiaire_id, 'public');
                $paiement->update(['justificatif_path' => $path]);
            }

            // Affectation FIFO ou manuelle
            if (!empty($validated['echeanciers'])) {
                $this->affecterPaiementManuel($paiement, $validated['echeanciers']);
            } else {
                $this->affecterPaiementFIFO($paiement);
            }

            // Si paiement en espèces, validation automatique
            if ($paiement->statut === 'valide') {
                $paiement->genererRecu();
                optional($paiement->stagiaire)->updateSoldePaiement();
                $this->notifyStagiaire($paiement, new PaiementValideNotification($paiement));
            } else {
                // Notification "reçu" (en attente de validation)
                $this->notifyStagiaire($paiement, new PaiementRecuNotification($paiement));
            }
        });

        return redirect()->route('paiements.show', $paiement)
            ->with('success', 'Paiement enregistré avec succès.');
    }

    /**
     * Détail d'un paiement
     */
    public function show(Paiement $paiement)
    {
        $paiement->load(['stagiaire.filiere', 'echeanciers' => fn($q) => $q->orderBy('date_echeance')]);
        return view('paiements.show', compact('paiement'));
    }

    /**
     * Valider un paiement
     */
    public function valider(Request $request, Paiement $paiement)
    {
        if ($paiement->statut === 'valide') {
            return back()->with('info', 'Ce paiement est déjà validé.');
        }

        DB::transaction(function () use ($request, $paiement) {
            $paiement->update([
                'statut'     => 'valide',
                'valide_at'  => now(),
                'valide_by'  => auth()->id(),
                'notes_admin' => $request->input('notes_admin', $paiement->notes_admin),
            ]);

            // Générer le reçu
            $paiement->genererRecu();

            // Recalcule les agrégats du stagiaire
            optional($paiement->stagiaire)->updateSoldePaiement();

            // Notification de validation
            $this->notifyStagiaire($paiement, new PaiementValideNotification($paiement));
        });

        return back()->with('success', 'Paiement validé avec succès.');
    }

    /**
     * Refuser un paiement
     */
    public function refuser(Request $request, Paiement $paiement)
    {
        $data = $request->validate([
            'motif_refus' => ['required', 'string', 'max:1000'],
        ]);

        if ($paiement->statut === 'refuse') {
            return back()->with('info', 'Ce paiement est déjà refusé.');
        }

        DB::transaction(function () use ($paiement, $data) {
            $paiement->update([
                'statut'      => 'refuse',
                'notes_admin' => $data['motif_refus'],
            ]);

            $this->notifyStagiaire($paiement, new PaiementRefuseNotification($paiement, $data['motif_refus']));
        });

        return back()->with('success', 'Paiement refusé.');
    }

    /**
     * Reçu PDF
     */
    public function telechargerRecu(Paiement $paiement)
    {
        if ($paiement->statut !== 'valide') {
            return back()->with('error', 'Le reçu n\'est disponible que pour les paiements validés.');
        }

        $paiement->load(['stagiaire.filiere', 'stagiaire.classe', 'echeanciers' => fn($q) => $q->orderBy('date_echeance')]);

        $pdf = Pdf::loadView('paiements.recu', [
            'paiement' => $paiement,
        ])->setPaper('a4');

        return $pdf->stream('recu_' . $paiement->numero_transaction . '.pdf');
    }

    /**
     * Vue stagiaire : Mes paiements
     */
    public function mesPaiements()
    {
        $stagiaire = auth()->user()->stagiaire;
        
        if (!$stagiaire) {
            abort(403, 'Aucun profil stagiaire associé');
        }
        
        $paiements = $stagiaire->paiements()
            ->with('echeanciers')
            ->latest('date_paiement')
            ->paginate(15);
        
        $stats = [
            'total_paye' => $stagiaire->total_paye,
            'solde_restant' => $stagiaire->solde_restant,
            'en_attente' => $stagiaire->paiements()->where('statut', 'en_attente')->count(),
        ];
        
        return view('stagiaire.paiements', compact('paiements', 'stats'));
    }

    /**
     * Historique des paiements d'un stagiaire (admin/comptable)
     */
    public function historique(Stagiaire $stagiaire)
    {
        $paiements = $stagiaire->paiements()
            ->with('echeanciers')
            ->latest('date_paiement')
            ->paginate(20);

        return view('paiements.historique', compact('stagiaire', 'paiements'));
    }

    // =========================================================================
    // MÉTHODES PRIVÉES
    // =========================================================================

    /**
     * Montant restant global du stagiaire
     */
    private function getTotalRestantStagiaire(int $stagiaireId): float
    {
        return (float) Echeancier::where('stagiaire_id', $stagiaireId)
            ->whereIn('statut', ['impaye', 'paye_partiel', 'en_retard'])
            ->sum('montant_restant');
    }

    /**
     * Affectation FIFO automatique
     */
    private function affecterPaiementFIFO(Paiement $paiement): void
    {
        $reste = (float) $paiement->montant;

        $echeanciers = Echeancier::where('stagiaire_id', $paiement->stagiaire_id)
            ->whereIn('statut', ['impaye', 'paye_partiel', 'en_retard'])
            ->where('montant_restant', '>', 0)
            ->orderBy('date_echeance', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($echeanciers as $ech) {
            if ($reste <= 0) break;

            $aAffecter = min($reste, $ech->montant_restant);
            if ($aAffecter <= 0) continue;

            $ech->affecterPaiement($paiement, $aAffecter);
            $reste -= $aAffecter;
        }
    }

    /**
     * Affectation manuelle sur échéanciers sélectionnés
     */
    private function affecterPaiementManuel(Paiement $paiement, array $echeancierIds): void
    {
        $reste = (float) $paiement->montant;

        $echeanciers = Echeancier::whereIn('id', $echeancierIds)
            ->where('stagiaire_id', $paiement->stagiaire_id)
            ->whereIn('statut', ['impaye', 'paye_partiel', 'en_retard'])
            ->where('montant_restant', '>', 0)
            ->orderBy('date_echeance', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($echeanciers as $ech) {
            if ($reste <= 0) break;

            $aAffecter = min($reste, $ech->montant_restant);
            if ($aAffecter <= 0) continue;

            $ech->affecterPaiement($paiement, $aAffecter);
            $reste -= $aAffecter;
        }
    }

    /**
     * Notifier le stagiaire
     */
    private function notifyStagiaire(Paiement $paiement, $notification): void
    {
        try {
            $stagiaire = $paiement->stagiaire;
            if ($stagiaire && $stagiaire->user) {
                $stagiaire->user->notify($notification);
            }
        } catch (\Exception $e) {
            // Logger l'erreur sans bloquer le paiement
            \Log::warning('Impossible d\'envoyer la notification email', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}