<?php

namespace App\Http\Controllers;

use App\Models\Echeancier;
use App\Models\Remise;
use App\Models\Stagiaire;
use App\Services\RemiseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Une remise n'est plus un simple enregistrement : à chaque création, modification,
 * activation ou suppression, elle est (ré)appliquée sur les échéances du stagiaire.
 */
class RemiseController extends Controller
{
    public function __construct(private RemiseService $service)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->hasFinancialAccess()) {
                abort(403, 'Accès réservé aux comptables et administrateurs');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $remises = Remise::with(['stagiaire.filiere', 'createur'])
            ->when($request->filled('stagiaire_id'), fn ($q) => $q->where('stagiaire_id', $request->stagiaire_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_remises'         => Remise::count(),
            'remises_actives'       => Remise::where('is_active', true)->count(),
            // Montant réellement accordé, tous types confondus (et non la somme des « valeurs » % + DH)
            'montant_total_remises' => Echeancier::sum('montant_remise'),
        ];

        return view('remises.index', compact('remises', 'stats'));
    }

    public function create(Request $request)
    {
        $stagiaire = $request->filled('stagiaire_id') ? Stagiaire::findOrFail($request->stagiaire_id) : null;
        $stagiaires = Stagiaire::actifs()->with('filiere')->orderBy('nom')->get();

        return view('remises.create', compact('stagiaires', 'stagiaire'));
    }

    public function store(Request $request)
    {
        $validated = $this->valider($request, true);

        $remise = Remise::create($validated + [
            'created_by' => Auth::id(),
            'is_active'  => $request->boolean('is_active'),
        ]);

        $this->service->appliquer($remise->stagiaire);

        return redirect()->route('remises.show', $remise)
            ->with('success', 'Remise créée et appliquée aux échéances concernées.');
    }

    public function show(Remise $remise)
    {
        $remise->load(['stagiaire.filiere', 'stagiaire.classe', 'createur']);

        // Échéances effectivement concernées par cette remise
        $echeances = Echeancier::where('stagiaire_id', $remise->stagiaire_id)->orderBy('date_echeance')->get()
            ->filter(fn ($e) => $remise->concerne($e));

        return view('remises.show', compact('remise', 'echeances'));
    }

    public function edit(Remise $remise)
    {
        $stagiaires = Stagiaire::actifs()->with('filiere')->orderBy('nom')->get();
        return view('remises.edit', compact('remise', 'stagiaires'));
    }

    public function update(Request $request, Remise $remise)
    {
        $validated = $this->valider($request, false);

        $remise->update($validated + ['is_active' => $request->boolean('is_active')]);
        $this->service->appliquer($remise->stagiaire);

        return redirect()->route('remises.show', $remise)
            ->with('success', 'Remise mise à jour et réappliquée.');
    }

    public function destroy(Remise $remise)
    {
        $stagiaire = $remise->stagiaire;
        $remise->delete();
        $this->service->appliquer($stagiaire);

        return redirect()->route('remises.index')
            ->with('success', 'Remise supprimée : les échéances non soldées retrouvent leur montant normal.');
    }

    public function toggleActive(Remise $remise)
    {
        $remise->update(['is_active' => !$remise->is_active]);
        $this->service->appliquer($remise->stagiaire);

        return back()->with('success', 'Remise ' . ($remise->is_active ? 'activée' : 'désactivée') . ' et échéances recalculées.');
    }

    private function valider(Request $request, bool $creation): array
    {
        $regles = [
            'titre'      => 'required|string|max:255',
            'type'       => 'required|in:pourcentage,montant_fixe',
            'valeur'     => ['required', 'numeric', 'min:0.01', $request->input('type') === 'pourcentage' ? 'max:100' : 'max:1000000'],
            'porte'      => ['nullable', Rule::in(array_keys(Remise::PORTEES))],
            'motif'      => 'required|string|max:1000',
            'date_debut' => 'required|date',
            'date_fin'   => 'nullable|date|after_or_equal:date_debut',
        ];
        if ($creation) {
            $regles['stagiaire_id'] = 'required|exists:stagiaires,id';
        }

        $validated = $request->validate($regles, [
            'valeur.max' => 'Un pourcentage ne peut pas dépasser 100 %.',
        ]);
        $validated['porte'] = $validated['porte'] ?? 'mensualite';

        return $validated;
    }
}
