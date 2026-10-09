<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Filiere;
use App\Models\Matiere;
use App\Models\Stagiaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\StagiaireService;

// Notifications
use App\Notifications\AccountActivated;
use App\Notifications\AccountDeactivated;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15);

        $stats = [
            'total_users' => User::count(),
            'admins' => User::where('role', 'administrateur')->count(),
            'comptables' => User::where('role', 'comptable')->count(), // ✅ AJOUT
            'professeurs' => User::where('role', 'professeur')->count(),
            'stagiaires' => User::where('role', 'stagiaire')->count(),
            'actifs' => User::where('is_active', true)->count(),
        ];

        return view('users.index', compact('users', 'stats'));
    }

    public function create()
    {
        $filieres = Filiere::all();
        $matieres = Matiere::all();

        return view('users.create', compact('filieres', 'matieres'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:stagiaire,administrateur,professeur,comptable', // ✅ AJOUT comptable
            'specialite' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'telephone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'filiere_id' => 'nullable|required_if:role,stagiaire|exists:filieres,id',
            'classe_id' => 'nullable|exists:classes,id',
            'niveau_id' => 'nullable|exists:niveaux,id',
            'filieres' => 'nullable|array',
            'filieres.*' => 'exists:filieres,id',
            'matieres' => 'nullable|array',
            'matieres.*' => 'exists:matieres,id',
        ], [
            'filiere_id.required_if' => 'La filière est obligatoire pour un stagiaire.',
        ]);

        // Un stagiaire est créé par le même service que la fiche stagiaire
        // (capacité de classe, cohérence filière/classe, matricule, échéance d'inscription)
        if ($validated['role'] === 'stagiaire') {
            $parts = preg_split('/\s+/', trim($validated['name']));
            $nom = $parts[0] ?? $validated['name'];
            $prenom = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : $nom;

            $stagiaire = DB::transaction(function () use ($validated, $nom, $prenom) {
                [$stagiaire] = app(StagiaireService::class)->creer([
                    'nom'        => $nom,
                    'prenom'     => $prenom,
                    'email'      => $validated['email'],
                    'telephone'  => $validated['telephone'] ?? null,
                    'filiere_id' => $validated['filiere_id'],
                    'classe_id'  => $validated['classe_id'] ?? null,
                    'niveau_id'  => $validated['niveau_id'] ?? null,
                ], Auth::id(), (bool) ($validated['is_active'] ?? true));

                // Mot de passe choisi par l'administrateur
                $stagiaire->user->update(['password' => Hash::make($validated['password'])]);

                return $stagiaire;
            });

            return redirect()->route('stagiaires.show', $stagiaire)
                ->with('success', "Stagiaire {$stagiaire->matricule} créé. Complétez sa fiche si besoin.");
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'specialite' => $validated['specialite'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'telephone' => $validated['telephone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => Auth::id(),
        ]);

        // Si professeur, assigner filières et matières
        if ($user->role === 'professeur') {
            if (!empty($validated['filieres'])) {
                $syncFilieres = [];
                foreach ($validated['filieres'] as $filiereId) {
                    $syncFilieres[$filiereId] = [
                        'created_by' => Auth::id(),
                        'is_active' => true,
                        'date_assignation' => now(),
                    ];
                }
                $user->filieres()->sync($syncFilieres);
            }

            if (!empty($validated['matieres'])) {
                $filiere_id = $validated['filieres'][0] ?? null;
                $syncMatieres = [];
                foreach ($validated['matieres'] as $matiereId) {
                    $syncMatieres[$matiereId] = [
                        'filiere_id' => $filiere_id,
                        'assigned_by' => Auth::id(),
                        'is_active' => true,
                        'date_assignation' => now(),
                    ];
                }
                $user->matieresEnseignees()->sync($syncMatieres);
            }
        }

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    public function show(User $user)
    {
        $user->load(['filieres', 'matieresEnseignees', 'createdBy', 'activatedBy']);

        $stats = [
            'notes_creees' => $user->notesCreated()->count(),
            'stagiaires_crees' => $user->stagiairesCreated()->count(),
            'plannings_crees' => $user->planningsCreated()->count(),
        ];

        // ✅ Stats supplémentaires pour comptable
        if ($user->isComptable()) {
            $stats['paiements_valides'] = \App\Models\Paiement::where('valide_by', $user->id)->count();
            $stats['montant_valide'] = \App\Models\Paiement::where('valide_by', $user->id)
                ->where('statut', 'valide')
                ->sum('montant');
        }

        return view('users.show', compact('user', 'stats'));
    }

    public function edit(User $user)
    {
        $user->load(['filieres', 'matieresEnseignees']);
        $filieres = Filiere::all();
        $matieres = Matiere::all();

        return view('users.edit', compact('user', 'filieres', 'matieres'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:stagiaire,administrateur,professeur,comptable', // ✅ AJOUT comptable
            'specialite' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'telephone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'filieres' => 'nullable|array',
            'filieres.*' => 'exists:filieres,id',
            'matieres' => 'nullable|array',
            'matieres.*' => 'exists:matieres,id',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'specialite' => $validated['specialite'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'telephone' => $validated['telephone'] ?? null,
            'is_active' => $validated['is_active'] ?? $user->is_active,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Si professeur, mettre à jour filières et matières
        if ($user->role === 'professeur') {
            $syncFilieres = [];
            if (!empty($validated['filieres'])) {
                foreach ($validated['filieres'] as $filiere_id) {
                    $syncFilieres[$filiere_id] = [
                        'created_by' => Auth::id(),
                        'is_active' => true,
                        'date_assignation' => now(),
                    ];
                }
            }
            $user->filieres()->sync($syncFilieres);

            $syncMatieres = [];
            if (!empty($validated['matieres'])) {
                $filiere_id = $validated['filieres'][0] ?? null;
                foreach ($validated['matieres'] as $matiere_id) {
                    $syncMatieres[$matiere_id] = [
                        'filiere_id' => $filiere_id,
                        'assigned_by' => Auth::id(),
                        'is_active' => true,
                        'date_assignation' => now(),
                    ];
                }
            }
            $user->matieresEnseignees()->sync($syncMatieres);
        }

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        // Stagiaire : mêmes règles que la fiche (bloqué s'il a des paiements ou des bulletins)
        if ($user->role === 'stagiaire' && $user->stagiaire) {
            try {
                app(StagiaireService::class)->supprimer($user->stagiaire);
            } catch (ValidationException $e) {
                return back()->with('error', $e->validator->errors()->first() . ' Vous pouvez aussi désactiver le compte.');
            }
            return redirect()->route('users.index')->with('success', 'Stagiaire et compte supprimés.');
        }

        // Professeur : son planning et ses notes sont l'historique de l'établissement
        if ($user->role === 'professeur'
            && (\App\Models\Planning::where('professeur_id', $user->id)->exists()
                || \App\Models\Note::where('created_by', $user->id)->exists())) {
            return back()->with('error', 'Ce professeur a des séances ou des notes enregistrées : désactivez son compte au lieu de le supprimer.');
        }

        // Ne jamais supprimer le dernier administrateur actif
        if ($user->role === 'administrateur'
            && User::where('role', 'administrateur')->where('is_active', true)->where('id', '!=', $user->id)->doesntExist()) {
            return back()->with('error', 'Impossible de supprimer le dernier administrateur actif.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé avec succès.');
    }

    public function toggleActive(User $user)
    {
        $newStatus = !$user->is_active;

        if (!$newStatus && $user->id === Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $user->update([
            'is_active' => $newStatus,
            'activated_at' => $newStatus ? now() : null,
            'activated_by' => $newStatus ? Auth::id() : null,
        ]);

        // Notification : Notifier l'utilisateur selon l'action
        if ($newStatus) {
            $user->notify(new AccountActivated());
        } else {
            $user->notify(new AccountDeactivated());
        }

        $status = $newStatus ? 'activé' : 'désactivé';

        return redirect()->back()
            ->with('success', "Utilisateur {$status} avec succès.");
    }
}