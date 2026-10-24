<?php

namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Models\Classe;
use App\Models\Filiere;
use App\Services\DiffusionAnnonces;
use App\Support\Layout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Administration et comptabilité : rédaction des annonces et suivi des lectures
 */
class GestionAnnonceController extends Controller
{
    public function __construct(private DiffusionAnnonces $diffusion)
    {
        $this->middleware('financial');
    }

    public function index(Request $request)
    {
        $annonces = Annonce::with('auteur:id,name,role')
            ->withCount('lectures')
            ->when($request->etat === 'programmees', fn ($q) => $q->where('publie_le', '>', now()))
            ->when($request->etat === 'expirees', fn ($q) => $q->whereNotNull('expire_le')->where('expire_le', '<=', now()))
            ->when(!$request->filled('etat'), fn ($q) => $q->enCours())
            ->orderByDesc('epinglee')->orderByDesc('publie_le')
            ->paginate(15)
            ->withQueryString();

        return view('annonces.gestion.index', ['annonces' => $annonces, 'layout' => Layout::pour(Auth::user())]);
    }

    public function create()
    {
        return view('annonces.gestion.form', $this->donneesFormulaire(new Annonce([
            'importance' => 'info',
            'roles' => array_keys(Annonce::ROLES),
            'publie_le' => now(),
        ])));
    }

    public function store(Request $request)
    {
        $data = $this->valider($request);
        $data['auteur_id'] = Auth::id();

        if ($request->hasFile('fichier')) {
            $data['fichier_path'] = $request->file('fichier')->store('annonces', 'local');
            $data['fichier_nom'] = $request->file('fichier')->getClientOriginalName();
        }

        $annonce = Annonce::create($data);
        $notifies = $this->diffusion->diffuser($annonce);

        $message = $annonce->publie_le->isFuture()
            ? 'Annonce programmée pour le ' . $annonce->publie_le->isoFormat('D MMMM [à] HH[h]mm') . '.'
            : 'Annonce publiée' . ($notifies ? " : {$notifies} personne(s) notifiée(s)." : '.');

        return redirect()->route('gestion.annonces.show', $annonce)->with('success', $message);
    }

    public function show(Annonce $annonce)
    {
        $destinataires = $annonce->destinataires()->orderBy('role')->orderBy('name')->get(['users.id', 'name', 'email', 'role']);
        $lectures = $annonce->lectures()->pluck('lu_at', 'user_id');

        return view('annonces.gestion.show', [
            'annonce' => $annonce,
            'destinataires' => $destinataires,
            'lectures' => $lectures,
            'nonLecteurs' => $destinataires->reject(fn ($u) => $lectures->has($u->id)),
            'layout' => Layout::pour(Auth::user()),
        ]);
    }

    public function edit(Annonce $annonce)
    {
        $this->verifierDroits($annonce);

        return view('annonces.gestion.form', $this->donneesFormulaire($annonce));
    }

    public function update(Request $request, Annonce $annonce)
    {
        $this->verifierDroits($annonce);
        $data = $this->valider($request);

        if ($request->boolean('retirer_fichier') && $annonce->fichier_path) {
            Storage::disk('local')->delete($annonce->fichier_path);
            $data['fichier_path'] = $data['fichier_nom'] = null;
        }
        if ($request->hasFile('fichier')) {
            if ($annonce->fichier_path) {
                Storage::disk('local')->delete($annonce->fichier_path);
            }
            $data['fichier_path'] = $request->file('fichier')->store('annonces', 'local');
            $data['fichier_nom'] = $request->file('fichier')->getClientOriginalName();
        }

        $annonce->update($data);
        $this->diffusion->diffuser($annonce); // annonce programmée dont la date est maintenant passée

        return redirect()->route('gestion.annonces.show', $annonce)->with('success', 'Annonce mise à jour.');
    }

    public function destroy(Annonce $annonce)
    {
        $this->verifierDroits($annonce);

        if ($annonce->fichier_path) {
            Storage::disk('local')->delete($annonce->fichier_path);
        }
        $annonce->delete();

        return redirect()->route('gestion.annonces.index')->with('success', 'Annonce supprimée.');
    }

    // -------------------------------------------------------------------------

    private function valider(Request $request): array
    {
        $data = $request->validate([
            'titre'        => 'required|string|max:255',
            'contenu'      => 'required|string|max:10000',
            'importance'   => ['required', Rule::in(array_keys(Annonce::IMPORTANCES))],
            'roles'        => 'required|array|min:1',
            'roles.*'      => Rule::in(array_keys(Annonce::ROLES)),
            'portee'       => 'required|in:tous,filiere,classes',
            'filiere_id'   => 'required_if:portee,filiere|nullable|exists:filieres,id',
            'classe_ids'   => 'required_if:portee,classes|nullable|array',
            'classe_ids.*' => 'exists:classes,id',
            'fichier'      => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,odt,jpg,jpeg,png|max:10240',
            'publie_le'    => 'nullable|date',
            'expire_le'    => 'nullable|date|after:' . ($request->filled('publie_le') ? 'publie_le' : 'now'),
            'epinglee'     => 'boolean',
        ], [
            'roles.required' => 'Choisissez au moins un groupe de destinataires.',
            'filiere_id.required_if' => 'Choisissez la filière.',
            'classe_ids.required_if' => 'Choisissez au moins une classe.',
            'expire_le.after' => 'La date d\'expiration doit être après la date de publication.',
        ]);

        return [
            'titre' => $data['titre'],
            'contenu' => $data['contenu'],
            'importance' => $data['importance'],
            'roles' => array_values(array_unique($data['roles'])),
            'filiere_id' => $data['portee'] === 'filiere' ? $data['filiere_id'] : null,
            'classe_ids' => $data['portee'] === 'classes' ? array_map('intval', $data['classe_ids']) : null,
            'publie_le' => $data['publie_le'] ?? now(),
            'expire_le' => $data['expire_le'] ?? null,
            'epinglee' => $request->boolean('epinglee'),
        ];
    }

    private function donneesFormulaire(Annonce $annonce): array
    {
        return [
            'annonce' => $annonce,
            'filieres' => Filiere::orderBy('nom')->get(['id', 'nom']),
            'classes' => Classe::with('filiere:id,nom')
                ->whereHas('anneeScolaire', fn ($q) => $q->where('is_active', true))
                ->orderBy('nom')->get(['id', 'nom', 'filiere_id']),
            'layout' => Layout::pour(Auth::user()),
        ];
    }

    private function verifierDroits(Annonce $annonce): void
    {
        abort_unless($annonce->modifiablePar(Auth::user()), 403, 'Seul l\'auteur ou l\'administration peut modifier cette annonce.');
    }
}
