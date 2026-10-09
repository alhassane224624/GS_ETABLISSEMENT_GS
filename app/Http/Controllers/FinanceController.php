<?php

namespace App\Http\Controllers;

use App\Models\Depense;
use App\Models\Salaire;
use App\Models\User;
use App\Services\FinanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Dépenses, salaires des professeurs et bilan (administrateur et comptable).
 */
class FinanceController extends Controller
{
    public function __construct(private FinanceService $service) {}

    private function layout(): string
    {
        return Auth::user()->role === 'comptable' ? 'layouts.comptable' : 'layouts.app';
    }

    // ------------------------------ Dépenses ------------------------------

    public function depenses(Request $request)
    {
        $debut = $request->filled('debut') ? Carbon::parse($request->debut) : now()->startOfMonth();
        $fin = $request->filled('fin') ? Carbon::parse($request->fin) : now()->endOfMonth();

        $query = Depense::with('auteur')
            ->whereBetween('date_depense', [$debut->toDateString(), $fin->toDateString()])
            ->when($request->filled('categorie'), fn ($q) => $q->where('categorie', $request->categorie));

        $total = (clone $query)->sum('montant');
        $depenses = $query->orderByDesc('date_depense')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('finances.depenses', ['layout' => $this->layout()] + compact('depenses', 'total', 'debut', 'fin'));
    }

    public function storeDepense(Request $request)
    {
        $data = $request->validate([
            'categorie'     => ['required', Rule::in(array_keys(Depense::CATEGORIES))],
            'libelle'       => 'required|string|max:255',
            'montant'       => 'required|numeric|min:0.01',
            'date_depense'  => 'required|date|before_or_equal:today',
            'mode_paiement' => ['required', Rule::in(array_keys(Depense::MODES))],
            'reference'     => 'nullable|string|max:100',
            'fournisseur'   => 'nullable|string|max:150',
            'justificatif'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('justificatif')) {
            $data['justificatif'] = $request->file('justificatif')->store('depenses', 'public');
        }

        Depense::create($data + ['created_by' => Auth::id()]);

        return back()->with('success', 'Dépense enregistrée.');
    }

    public function destroyDepense(Depense $depense)
    {
        if ($depense->salaire_id) {
            return back()->with('error', 'Dépense liée à un salaire : annulez le paiement du salaire à la place.');
        }
        if ($depense->justificatif) {
            Storage::disk('public')->delete($depense->justificatif);
        }
        $depense->delete();

        return back()->with('success', 'Dépense supprimée.');
    }

    // ------------------------------ Salaires ------------------------------

    public function salaires(Request $request)
    {
        $mois = $request->filled('mois') ? Carbon::createFromFormat('Y-m', $request->mois)->startOfMonth() : now()->startOfMonth();

        $salaires = Salaire::with('professeur')->whereDate('mois', $mois->toDateString())->get()->sortBy('professeur.name');
        $professeurs = User::where('role', 'professeur')->where('is_active', true)->orderBy('name')->get();

        return view('finances.salaires', ['layout' => $this->layout()] + compact('mois', 'salaires', 'professeurs'));
    }

    public function calculerSalaires(Request $request)
    {
        $request->validate(['mois' => 'required|date_format:Y-m']);
        $mois = Carbon::createFromFormat('Y-m', $request->mois)->startOfMonth();

        $r = $this->service->calculerMois($mois);
        $message = "{$r['calcules']} salaire(s) calculé(s)" . ($r['payes'] ? ", {$r['payes']} déjà payé(s) inchangé(s)" : '') . '.';
        if ($r['sans_tarif']) {
            $message .= ' Sans tarif défini : ' . implode(', ', $r['sans_tarif']) . '.';
        }

        return back()->with('success', $message);
    }

    public function remuneration(Request $request, User $professeur)
    {
        abort_unless($professeur->role === 'professeur', 404);
        $data = $request->validate([
            'mode_remuneration' => 'required|in:horaire,fixe',
            'taux_horaire'      => 'nullable|required_if:mode_remuneration,horaire|numeric|min:0',
            'salaire_fixe'      => 'nullable|required_if:mode_remuneration,fixe|numeric|min:0',
        ]);
        $professeur->update($data);

        return back()->with('success', "Rémunération de {$professeur->name} enregistrée. Recalculez le mois pour l'appliquer.");
    }

    public function showSalaire(Salaire $salaire)
    {
        $salaire->load('professeur');
        $seances = $this->service->seancesRealisees($salaire->professeur_id, $salaire->mois);

        return view('finances.salaire', ['layout' => $this->layout()] + compact('salaire', 'seances'));
    }

    public function ajusterSalaire(Request $request, Salaire $salaire)
    {
        $data = $request->validate([
            'primes' => 'nullable|numeric|min:0',
            'retenues' => 'nullable|numeric|min:0',
            'observation' => 'nullable|string|max:500',
        ]);
        try {
            $this->service->ajuster($salaire, (float) ($data['primes'] ?? 0), (float) ($data['retenues'] ?? 0), $data['observation'] ?? null);
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }
        return back()->with('success', 'Salaire ajusté.');
    }

    public function payerSalaire(Request $request, Salaire $salaire)
    {
        $data = $request->validate([
            'mode_paiement' => ['required', Rule::in(array_keys(Depense::MODES))],
            'date_paiement' => 'required|date|before_or_equal:today',
            'reference'     => 'nullable|string|max:100',
        ]);
        try {
            $this->service->payer($salaire, $data['mode_paiement'], $data['date_paiement'], $data['reference'] ?? null);
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }
        return back()->with('success', 'Salaire payé et enregistré dans les dépenses.');
    }

    public function annulerPaiementSalaire(Salaire $salaire)
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Réservé à l\'administrateur.');
        $this->service->annulerPaiement($salaire);
        return back()->with('success', 'Paiement du salaire annulé (dépense supprimée).');
    }

    // ------------------------------ Bilan ------------------------------

    public function bilan(Request $request)
    {
        $debut = $request->filled('debut') ? Carbon::parse($request->debut) : now()->startOfYear();
        $fin = $request->filled('fin') ? Carbon::parse($request->fin) : now();
        if ($fin->lt($debut)) {
            [$debut, $fin] = [$fin, $debut];
        }

        $bilan = $this->service->bilan($debut, $fin);

        return view('finances.bilan', ['layout' => $this->layout()] + compact('bilan', 'debut', 'fin'));
    }
}
