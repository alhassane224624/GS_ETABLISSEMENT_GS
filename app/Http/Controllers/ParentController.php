<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\Bulletin;
use App\Models\Paiement;
use App\Models\Planning;
use App\Models\Stagiaire;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Portail parents (lecture seule) + gestion des accès parents par l'administration.
 */
class ParentController extends Controller
{
    // ------------------------------ Portail ------------------------------

    public function index()
    {
        $enfants = Auth::user()->enfants()->with(['classe', 'filiere'])->get()->map(function (Stagiaire $s) {
            $s->resume = [
                'dernier_bulletin' => $s->bulletins()->whereNotNull('validated_at')->with('periode')->latest('validated_at')->first(),
                'absences_non_justifiees' => $s->absences()->where('justifiee', false)->count(),
                'reste' => (float) $s->echeanciers()->sum('montant_restant'),
                'retards' => $s->echeanciers()->where('montant_restant', '>', 0)->whereDate('date_echeance', '<', now()->toDateString())->count(),
            ];
            return $s;
        });

        // Un seul enfant : on va directement à sa fiche
        if ($enfants->count() === 1) {
            return redirect()->route('parent.enfant', $enfants->first());
        }

        return view('parent.index', compact('enfants'));
    }

    public function enfant(Stagiaire $stagiaire)
    {
        $this->autoriser($stagiaire);
        $stagiaire->load(['classe', 'filiere']);

        return view('parent.enfant', [
            'stagiaire' => $stagiaire,
            'enfants' => Auth::user()->enfants()->get(['stagiaires.id', 'nom', 'prenom']),
            'bulletins' => $stagiaire->bulletins()->whereNotNull('validated_at')->with('periode')->latest('validated_at')->get(),
            'absences' => $stagiaire->absences()->with('planning.matiere')->latest('date')->limit(30)->get(),
            'echeances' => $stagiaire->echeanciers()->orderBy('date_echeance')->get(),
            'paiements' => $stagiaire->paiements()->where('statut', 'valide')->latest('date_paiement')->limit(20)->get(),
            'aFaire' => Planning::with('matiere')->where('classe_id', $stagiaire->classe_id)->whereNotNull('devoirs')
                ->whereDate('devoirs_pour', '>=', now()->toDateString())->orderBy('devoirs_pour')->get(),
        ]);
    }

    public function bulletin(Stagiaire $stagiaire, Bulletin $bulletin)
    {
        $this->autoriser($stagiaire);
        abort_unless((int) $bulletin->stagiaire_id === (int) $stagiaire->id && $bulletin->validated_at, 403);

        $bulletin->loadMissing(['stagiaire', 'periode.anneeScolaire', 'classe.filiere']);
        return Pdf::loadView('bulletins.pdf', compact('bulletin'))->download('bulletin_' . $stagiaire->matricule . '.pdf');
    }

    public function recu(Stagiaire $stagiaire, Paiement $paiement)
    {
        $this->autoriser($stagiaire);
        abort_unless((int) $paiement->stagiaire_id === (int) $stagiaire->id && $paiement->statut === 'valide', 403);

        $paiement->load(['stagiaire.filiere', 'stagiaire.classe', 'validateur', 'user', 'echeanciers']);
        return Pdf::loadView('paiements.recu', compact('paiement'))->setPaper('a4')->download('recu_' . $paiement->numero_transaction . '.pdf');
    }

    private function autoriser(Stagiaire $stagiaire): void
    {
        abort_unless(Auth::user()->enfants()->where('stagiaires.id', $stagiaire->id)->exists(), 403);
    }

    // ------------------------------ Administration ------------------------------

    /** Crée le compte parent (ou rattache un compte parent existant : fratrie) */
    public function lier(Request $request, Stagiaire $stagiaire)
    {
        $data = $request->validate([
            'nom'       => 'required|string|max:255',
            'email'     => 'required|email|max:255',
            'telephone' => 'nullable|string|max:20',
            'lien'      => 'required|in:pere,mere,tuteur',
        ]);

        $user = User::where('email', $data['email'])->first();
        $motDePasse = null;

        if ($user && $user->role !== 'parent') {
            return back()->with('error', 'Cet e-mail appartient déjà à un compte « ' . $user->role . ' ». Utilisez une autre adresse pour le parent.');
        }

        if (!$user) {
            $motDePasse = Str::random(10);
            $user = User::create([
                'name' => $data['nom'],
                'email' => $data['email'],
                'telephone' => $data['telephone'] ?? null,
                'password' => Hash::make($motDePasse),
                'role' => 'parent',
                'is_active' => true,
                'created_by' => Auth::id(),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $user->enfants()->syncWithoutDetaching([$stagiaire->id => ['lien' => $data['lien']]]);

        return back()->with('success', $motDePasse
            ? "Accès parent créé — identifiant : {$user->email} — mot de passe provisoire : {$motDePasse} (à communiquer, il n'apparaîtra plus)."
            : "Compte parent existant ({$user->email}) rattaché à ce stagiaire.");
    }

    public function delier(Stagiaire $stagiaire, User $parent)
    {
        abort_unless($parent->role === 'parent', 404);
        $parent->enfants()->detach($stagiaire->id);

        // Plus aucun enfant : compte désactivé
        if ($parent->enfants()->doesntExist()) {
            $parent->update(['is_active' => false]);
        }

        return back()->with('success', 'Accès parent retiré.');
    }
}
