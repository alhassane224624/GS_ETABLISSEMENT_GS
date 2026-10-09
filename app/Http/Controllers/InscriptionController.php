<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Inscription;
use App\Services\InscriptionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InscriptionController extends Controller
{
    public function __construct(private InscriptionService $service) {}

    /**
     * Vue d'ensemble d'une année : classes, effectifs, avancement des délibérations
     */
    public function index(Request $request)
    {
        $annees = AnneeScolaire::orderByDesc('debut')->get();
        $annee = $request->filled('annee_id')
            ? AnneeScolaire::findOrFail($request->annee_id)
            : ($annees->firstWhere('is_active', true) ?? $annees->first());

        $classes = collect();
        $sansClasse = collect();
        if ($annee) {
            $classes = Classe::with(['filiere', 'niveau'])
                ->where('annee_scolaire_id', $annee->id)
                ->withCount([
                    'inscriptions as inscrits' => fn ($q) => $q->where('statut', 'inscrit'),
                    'inscriptions as total' => fn ($q) => $q->where('statut', '!=', 'abandon'),
                    'inscriptions as decides' => fn ($q) => $q->where('statut', '!=', 'abandon')->whereNotNull('decision'),
                ])
                ->orderBy('filiere_id')->orderBy('niveau_id')->orderBy('nom')
                ->get();

            $sansClasse = Inscription::with(['stagiaire', 'filiere'])
                ->where('annee_scolaire_id', $annee->id)
                ->whereNull('classe_id')
                ->where('statut', 'inscrit')
                ->get();
        }

        return view('inscriptions.index', compact('annees', 'annee', 'classes', 'sansClasse'));
    }

    /**
     * Délibération d'une classe
     */
    public function deliberation(Classe $classe)
    {
        $classe->load(['filiere', 'niveau', 'anneeScolaire']);
        $lignes = $this->service->propositions($classe);
        $niveauSuivant = $this->service->niveauSuivant($classe->niveau);
        $seuil = $this->service->seuil();

        return view('inscriptions.deliberation', compact('classe', 'lignes', 'niveauSuivant', 'seuil'));
    }

    public function enregistrerDeliberation(Request $request, Classe $classe)
    {
        $data = $request->validate([
            'decisions'                 => 'required|array',
            'decisions.*.decision'      => ['nullable', Rule::in(array_keys(Inscription::DECISIONS))],
            'decisions.*.observation'   => 'nullable|string|max:500',
        ]);

        $n = $this->service->enregistrerDecisions($classe, $data['decisions']);

        return redirect()->route('inscriptions.index', ['annee_id' => $classe->annee_scolaire_id])
            ->with('success', "{$n} décision(s) enregistrée(s) pour {$classe->nom}.");
    }

    /**
     * Passage vers l'année suivante
     */
    public function passageForm(Request $request)
    {
        $annees = AnneeScolaire::orderByDesc('debut')->get();
        $source = $request->filled('source_id') ? AnneeScolaire::find($request->source_id) : $annees->firstWhere('is_active', true);
        $cible = $request->filled('cible_id') ? AnneeScolaire::find($request->cible_id)
            : ($source ? AnneeScolaire::where('debut', '>', $source->debut)->orderBy('debut')->first() : null);

        $groupes = collect();
        $classesCible = collect();

        if ($source && $cible && $source->id !== $cible->id) {
            $classesCible = Classe::with(['filiere', 'niveau'])->where('annee_scolaire_id', $cible->id)
                ->withCount(['inscriptions as inscrits' => fn ($q) => $q->where('statut', 'inscrit')])
                ->orderBy('nom')->get();

            $groupes = Classe::with(['filiere', 'niveau'])->where('annee_scolaire_id', $source->id)
                ->withCount([
                    'inscriptions as admis' => fn ($q) => $q->where('decision', 'admis')->where('statut', '!=', 'abandon'),
                    'inscriptions as redoublants' => fn ($q) => $q->where('decision', 'redouble')->where('statut', '!=', 'abandon'),
                    'inscriptions as sortants' => fn ($q) => $q->whereIn('decision', ['diplome', 'exclu'])->where('statut', '!=', 'abandon'),
                    'inscriptions as sans_decision' => fn ($q) => $q->whereNull('decision')->where('statut', 'inscrit'),
                ])
                ->orderBy('nom')->get()
                ->map(function ($classe) use ($classesCible) {
                    $suivant = $this->service->niveauSuivant($classe->niveau);
                    $classe->cibles_admis = $suivant
                        ? $classesCible->where('filiere_id', $classe->filiere_id)->where('niveau_id', $suivant->id)->values()
                        : collect();
                    $classe->cibles_redouble = $classesCible->where('filiere_id', $classe->filiere_id)
                        ->where('niveau_id', $classe->niveau_id)->values();
                    $classe->niveau_suivant = $suivant;
                    return $classe;
                });
        }

        return view('inscriptions.passage', compact('annees', 'source', 'cible', 'groupes', 'classesCible'));
    }

    public function passage(Request $request)
    {
        $data = $request->validate([
            'source_id'            => 'required|exists:annee_scolaires,id',
            'cible_id'             => 'required|exists:annee_scolaires,id|different:source_id',
            'affectations'         => 'nullable|array',
            'affectations.*.admis'    => 'nullable|exists:classes,id',
            'affectations.*.redouble' => 'nullable|exists:classes,id',
            'frais_reinscription'  => 'nullable|numeric|min:0',
        ]);

        try {
            $r = $this->service->passage(
                AnneeScolaire::findOrFail($data['source_id']),
                AnneeScolaire::findOrFail($data['cible_id']),
                $data['affectations'] ?? [],
                (float) ($data['frais_reinscription'] ?? 0)
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $message = "{$r['crees']} réinscription(s) créée(s), {$r['diplomes']} diplômé(s), {$r['sortis']} sortie(s)"
            . ($r['deja'] ? ", {$r['deja']} déjà inscrit(s)" : '') . '.';

        return redirect()->route('inscriptions.passage.form', ['source_id' => $data['source_id'], 'cible_id' => $data['cible_id']])
            ->with('success', $message . ' Activez la nouvelle année (Années scolaires) pour basculer les classes.')
            ->with('erreurs_passage', $r['erreurs']);
    }

    public function copierClasses(Request $request)
    {
        $data = $request->validate([
            'source_id' => 'required|exists:annee_scolaires,id',
            'cible_id'  => 'required|exists:annee_scolaires,id|different:source_id',
        ]);

        $n = $this->service->copierClasses(AnneeScolaire::findOrFail($data['source_id']), AnneeScolaire::findOrFail($data['cible_id']));

        return redirect()->route('inscriptions.passage.form', $data)
            ->with('success', "{$n} classe(s) créée(s) dans la nouvelle année.");
    }

    /** Procès-verbal de délibération d'une classe (PDF paysage) */
    public function pv(Classe $classe)
    {
        $classe->load(['filiere', 'niveau', 'anneeScolaire']);
        $examens = app(\App\Services\ExamenService::class);

        $lignes = $this->service->propositions($classe)->map(function ($l) use ($classe, $examens) {
            $sid = $l['inscription']->stagiaire_id;
            $l['matieres'] = $examens->moyennesMatieres($sid, $classe->annee_scolaire_id);
            $l['rattrapage'] = $examens->notesRattrapage($sid, $classe->annee_scolaire_id);
            return $l;
        });

        // Colonnes = toutes les matières rencontrées
        $colonnes = $lignes->flatMap(fn ($l) => collect($l['matieres'])->map(fn ($m, $code) => ['code' => $code, 'nom' => $m['nom'], 'coef' => $m['coefficient']]))
            ->unique('code')->sortBy('nom')->values();

        $stats = $lignes->groupBy(fn ($l) => $l['inscription']->decision ?? 'en_attente')->map->count();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('inscriptions.pv', [
            'classe' => $classe,
            'lignes' => $lignes,
            'colonnes' => $colonnes,
            'stats' => $stats,
            'seuil' => $this->service->seuil(),
            'etab' => \App\Support\Etablissement::infos(),
            'logo' => \App\Support\Etablissement::logoBase64(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('PV_deliberation_' . str($classe->nom)->slug() . '_' . str($classe->anneeScolaire->nom ?? '')->slug() . '.pdf');
    }
}
