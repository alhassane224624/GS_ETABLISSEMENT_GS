<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Epreuve;
use App\Models\Examen;
use App\Models\Matiere;
use App\Models\Periode;
use App\Models\Salle;
use App\Models\User;
use App\Services\ExamenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExamenController extends Controller
{
    public function __construct(private ExamenService $service) {}

    // ------------------------- Administration -------------------------

    public function index(Request $request)
    {
        $annees = AnneeScolaire::orderByDesc('debut')->get();
        $annee = $request->filled('annee_id') ? AnneeScolaire::findOrFail($request->annee_id)
            : ($annees->firstWhere('is_active', true) ?? $annees->first());

        $examens = $annee
            ? Examen::with('periode')->withCount('epreuves')->where('annee_scolaire_id', $annee->id)->orderBy('date_debut')->get()
            : collect();
        $periodes = $annee ? Periode::where('annee_scolaire_id', $annee->id)->orderBy('debut')->get() : collect();

        return view('examens.index', compact('annees', 'annee', 'examens', 'periodes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'periode_id'        => 'nullable|required_if:type,normale|exists:periodes,id',
            'nom'               => 'required|string|max:150',
            'type'              => ['required', Rule::in(array_keys(Examen::TYPES))],
            'date_debut'        => 'required|date',
            'date_fin'          => 'required|date|after_or_equal:date_debut',
        ], ['periode_id.required_if' => 'Une session normale doit être rattachée à une période (ses notes vont dans les bulletins).']);

        $examen = Examen::create($data + ['created_by' => Auth::id()]);

        return redirect()->route('examens.show', $examen)->with('success', 'Session créée : ajoutez les épreuves.');
    }

    public function show(Examen $examen)
    {
        $examen->load(['anneeScolaire', 'periode', 'epreuves.classe', 'epreuves.matiere', 'epreuves.salle', 'epreuves.surveillant']);
        $examen->epreuves->each(fn ($e) => $e->nb_notes = $e->resultats()->whereNotNull('note')->count());

        return view('examens.show', [
            'examen' => $examen,
            'classes' => Classe::with('filiere')->where('annee_scolaire_id', $examen->annee_scolaire_id)->orderBy('nom')->get(),
            'matieres' => Matiere::orderBy('nom')->get(),
            'salles' => Salle::orderBy('nom')->get(),
            'surveillants' => User::where('role', 'professeur')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeEpreuve(Request $request, Examen $examen)
    {
        abort_if($examen->estCloturee(), 403, 'Session clôturée.');

        $data = $request->validate([
            'classe_id'      => ['required', Rule::exists('classes', 'id')->where('annee_scolaire_id', $examen->annee_scolaire_id)],
            'matiere_id'     => ['required', 'exists:matieres,id', Rule::unique('epreuves')->where('examen_id', $examen->id)->where('classe_id', $request->classe_id)],
            'date'           => 'required|date',
            'heure_debut'    => 'required|date_format:H:i',
            'heure_fin'      => 'required|date_format:H:i|after:heure_debut',
            'salle_id'       => 'nullable|exists:salles,id',
            'surveillant_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'professeur')],
            'note_sur'       => 'required|numeric|min:1|max:100',
        ], ['matiere_id.unique' => 'Cette matière a déjà une épreuve pour cette classe dans la session.']);

        try {
            $this->service->creerEpreuve($examen, $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', 'Épreuve ajoutée' . (!empty($data['salle_id']) && !empty($data['surveillant_id']) ? ' et inscrite au planning.' : '.'));
    }

    public function destroyEpreuve(Epreuve $epreuve)
    {
        try {
            $this->service->supprimerEpreuve($epreuve);
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }
        return back()->with('success', 'Épreuve supprimée.');
    }

    public function cloturer(Examen $examen)
    {
        $examen->update(['statut' => $examen->estCloturee() ? 'planifiee' : 'cloturee']);
        return back()->with('success', $examen->estCloturee() ? 'Session clôturée : les notes sont figées.' : 'Session rouverte.');
    }

    // ------------------------- Saisie des notes (admin ou professeur) -------------------------

    public function saisie(Epreuve $epreuve)
    {
        $this->autoriserSaisie($epreuve);
        $epreuve->load(['examen', 'classe', 'matiere']);

        $convoques = $this->service->convoques($epreuve);
        $resultats = $epreuve->resultats()->get()->keyBy('stagiaire_id');
        $layout = Auth::user()->isAdmin() ? 'layouts.app' : 'layouts.app-professeur';

        return view('examens.saisie', compact('epreuve', 'convoques', 'resultats', 'layout'));
    }

    public function enregistrerSaisie(Request $request, Epreuve $epreuve)
    {
        $this->autoriserSaisie($epreuve);

        $data = $request->validate([
            'notes'     => 'nullable|array',
            'notes.*'   => 'nullable|numeric|min:0',
            'absents'   => 'nullable|array',
        ]);

        try {
            $r = $this->service->enregistrerResultats($epreuve, $data['notes'] ?? [], $data['absents'] ?? []);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $message = "{$r['saisis']} résultat(s) enregistré(s).";
        if ($r['verrouilles']) {
            $message .= ' ' . count($r['verrouilles']) . ' non enregistré(s) : bulletin de la période déjà validé.';
        }

        return back()->with('success', $message);
    }

    /** Professeur : ses épreuves (matières qu'il enseigne dans ses filières) */
    public function mesEpreuves()
    {
        $user = Auth::user();
        $filieres = $user->filieres()->pluck('filieres.id');

        $epreuves = Epreuve::with(['examen', 'classe', 'matiere'])
            ->whereHas('examen', fn ($q) => $q->where('statut', '!=', 'cloturee'))
            ->whereHas('classe', fn ($q) => $q->whereIn('filiere_id', $filieres))
            ->orderBy('date')
            ->get()
            ->filter(fn ($e) => $user->canTeachMatiere($e->matiere_id) || (int) $e->surveillant_id === (int) $user->id);

        return view('examens.mes-epreuves', compact('epreuves'));
    }

    private function autoriserSaisie(Epreuve $epreuve): void
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return;
        }
        $epreuve->loadMissing('classe');
        abort_unless(
            $user->role === 'professeur'
            && $user->filieres()->where('filieres.id', $epreuve->classe->filiere_id)->exists()
            && $user->canTeachMatiere($epreuve->matiere_id),
            403,
            'Vous n\'enseignez pas cette matière dans cette classe.'
        );
    }
}
