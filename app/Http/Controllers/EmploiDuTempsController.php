<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Creneau;
use App\Models\Matiere;
use App\Models\Periode;
use App\Models\Salle;
use App\Models\User;
use App\Services\EmploiDuTempsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmploiDuTempsController extends Controller
{
    public function __construct(private EmploiDuTempsService $service) {}

    /**
     * Semaine type d'une classe + formulaire d'ajout + génération
     */
    public function index(Request $request)
    {
        $annees = AnneeScolaire::orderByDesc('debut')->get();
        $annee = $request->filled('annee_id') ? AnneeScolaire::findOrFail($request->annee_id)
            : ($annees->firstWhere('is_active', true) ?? $annees->first());

        $classes = $annee ? Classe::with('filiere')->where('annee_scolaire_id', $annee->id)->orderBy('nom')->get() : collect();
        $classe = $request->filled('classe_id') ? $classes->firstWhere('id', (int) $request->classe_id) : $classes->first();

        $creneaux = $classe
            ? Creneau::with(['matiere', 'professeur', 'salle'])->where('classe_id', $classe->id)
                ->orderBy('jour')->orderBy('heure_debut')->get()->groupBy('jour')
            : collect();

        $volume = $creneaux->flatten()->where('is_active', true)->sum(fn ($c) => $c->duree);

        $periode = $annee ? Periode::where('annee_scolaire_id', $annee->id)->where('is_active', true)->first() : null;

        return view('emploi-du-temps.index', [
            'annees' => $annees,
            'annee' => $annee,
            'classes' => $classes,
            'classe' => $classe,
            'creneaux' => $creneaux,
            'volume' => $volume,
            'matieres' => Matiere::orderBy('nom')->get(),
            'professeurs' => User::where('role', 'professeur')->where('is_active', true)->orderBy('name')->get(),
            'salles' => Salle::where('disponible', true)->orderBy('nom')->get(),
            'genererDebut' => max(now()->toDateString(), optional($periode)->debut?->toDateString() ?? $annee?->debut?->toDateString() ?? now()->toDateString()),
            'genererFin' => optional($periode)->fin?->toDateString() ?? $annee?->fin?->toDateString() ?? now()->addMonth()->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->valider($request);

        if ($conflits = $this->service->conflits($data)) {
            return back()->withInput()->with('error', implode(' ', $conflits));
        }

        $data['created_by'] = auth()->id();
        Creneau::create($data);

        return redirect()->route('emploi-du-temps.index', ['annee_id' => $data['annee_scolaire_id'], 'classe_id' => $data['classe_id']])
            ->with('success', 'Créneau ajouté. Pensez à générer les séances pour la période.');
    }

    public function update(Request $request, Creneau $creneau)
    {
        // Activer / désactiver uniquement (pour modifier un horaire : supprimer et recréer)
        $creneau->update(['is_active' => !$creneau->is_active]);

        if (!$creneau->is_active) {
            $n = $this->service->supprimerSeancesFutures($creneau);
            return back()->with('success', "Créneau désactivé, {$n} séance(s) à venir supprimée(s).");
        }

        if ($conflits = $this->service->conflits($creneau->toArray(), $creneau->id)) {
            $creneau->update(['is_active' => false]);
            return back()->with('error', implode(' ', $conflits));
        }

        return back()->with('success', 'Créneau réactivé. Régénérez les séances si besoin.');
    }

    public function destroy(Request $request, Creneau $creneau)
    {
        $n = $this->service->supprimerSeancesFutures($creneau);
        $params = ['annee_id' => $creneau->annee_scolaire_id, 'classe_id' => $creneau->classe_id];
        $creneau->delete(); // les séances passées restent (creneau_id → NULL)

        return redirect()->route('emploi-du-temps.index', $params)
            ->with('success', "Créneau supprimé avec {$n} séance(s) à venir. Les séances passées sont conservées.");
    }

    public function generer(Request $request)
    {
        $data = $request->validate([
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'portee'            => 'required|in:classe,toutes',
            'classe_id'         => 'required_if:portee,classe|nullable|exists:classes,id',
            'date_debut'        => 'required|date',
            'date_fin'          => 'required|date|after_or_equal:date_debut',
            'jours_exclus'      => 'nullable|string|max:2000',
        ]);

        if (Carbon::parse($data['date_debut'])->diffInDays(Carbon::parse($data['date_fin'])) > 200) {
            return back()->withInput()->with('error', 'Période trop longue : générez au maximum 200 jours à la fois (un semestre).');
        }

        // Jours exclus : dates séparées par des virgules ou des retours à la ligne (jj/mm/aaaa ou aaaa-mm-jj)
        $exclus = collect(preg_split('/[\s,;]+/', (string) ($data['jours_exclus'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(function ($d) {
                try {
                    return str_contains($d, '/') ? Carbon::createFromFormat('d/m/Y', $d)->toDateString() : Carbon::parse($d)->toDateString();
                } catch (\Throwable) {
                    return null;
                }
            })->filter()->values()->all();

        $r = $this->service->generer(
            $data['annee_scolaire_id'],
            $data['date_debut'],
            $data['date_fin'],
            $data['portee'] === 'classe' ? [(int) $data['classe_id']] : null,
            $exclus
        );

        return back()
            ->with('success', "{$r['creees']} séance(s) créée(s)" . ($r['existantes'] ? ", {$r['existantes']} déjà présente(s)" : '') . '.')
            ->with('conflits_generation', $r['conflits']);
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'classe_id'     => 'required|exists:classes,id',
            'matiere_id'    => 'required|exists:matieres,id',
            'professeur_id' => ['required', Rule::exists('users', 'id')->where('role', 'professeur')],
            'salle_id'      => 'required|exists:salles,id',
            'jour'          => 'required|integer|between:1,6',
            'heure_debut'   => 'required|date_format:H:i',
            'heure_fin'     => 'required|date_format:H:i|after:heure_debut',
            'type_cours'    => ['required', Rule::in(array_keys(Creneau::TYPES))],
        ], [
            'heure_fin.after' => 'L\'heure de fin doit être après l\'heure de début.',
        ]);
    }
}
