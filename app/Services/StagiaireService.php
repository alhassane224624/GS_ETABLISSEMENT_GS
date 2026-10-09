<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Echeancier;
use App\Models\Stagiaire;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Toute la logique de cycle de vie d'un stagiaire au même endroit
 * (création manuelle, inscription en ligne, import Excel).
 */
class StagiaireService
{
    /**
     * Crée le compte utilisateur + le dossier stagiaire dans une transaction.
     *
     * @return array{0: Stagiaire, 1: string} [stagiaire, mot de passe en clair à communiquer]
     */
    public function creer(array $data, ?int $createdBy, bool $actif = true): array
    {
        $this->verifierClasse($data['classe_id'] ?? null, $data['filiere_id'], $data['niveau_id'] ?? null);

        $motDePasse = Str::random(10);

        $stagiaire = DB::transaction(function () use ($data, $createdBy, $actif, $motDePasse) {
            $user = User::create([
                'name'              => trim($data['prenom'] . ' ' . $data['nom']),
                'email'             => $data['email'],
                'password'          => Hash::make($motDePasse),
                'role'              => 'stagiaire',
                'is_active'         => $actif,
                'created_by'        => $createdBy,
            ]);
            // Compte créé par l'établissement : e-mail considéré comme vérifié
            $user->forceFill(['email_verified_at' => now()])->save();

            $stagiaire = Stagiaire::create(array_merge($data, [
                'user_id'          => $user->id,
                'matricule'        => $data['matricule'] ?? $this->genererMatricule(),
                'date_inscription' => $data['date_inscription'] ?? now()->toDateString(),
                'is_active'        => $actif,
                'statut'           => $data['statut'] ?? ($actif ? 'actif' : 'suspendu'),
                'created_by'       => $createdBy,
            ]));

            // Inscription de l'année (l'effectif de la classe en découle)
            app(InscriptionService::class)->enregistrerCourante($stagiaire);

            $this->creerEcheanceInscription($stagiaire, (bool) ($data['frais_payes'] ?? false));

            return $stagiaire;
        });

        return [$stagiaire, $motDePasse];
    }

    /**
     * Frais d'inscription saisis sur la fiche → échéance « Frais d'inscription » sur l'année active.
     * Si la case « frais payés » est cochée, l'encaissement en espèces est enregistré (avec reçu).
     */
    private function creerEcheanceInscription(Stagiaire $stagiaire, bool $dejaPayes): void
    {
        $montant = (float) ($stagiaire->frais_inscription ?? 0);
        $annee = AnneeScolaire::where('is_active', true)->first();
        if ($montant <= 0 || !$annee) {
            return;
        }

        $echeance = Echeancier::create([
            'stagiaire_id'      => $stagiaire->id,
            'annee_scolaire_id' => $annee->id,
            'titre'             => 'Frais d\'inscription ' . $annee->nom,
            'type'              => 'inscription',
            'montant'           => $montant,
            'montant_remise'    => 0,
            'montant_paye'      => 0,
            'montant_restant'   => $montant,
            'date_echeance'     => $stagiaire->date_inscription ?? now()->toDateString(),
            'statut'            => 'impaye',
        ]);
        $echeance->recalculer();

        if ($dejaPayes) {
            app(PaiementService::class)->enregistrer([
                'stagiaire_id'     => $stagiaire->id,
                'montant'          => $montant,
                'methode_paiement' => 'especes',
                'type_paiement'    => 'inscription',
                'date_paiement'    => now()->toDateString(),
                'echeanciers'      => [$echeance->id],
                'notes_admin'      => 'Encaissé à l\'inscription',
            ]);
        }

        $stagiaire->updateSoldePaiement();
    }

    /**
     * Change la classe d'un stagiaire en gardant les effectifs cohérents.
     */
    public function changerClasse(Stagiaire $stagiaire, ?int $nouvelleClasseId, int $filiereId, ?int $niveauId): void
    {
        // Vérification seulement : après la mise à jour du stagiaire, appeler synchroniser()
        if ((int) $stagiaire->classe_id !== (int) $nouvelleClasseId) {
            $this->verifierClasse($nouvelleClasseId, $filiereId, $niveauId, $stagiaire->id);
        }
    }

    /** À appeler après toute modification de classe / filière / niveau / statut */
    public function synchroniser(Stagiaire $stagiaire): void
    {
        app(InscriptionService::class)->enregistrerCourante($stagiaire->fresh());
    }

    /**
     * Supprime un stagiaire et son compte. Refusé s'il a un historique financier
     * ou des bulletins : il faut alors changer son statut (abandonné, transféré...).
     */
    public function supprimer(Stagiaire $stagiaire): void
    {
        if ($stagiaire->paiements()->exists() || $stagiaire->bulletins()->exists()) {
            throw ValidationException::withMessages([
                'stagiaire' => 'Ce stagiaire a des paiements ou des bulletins : changez plutôt son statut (abandonné, transféré, diplômé).',
            ]);
        }

        DB::transaction(function () use ($stagiaire) {
            $classes = $stagiaire->inscriptions()->pluck('classe_id')->push($stagiaire->classe_id)->filter()->unique();
            $user = $stagiaire->user;
            $stagiaire->delete(); // ses inscriptions partent avec lui
            $user?->delete();
            $classes->each(fn ($id) => app(InscriptionService::class)->recompter($id));
        });
    }

    /**
     * Matricule unique : STG-2026-00042
     */
    public function genererMatricule(): string
    {
        $prefixe = 'STG-' . now()->format('Y') . '-';
        $dernier = Stagiaire::where('matricule', 'like', $prefixe . '%')
            ->orderByDesc('matricule')
            ->value('matricule');

        $numero = $dernier ? ((int) substr($dernier, strlen($prefixe))) + 1 : 1;

        return $prefixe . str_pad((string) $numero, 5, '0', STR_PAD_LEFT);
    }

    private function verifierClasse(?int $classeId, int $filiereId, ?int $niveauId, ?int $stagiaireId = null): void
    {
        if (!$classeId) {
            return;
        }

        $classe = Classe::findOrFail($classeId);

        if ((int) $classe->filiere_id !== (int) $filiereId) {
            throw ValidationException::withMessages(['classe_id' => 'Cette classe n\'appartient pas à la filière choisie.']);
        }
        if ($niveauId && (int) $classe->niveau_id !== (int) $niveauId) {
            throw ValidationException::withMessages(['classe_id' => 'Cette classe ne correspond pas au niveau choisi.']);
        }
        if (app(InscriptionService::class)->placesLibres($classe->id, $stagiaireId) <= 0) {
            throw ValidationException::withMessages(['classe_id' => "La classe {$classe->nom} est complète ({$classe->effectif_max} places)."]);
        }
    }
}
