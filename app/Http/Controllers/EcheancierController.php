<?php

namespace App\Http\Controllers;

use App\Models\Echeancier;
use App\Models\Stagiaire;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // ✅ Import Carbon

class EcheancierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // ✅ Accès admin ET comptable uniquement
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->hasFinancialAccess()) {
                abort(403, 'Accès réservé aux comptables et administrateurs');
            }
            return $next($request);
        });
    }

    /**
     * Liste des échéanciers
     */
    public function index(Request $request)
    {
        $query = Echeancier::with(['stagiaire.filiere', 'anneeScolaire']);

        if ($request->filled('stagiaire_id')) {
            $query->where('stagiaire_id', $request->stagiaire_id);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('en_retard') && $request->boolean('en_retard')) {
            $query->enRetard();
        }

        $echeanciers = $query->orderBy('date_echeance', 'desc')->paginate(20);

        $stats = [
            'total_impayes' => Echeancier::impayes()->sum('montant_restant'),
            'en_retard'     => Echeancier::enRetard()->count(),
            'a_venir'       => Echeancier::aVenir()->count(),
        ];

        return view('echeanciers.index', compact('echeanciers', 'stats'));
    }

    /**
     * Formulaire de création d'un échéancier
     */
    public function create(Request $request)
    {
        $stagiaireId = $request->input('stagiaire_id');
        $stagiaire   = $stagiaireId ? Stagiaire::findOrFail($stagiaireId) : null;

        $stagiaires = Stagiaire::actifs()->with('filiere')->orderBy('nom')->get();
        $annees     = AnneeScolaire::orderBy('debut', 'desc')->get();

        return view('echeanciers.create', compact('stagiaires', 'stagiaire', 'annees'));
    }

    /**
     * Enregistrer un échéancier simple
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'stagiaire_id'       => 'required|exists:stagiaires,id',
            'annee_scolaire_id'  => 'required|exists:annee_scolaires,id',
            'titre'              => 'required|string|max:255',
            'montant'            => 'required|numeric|min:1',
            'date_echeance'      => 'required|date',
        ]);

        $echeancier = Echeancier::create([
            'stagiaire_id'      => $validated['stagiaire_id'],
            'annee_scolaire_id' => $validated['annee_scolaire_id'],
            'titre'             => $validated['titre'],
            'montant'           => $validated['montant'],
            'date_echeance'     => $validated['date_echeance'],
            'montant_paye'      => 0,
            'montant_restant'   => $validated['montant'],
            'statut'            => 'impaye',
        ]);

        // Mise à jour du solde du stagiaire
        Stagiaire::find($validated['stagiaire_id'])?->updateSoldePaiement();

        return redirect()->route('echeanciers.index')
            ->with('success', 'Échéancier créé avec succès.');
    }

    /**
     * Générer automatiquement des échéanciers mensuels
     */
    public function genererMensuels(Request $request)
    {
        $validated = $request->validate([
            'stagiaire_id'      => 'required|exists:stagiaires,id',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'montant_mensuel'   => 'required|numeric|min:1',
            'date_debut'        => 'required|date',
            'nombre_mois'       => 'required|integer|min:1|max:12',
        ]);

        DB::beginTransaction();
        try {
            $stagiaire = Stagiaire::findOrFail($validated['stagiaire_id']);
            $dateDebut = Carbon::parse($validated['date_debut']);

            for ($i = 0; $i < $validated['nombre_mois']; $i++) {
                $dateEcheance = $dateDebut->copy()->addMonths($i);

                Echeancier::create([
                    'stagiaire_id'      => $validated['stagiaire_id'],
                    'annee_scolaire_id' => $validated['annee_scolaire_id'],
                    'titre'             => 'Mensualité ' . $dateEcheance->format('F Y'),
                    'montant'           => $validated['montant_mensuel'],
                    'date_echeance'     => $dateEcheance,
                    'montant_paye'      => 0,
                    'montant_restant'   => $validated['montant_mensuel'],
                    'statut'            => 'impaye',
                ]);
            }

            $stagiaire->updateSoldePaiement();

            DB::commit();

            return redirect()
                ->route('echeanciers.index', ['stagiaire_id' => $validated['stagiaire_id']])
                ->with('success', "{$validated['nombre_mois']} échéanciers créés avec succès.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Afficher un échéancier
     */
    public function show(Echeancier $echeancier)
    {
        $echeancier->load(['stagiaire.filiere', 'anneeScolaire', 'paiements.user']);

        return view('echeanciers.show', compact('echeancier'));
    }

    /**
     * Formulaire d'édition d'un échéancier
     */
    public function edit(Echeancier $echeancier)
    {
        $stagiaires = Stagiaire::actifs()->with('filiere')->orderBy('nom')->get();
        $annees     = AnneeScolaire::orderBy('debut', 'desc')->get();

        return view('echeanciers.edit', compact('echeancier', 'stagiaires', 'annees'));
    }

    /**
     * Mettre à jour un échéancier
     */
    public function update(Request $request, Echeancier $echeancier)
    {
        if ($echeancier->statut === 'paye') {
            return back()->with('error', 'Impossible de modifier un échéancier déjà payé.');
        }

        $validated = $request->validate([
            'titre'         => 'required|string|max:255',
            'montant'       => 'required|numeric|min:' . $echeancier->montant_paye,
            'date_echeance' => 'required|date',
        ]);

        $echeancier->update([
            'titre'           => $validated['titre'],
            'montant'         => $validated['montant'],
            'date_echeance'   => $validated['date_echeance'],
            'montant_restant' => $validated['montant'] - $echeancier->montant_paye,
        ]);

        $echeancier->stagiaire?->updateSoldePaiement();

        return redirect()->route('echeanciers.index')
            ->with('success', 'Échéancier mis à jour avec succès.');
    }

    /**
     * Supprimer un échéancier
     */
    public function destroy(Echeancier $echeancier)
    {
        if ($echeancier->montant_paye > 0) {
            return back()->with('error', 'Impossible de supprimer un échéancier avec des paiements.');
        }

        $stagiaireId = $echeancier->stagiaire_id;
        $echeancier->delete();

        Stagiaire::find($stagiaireId)?->updateSoldePaiement();

        return redirect()->route('echeanciers.index')
            ->with('success', 'Échéancier supprimé avec succès.');
    }

    /**
     * Vue stagiaire : Mes échéanciers
     */
    public function mesEcheanciers()
    {
        $stagiaire = auth()->user()->stagiaire;

        if (!$stagiaire) {
            abort(403, 'Aucun profil stagiaire associé');
        }

        $echeanciers = $stagiaire->echeanciers()
            ->with('anneeScolaire')
            ->orderBy('date_echeance', 'desc')
            ->get();

        $stats = [
            'total_a_payer'  => $echeanciers->sum('montant'),
            'total_paye'     => $echeanciers->sum('montant_paye'),
            'total_restant'  => $echeanciers->sum('montant_restant'),
            'en_retard'      => $echeanciers->where('statut', 'en_retard')->count(),
            'payes'          => $echeanciers->where('statut', 'paye')->count(),
        ];

        return view('stagiaire.echeanciers', compact('echeanciers', 'stats'));
    }

    /**
     * Imprimer/Télécharger un échéancier en PDF
     */
    public function imprimer(Echeancier $echeancier)
    {
        $echeancier->load([
            'stagiaire.filiere',
            'stagiaire.classe',
            'anneeScolaire',
            'paiements.user'
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('echeanciers.print', [
            'echeancier' => $echeancier
        ]);

        return $pdf->download('echeancier_' . $echeancier->id . '.pdf');
    }

    /**
     * Vérifier et mettre à jour les retards
     */
    public function verifierRetards()
    {
        $echeanciers = Echeancier::whereIn('statut', ['impaye', 'paye_partiel'])
            ->where('date_echeance', '<', now())
            ->get();

        $count = 0;
        foreach ($echeanciers as $echeancier) {
            if ($echeancier->statut !== 'en_retard') {
                $echeancier->update(['statut' => 'en_retard']);
                $count++;
            }
        }

        return back()->with('success', "{$count} échéanciers mis à jour.");
    }
}
