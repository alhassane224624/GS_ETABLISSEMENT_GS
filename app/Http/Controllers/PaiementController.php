<?php

namespace App\Http\Controllers;

use App\Models\Echeancier;
use App\Models\Paiement;
use App\Models\Stagiaire;
use App\Services\PaiementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaiementController extends Controller
{
    public function __construct(private PaiementService $service)
    {
        $this->middleware('auth');

        // Admin et comptable — sauf l'espace stagiaire (ses paiements, ses reçus)
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->hasFinancialAccess()) {
                abort(403, 'Accès réservé aux comptables et administrateurs');
            }
            return $next($request);
        })->except(['mesPaiements', 'telechargerRecu']);
    }

    /**
     * Liste paginée des paiements + filtres
     */
    public function index(Request $request)
    {
        $paiements = Paiement::with(['stagiaire.filiere', 'echeanciers'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->string('search');
                // Groupé : sinon le OR annulerait les autres filtres
                $q->where(function ($qq) use ($s) {
                    $qq->where('numero_transaction', 'like', "%{$s}%")
                       ->orWhere('reference_externe', 'like', "%{$s}%")
                       ->orWhereHas('stagiaire', fn ($sq) => $sq->where('nom', 'like', "%{$s}%")
                            ->orWhere('prenom', 'like', "%{$s}%")
                            ->orWhere('matricule', 'like', "%{$s}%"));
                });
            })
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('type_paiement'), fn ($q) => $q->where('type_paiement', $request->type_paiement))
            ->when($request->filled('methode_paiement'), fn ($q) => $q->where('methode_paiement', $request->methode_paiement))
            ->when($request->filled('date_debut'), fn ($q) => $q->whereDate('date_paiement', '>=', $request->date_debut))
            ->when($request->filled('date_fin'), fn ($q) => $q->whereDate('date_paiement', '<=', $request->date_fin))
            ->latest('date_paiement')->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total_paiements' => Paiement::valides()->sum('montant'),
            'en_attente'      => Paiement::enAttente()->count(),
            'refuses'         => Paiement::refuses()->count(),
        ];

        return view('paiements.index', compact('paiements', 'stats'));
    }

    /**
     * Formulaire d'encaissement
     */
    public function create(Request $request)
    {
        $stagiaire = null;
        $echeances = collect();
        $encaissable = 0;
        $enAttente = collect();

        if ($request->filled('stagiaire_id')) {
            $stagiaire = Stagiaire::with(['filiere', 'classe'])->findOrFail($request->stagiaire_id);
            $echeances = Echeancier::where('stagiaire_id', $stagiaire->id)->ouverts()
                ->orderBy('date_echeance')->get();
            $encaissable = $this->service->montantEncaissable($stagiaire->id);
            $enAttente = $stagiaire->paiements()->enAttente()->latest()->get();
        }

        $stagiaires = Stagiaire::actifs()->orderBy('nom')->orderBy('prenom')->get(['id', 'nom', 'prenom', 'matricule']);

        return view('paiements.create', compact('stagiaire', 'stagiaires', 'echeances', 'encaissable', 'enAttente'));
    }

    /**
     * Enregistrer un paiement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'stagiaire_id'      => ['required', 'exists:stagiaires,id'],
            'montant'           => ['required', 'numeric', 'min:0.01'],
            'methode_paiement'  => ['required', Rule::in(array_keys(Paiement::METHODES))],
            'type_paiement'     => ['nullable', Rule::in(array_keys(Paiement::TYPES))],
            'date_paiement'     => ['required', 'date', 'before_or_equal:today'],
            'reference_externe' => ['nullable', 'required_if:methode_paiement,cheque,virement', 'string', 'max:100'],
            'banque'            => ['nullable', 'string', 'max:100'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'notes_admin'       => ['nullable', 'string', 'max:1000'],
            'justificatif'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'echeanciers'       => ['nullable', 'array'],
            'echeanciers.*'     => ['integer', 'exists:echeanciers,id'],
        ], [
            'reference_externe.required_if' => 'Indiquez le n° de chèque ou la référence du virement.',
            'date_paiement.before_or_equal' => 'La date de paiement ne peut pas être dans le futur.',
        ]);

        $paiement = $this->service->enregistrer($validated, $request->file('justificatif'));

        $message = $paiement->statut === 'valide'
            ? 'Paiement encaissé et validé. Le reçu est disponible.'
            : 'Paiement enregistré en attente : validez-le quand le ' . mb_strtolower($paiement->methode_libelle) . ' est encaissé.';

        return redirect()->route('paiements.show', $paiement)->with('success', $message);
    }

    public function show(Paiement $paiement)
    {
        $paiement->load(['stagiaire.filiere', 'stagiaire.classe', 'user', 'validateur',
            'echeanciers' => fn ($q) => $q->orderBy('date_echeance')]);

        $echeancesCibles = $paiement->echeances_cibles
            ? Echeancier::whereIn('id', $paiement->echeances_cibles)->orderBy('date_echeance')->get()
            : collect();

        return view('paiements.show', compact('paiement', 'echeancesCibles'));
    }

    public function valider(Request $request, Paiement $paiement)
    {
        $request->validate(['notes_admin' => 'nullable|string|max:1000']);

        try {
            $this->service->valider($paiement, $request->input('notes_admin'));
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return back()->with('success', 'Paiement validé et imputé sur les échéances.');
    }

    public function refuser(Request $request, Paiement $paiement)
    {
        $data = $request->validate(['motif_refus' => 'required|string|max:1000']);

        try {
            $this->service->refuser($paiement, $data['motif_refus']);
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return back()->with('success', 'Paiement refusé.');
    }

    /**
     * Annuler un paiement validé (administrateur uniquement)
     */
    public function annuler(Request $request, Paiement $paiement)
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Seul un administrateur peut annuler un paiement validé.');

        $data = $request->validate(['motif_annulation' => 'required|string|max:1000']);

        try {
            $this->service->annuler($paiement, $data['motif_annulation']);
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return back()->with('success', 'Paiement annulé : les échéances concernées sont de nouveau dues.');
    }

    /**
     * Reçu PDF (admin, comptable, ou le stagiaire concerné)
     */
    public function telechargerRecu(Paiement $paiement)
    {
        $user = auth()->user();
        if (!$user->hasFinancialAccess()) {
            $stagiaire = $user->stagiaire;
            abort_if(!$stagiaire || (int) $paiement->stagiaire_id !== (int) $stagiaire->id, 403);
        }

        if ($paiement->statut !== 'valide') {
            return back()->with('error', 'Le reçu n\'est disponible que pour un paiement validé.');
        }

        $paiement->load(['stagiaire.filiere', 'stagiaire.classe', 'validateur', 'user',
            'echeanciers' => fn ($q) => $q->orderBy('date_echeance')]);

        return Pdf::loadView('paiements.recu', ['paiement' => $paiement])
            ->setPaper('a4')
            ->stream('recu_' . $paiement->numero_transaction . '.pdf')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Espace stagiaire : mes paiements
     */
    public function mesPaiements()
    {
        $stagiaire = auth()->user()->stagiaire;
        abort_unless($stagiaire, 403, 'Aucun profil stagiaire associé');

        $stagiaire->updateSoldePaiement();

        $paiements = $stagiaire->paiements()->with('echeanciers')
            ->latest('date_paiement')->latest('id')->paginate(15);

        $stats = [
            'total_a_payer' => (float) $stagiaire->total_a_payer,
            'total_paye'    => (float) $stagiaire->total_paye,
            'solde_restant' => (float) $stagiaire->solde_restant,
            'en_attente'    => $stagiaire->paiements()->enAttente()->sum('montant'),
        ];

        return view('stagiaires.paiements', compact('stagiaire', 'paiements', 'stats'));
    }

    /**
     * Historique complet d'un stagiaire
     */
    public function historique(Stagiaire $stagiaire)
    {
        $stagiaire->updateSoldePaiement();

        $paiements = $stagiaire->paiements()->with('echeanciers')
            ->latest('date_paiement')->latest('id')->paginate(20);

        return view('paiements.historique', compact('stagiaire', 'paiements'));
    }
}
