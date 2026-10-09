<?php

namespace App\Http\Controllers;

use App\Models\Stagiaire;
use App\Models\Filiere;
use App\Models\Classe;
use App\Models\Niveau;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\StagiaireService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StagiairesExport;

// 🔔 AJOUT : Importer les notifications
use App\Notifications\StagiaireCreated;
use App\Notifications\StagiaireUpdated;
use App\Notifications\InscriptionValidated;

class StagiaireController extends Controller
{
    public function __construct(private StagiaireService $service)
    {
        $this->middleware('admin')->except(['showInscriptionForm', 'storeInscription']);
    }

    public function index(Request $request)
    {
        $query = Stagiaire::with(['filiere', 'classe', 'niveau', 'createdBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('filiere_id')) {
            $query->where('filiere_id', $request->filiere_id);
        }

        if ($request->filled('classe_id')) {
            $query->where('classe_id', $request->classe_id);
        }

        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->niveau_id);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $stagiaires = $query->latest('created_at')->paginate(20);
        
        $filieres = Filiere::all();
        $classes = Classe::with('niveau')->get();
        $niveaux = Niveau::with('filiere')->get();

        return view('stagiaires.index', compact('stagiaires', 'filieres', 'classes', 'niveaux'));
    }

    public function create()
    {
        $filieres = Filiere::all();
        $classes = Classe::with('niveau', 'filiere')->where('effectif_actuel', '<', 'effectif_max')->get();
        $niveaux = Niveau::with('filiere')->get();
        
        return view('stagiaires.create', compact('filieres', 'classes', 'niveaux'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'matricule' => 'nullable|string|max:255|unique:stagiaires',
            'date_naissance' => 'nullable|date|before:today',
            'lieu_naissance' => 'nullable|string|max:255',
            'sexe' => 'nullable|in:M,F',
            'telephone' => 'nullable|string|max:20',
            'email' => 'required|email|max:255|unique:users,email',
            'adresse' => 'nullable|string|max:500',
            'nom_tuteur' => 'nullable|string|max:255',
            'telephone_tuteur' => 'nullable|string|max:20',
            'email_tuteur' => 'nullable|email|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'filiere_id' => 'required|exists:filieres,id',
            'classe_id' => 'nullable|exists:classes,id',
            'niveau_id' => 'nullable|exists:niveaux,id',
            'date_inscription' => 'nullable|date',
            'frais_inscription' => 'nullable|numeric|min:0',
            'frais_payes' => 'boolean',
        ]);

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('photos', 'public')
            : null;

        try {
            [$stagiaire, $motDePasse] = $this->service->creer(
                array_merge($validated, [
                    'photo'             => $photoPath,
                    'frais_inscription' => $validated['frais_inscription'] ?? 0,
                    'frais_payes'       => $validated['frais_payes'] ?? false,
                ]),
                Auth::id()
            );
        } catch (ValidationException $e) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            throw $e;
        }

        // 🔔 Notifier les administrateurs
        User::where('role', 'administrateur')->get()
            ->each(fn ($admin) => $admin->notify(new StagiaireCreated($stagiaire)));

