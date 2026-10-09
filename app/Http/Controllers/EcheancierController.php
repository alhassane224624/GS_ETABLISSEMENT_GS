<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Echeancier;
use App\Models\Stagiaire;
use App\Services\RemiseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EcheancierController extends Controller
{
    private const MOIS = [1 => 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    public function __construct(private RemiseService $remises)
    {
        $this->middleware('auth');

        $this->middleware(function ($request, $next) {
            if (!auth()->user()->hasFinancialAccess()) {
                abort(403, 'Accès réservé aux comptables et administrateurs');
            }
            return $next($request);
        })->except(['mesEcheanciers']);
    }

    public function index(Request $request)
    {
        $query = Echeancier::with(['stagiaire.filiere', 'anneeScolaire'])
            ->when($request->filled('stagiaire_id'), fn ($q) => $q->where('stagiaire_id', $request->stagiaire_id))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->boolean('en_retard'), fn ($q) => $q->enRetard());

        $echeanciers = $query->orderBy('date_echeance', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'total_impayes' => Echeancier::ouverts()->sum('montant_restant'),
            'en_retard'     => Echeancier::enRetard()->count(),
            'a_venir'       => Echeancier::aVenir()->count(),
        ];

        return view('echeanciers.index', compact('echeanciers', 'stats'));
    }

    public function create(Request $request)
    {
        $stagiaire = $request->filled('stagiaire_id') ? Stagiaire::findOrFail($request->stagiaire_id) : null;
        $stagiaires = Stagiaire::actifs()->with('filiere')->orderBy('nom')->get();
        $annees = AnneeScolaire::orderBy('debut', 'desc')->get();

        return view('echeanciers.create', compact('stagiaires', 'stagiaire', 'annees'));
    }

    /**
     * Échéance unique (frais d'inscription, examen, mensualité isolée…)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'stagiaire_id'      => 'required|exists:stagiaires,id',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'type'              => ['nullable', Rule::in(array_keys(Echeancier::TYPES))],
            'titre'             => 'required|string|max:255',
            'montant'           => 'required|numeric|min:1',
            'date_echeance'     => 'required|date',
        ]);

        $stagiaire = Stagiaire::findOrFail($validated['stagiaire_id']);

        DB::transaction(function () use ($validated, $stagiaire) {
            $echeance = Echeancier::create([
                'stagiaire_id'      => $validated['stagiaire_id'],
                'annee_scolaire_id' => $validated['annee_scolaire_id'],
                'titre'             => $validated['titre'],
                'montant'           => $validated['montant'],
                'date_echeance'     => $validated['date_echeance'],
                'type'           => $validated['type'] ?? $this->deviner($validated['titre']),
                'montant_remise' => 0,
                'montant_paye'   => 0,
                'montant_restant'=> $validated['montant'],
                'statut'         => 'impaye',
            ]);
            $echeance->recalculer();

            // Les remises du stagiaire s'appliquent aussi à la nouvelle échéance
            $this->remises->appliquer($stagiaire);
        });

        return redirect()->route('echeanciers.index', ['stagiaire_id' => $stagiaire->id])
            ->with('success', 'Échéance créée.');
    }

    /**
     * Génère les mensualités (une par mois), sans doublon
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

        $stagiaire = Stagiaire::findOrFail($validated['stagiaire_id']);
        $debut = Carbon::parse($validated['date_debut']);
        $crees = 0;
        $ignores = 0;

        DB::transaction(function () use ($validated, $stagiaire, $debut, &$crees, &$ignores) {
            for ($i = 0; $i < $validated['nombre_mois']; $i++) {
                $date = $debut->copy()->addMonthsNoOverflow($i);
                $titre = 'Mensualité ' . self::MOIS[$date->month] . ' ' . $date->year;

                $existe = Echeancier::where('stagiaire_id', $stagiaire->id)
                    ->where('annee_scolaire_id', $validated['annee_scolaire_id'])
                    ->where('type', 'mensualite')
                    ->whereYear('date_echeance', $date->year)
                    ->whereMonth('date_echeance', $date->month)
                    ->exists();

                if ($existe) {
                    $ignores++;
                    continue;
                }

                Echeancier::create([
                    'stagiaire_id'      => $stagiaire->id,
                    'annee_scolaire_id' => $validated['annee_scolaire_id'],
                    'titre'             => $titre,
                    'type'              => 'mensualite',
                    'montant'           => $validated['montant_mensuel'],
                    'montant_remise'    => 0,
                    'date_echeance'     => $date,
                    'montant_paye'      => 0,
                    'montant_restant'   => $validated['montant_mensuel'],
                    'statut'            => 'impaye',
                ])->recalculer();
                $crees++;
            }

            $this->remises->appliquer($stagiaire);
        });

        $message = "{$crees} mensualité(s) créée(s).";
        if ($ignores) {
            $message .= " {$ignores} mois existaient déjà et ont été ignorés.";
        }

        return redirect()->route('echeanciers.index', ['stagiaire_id' => $stagiaire->id])->with('success', $message);
    }

    public function show(Echeancier $echeancier)
    {
        $echeancier->load(['stagiaire.filiere', 'anneeScolaire', 'paiements.user']);
        return view('echeanciers.show', compact('echeancier'));
    }

    public function edit(Echeancier $echeancier)
    {
        $stagiaires = Stagiaire::actifs()->with('filiere')->orderBy('nom')->get();
        $annees = AnneeScolaire::orderBy('debut', 'desc')->get();
        return view('echeanciers.edit', compact('echeancier', 'stagiaires', 'annees'));
    }

    public function update(Request $request, Echeancier $echeancier)
    {
        if ($echeancier->statut === 'paye') {
            return back()->with('error', 'Impossible de modifier une échéance déjà payée.');
        }

        $validated = $request->validate([
            'titre'         => 'required|string|max:255',
            'type'          => ['nullable', Rule::in(array_keys(Echeancier::TYPES))],
            'montant'       => 'required|numeric|min:' . max(1, (float) $echeancier->montant_paye),
            'date_echeance' => 'required|date',
        ], [
            'montant.min' => 'Le montant ne peut pas être inférieur à ce qui a déjà été payé (' . number_format($echeancier->montant_paye, 2, ',', ' ') . ' DH).',
        ]);

        DB::transaction(function () use ($echeancier, $validated) {
            $echeancier->update([
                'titre'         => $validated['titre'],
                'type'          => $validated['type'] ?? $echeancier->type,
                'montant'       => $validated['montant'],
                'date_echeance' => $validated['date_echeance'],
            ]);
            $this->remises->appliquer($echeancier->stagiaire); // recalcule remise, reste et statut
        });

        return redirect()->route('echeanciers.index', ['stagiaire_id' => $echeancier->stagiaire_id])
            ->with('success', 'Échéance mise à jour.');
    }

    public function destroy(Echeancier $echeancier)
    {
        if ((float) $echeancier->montant_paye > 0 || $echeancier->paiements()->exists()) {
            return back()->with('error', 'Impossible de supprimer une échéance qui a reçu des paiements.');
        }

        $stagiaire = $echeancier->stagiaire;
        $echeancier->delete();
        $stagiaire?->updateSoldePaiement();

        return redirect()->route('echeanciers.index', ['stagiaire_id' => $stagiaire?->id])
            ->with('success', 'Échéance supprimée.');
    }

    /**
     * Espace stagiaire : mes échéances
     */
    public function mesEcheanciers()
    {
        $stagiaire = auth()->user()->stagiaire;
        abort_unless($stagiaire, 403, 'Aucun profil stagiaire associé');

        $echeanciers = $stagiaire->echeanciers()->with('anneeScolaire')->orderBy('date_echeance')->get();

        $stats = [
            'total_du'  => $echeanciers->sum(fn ($e) => $e->montant_net),
            'total_paye'=> $echeanciers->sum('montant_paye'),
            'restant'   => $echeanciers->sum('montant_restant'),
            'remises'   => $echeanciers->sum('montant_remise'),
            'retards'   => $echeanciers->where('statut', 'en_retard')->count(),
        ];

        return view('stagiaires.echeanciers', compact('stagiaire', 'echeanciers', 'stats'));
    }

    public function imprimer(Echeancier $echeancier)
    {
        $echeancier->load(['stagiaire.filiere', 'stagiaire.classe', 'anneeScolaire', 'paiements.user']);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('echeanciers.print', ['echeancier' => $echeancier])
            ->download('echeance_' . $echeancier->id . '.pdf');
    }

    /**
     * Recalcule les statuts (retards) de toutes les échéances ouvertes
     */
    public function verifierRetards()
    {
        $avant = Echeancier::where('statut', 'en_retard')->count();

        Echeancier::where('montant_restant', '>', 0)->each(fn ($e) => $e->recalculer());
        Stagiaire::has('echeanciers')->each(fn ($s) => $s->updateSoldePaiement());

        $apres = Echeancier::where('statut', 'en_retard')->count();

        return back()->with('success', "Statuts recalculés : {$apres} échéance(s) en retard (avant : {$avant}).");
    }

    private function deviner(string $titre): string
    {
        $t = mb_strtolower($titre);
        return match (true) {
            str_contains($t, 'inscription') => 'inscription',
            str_contains($t, 'examen')      => 'examen',
            str_contains($t, 'mensualit') || preg_match('/janv|févr|fevr|mars|avril|mai|juin|juil|août|aout|sept|oct|nov|déc|dec/u', $t) => 'mensualite',
            default                         => 'autre',
        };
    }
}