        return redirect()->route('stagiaires.show', $stagiaire)
            ->with('success', "✅ {$stagiaire->prenom} {$stagiaire->nom} a été créé. Identifiant : {$stagiaire->email} — mot de passe provisoire : {$motDePasse} (à communiquer au stagiaire, il n'apparaîtra plus).");
    }

    public function show(Stagiaire $stagiaire)
    {
        $stagiaire->load(['inscriptions.anneeScolaire', 'inscriptions.classe', 'inscriptions.niveau', 'parents']);
        $stagiaire->load(['filiere', 'classe', 'niveau', 'notes.matiere', 'absences']);
        
        $stats = [
            'total_notes' => $stagiaire->notes()->count(),
            'moyenne_generale' => $stagiaire->notes()->avg('note'),
            'total_absences' => $stagiaire->absences()->count(),
            'absences_injustifiees' => $stagiaire->absences()->where('justifiee', false)->count(),
        ];

        return view('stagiaires.show', compact('stagiaire', 'stats'));
    }

    public function edit(Stagiaire $stagiaire)
    {
        $filieres = Filiere::all();
        $classes = Classe::with('niveau', 'filiere')->get();
        $niveaux = Niveau::with('filiere')->get();
        
        return view('stagiaires.edit', compact('stagiaire', 'filieres', 'classes', 'niveaux'));
    }

    public function update(Request $request, Stagiaire $stagiaire)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'matricule' => 'required|string|max:255|unique:stagiaires,matricule,' . $stagiaire->id,
            'date_naissance' => 'nullable|date|before:today',
            'lieu_naissance' => 'nullable|string|max:255',
            'sexe' => 'nullable|in:M,F',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:users,email,' . ($stagiaire->user_id ?? 'NULL'),
            'adresse' => 'nullable|string|max:500',
            'nom_tuteur' => 'nullable|string|max:255',
            'telephone_tuteur' => 'nullable|string|max:20',
            'email_tuteur' => 'nullable|email|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'filiere_id' => 'required|exists:filieres,id',
            'classe_id' => 'nullable|exists:classes,id',
            'niveau_id' => 'nullable|exists:niveaux,id',
            'statut' => 'required|in:actif,suspendu,diplome,abandonne,transfere',
            'motif_statut' => 'nullable|string|max:1000',
            'frais_inscription' => 'nullable|numeric|min:0',
            'frais_payes' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('photo')) {
            if ($stagiaire->photo) {
                Storage::disk('public')->delete($stagiaire->photo);
            }
            $validated['photo'] = $request->file('photo')->store('photos', 'public');
        }

        DB::transaction(function () use ($stagiaire, $validated) {
            $this->service->changerClasse(
                $stagiaire,
                $validated['classe_id'] ?? null,
                (int) $validated['filiere_id'],
                $validated['niveau_id'] ?? null
            );

            $stagiaire->update($validated);
            $this->service->synchroniser($stagiaire); // inscription de l'année + effectifs

            // Garder le compte utilisateur synchronisé
            if ($stagiaire->user) {
                $stagiaire->user->update(array_filter([
                    'name'      => trim($stagiaire->prenom . ' ' . $stagiaire->nom),
                    'email'     => $validated['email'] ?? null,
                    'is_active' => $validated['is_active'] ?? null,
                ], fn ($v) => $v !== null));
            }
        });

        // 🔔 NOTIFICATION : Notifier les administrateurs de la modification
        User::where('role', 'administrateur')
            ->get()
            ->each(function($admin) use ($stagiaire) {
                $admin->notify(new StagiaireUpdated($stagiaire));
            });

        return redirect()->route('stagiaires.index')
            ->with('success', 'Stagiaire mis à jour avec succès.');
    }

    public function destroy(Stagiaire $stagiaire)
    {
        $photo = $stagiaire->photo;

        try {
            $this->service->supprimer($stagiaire);
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        if ($photo) {
            Storage::disk('public')->delete($photo);
        }

        return redirect()->route('stagiaires.index')
            ->with('success', 'Stagiaire supprimé avec succès.');
    }

    public function export(Request $request)
    {
        $filiere_id = $request->get('filiere_id');
        $classe_id = $request->get('classe_id');
        
        return Excel::download(
            new StagiairesExport($filiere_id, $classe_id), 
            'stagiaires_' . now()->format('Y_m_d') . '.xlsx'
        );
    }

    public function changeStatut(Request $request, Stagiaire $stagiaire)
    {
        $validated = $request->validate([
            'statut' => 'required|in:actif,suspendu,diplome,abandonne,transfere',
            'motif_statut' => 'nullable|string|max:1000',
        ]);

        $actif = $validated['statut'] === 'actif';
        $stagiaire->update($validated + ['is_active' => $actif || $validated['statut'] === 'diplome']);
        $this->service->synchroniser($stagiaire); // abandon / transfert libère la place dans la classe

        // L'accès à l'espace stagiaire suit le statut
        $stagiaire->user?->update([
            'is_active'    => $actif || $validated['statut'] === 'diplome',
            'activated_at' => $actif ? now() : $stagiaire->user->activated_at,
            'activated_by' => $actif ? Auth::id() : $stagiaire->user->activated_by,
        ]);

        // 🔔 NOTIFICATION : Si inscription validée, notifier le stagiaire
        if ($validated['statut'] === 'actif' && $stagiaire->user) {
            $stagiaire->user->notify(new InscriptionValidated($stagiaire));
        }

        return redirect()->back()
            ->with('success', 'Statut du stagiaire modifié avec succès.');
    }

    // =========================================================================
    // INSCRIPTION EN LIGNE (publique)
    // =========================================================================

    public function showInscriptionForm()
    {
        $filieres = Filiere::orderBy('nom')->get();
        return view('stagiaires.inscription', compact('filieres'));
    }

    public function storeInscription(Request $request)
    {
        // Pot de miel anti-robot : ce champ caché doit rester vide
        if ($request->filled('site_web')) {
            return redirect()->route('welcome');
        }

        $validated = $request->validate([
            'nom'             => 'required|string|max:255',
            'prenom'          => 'required|string|max:255',
            'date_naissance'  => 'nullable|date|before:today',
            'sexe'            => 'nullable|in:M,F',
            'telephone'       => 'required|string|max:20',
            'email'           => 'required|email|max:255|unique:users,email',
            'adresse'         => 'nullable|string|max:500',
            'nom_tuteur'      => 'nullable|string|max:255',
            'telephone_tuteur'=> 'nullable|string|max:20',
            'photo'           => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'filiere_id'      => 'required|exists:filieres,id',
        ]);

        $validated['photo'] = $request->hasFile('photo')
            ? $request->file('photo')->store('photos', 'public')
            : null;

        // Dossier créé désactivé, en attente de validation par l'administration
        [$stagiaire] = $this->service->creer($validated + [
            'statut'       => 'suspendu',
            'motif_statut' => 'Demande d\'inscription en ligne — à valider',
        ], null, false);

        User::where('role', 'administrateur')->get()
            ->each(fn ($admin) => $admin->notify(new StagiaireCreated($stagiaire)));

        return redirect()->route('stagiaires.inscription.form')
            ->with('success', "Votre demande a bien été envoyée (dossier {$stagiaire->matricule}). L'établissement vous contactera après validation.");
    }
}
