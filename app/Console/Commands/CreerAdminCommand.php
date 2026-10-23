<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Crée un compte administrateur (premier accès après un déploiement), sans passer par les données de démo.
 */
class CreerAdminCommand extends Command
{
    protected $signature = 'app:creer-admin';
    protected $description = 'Crée un compte administrateur';

    public function handle(): int
    {
        $nom = $this->ask('Nom complet');
        $email = $this->ask('Adresse e-mail (identifiant de connexion)');
        $motDePasse = $this->secret('Mot de passe (12 caractères minimum)');
        $confirmation = $this->secret('Confirmez le mot de passe');

        $validateur = Validator::make(
            ['nom' => $nom, 'email' => $email, 'password' => $motDePasse, 'password_confirmation' => $confirmation],
            [
                'nom' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
            ]
        );

        if ($validateur->fails()) {
            foreach ($validateur->errors()->all() as $erreur) {
                $this->error($erreur);
            }
            return self::FAILURE;
        }

        $admin = User::create([
            'name' => $nom,
            'email' => $email,
            'password' => Hash::make($motDePasse),
            'role' => 'administrateur',
            'is_active' => true,
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();

        $this->info("Administrateur créé : {$email}. Vous pouvez vous connecter.");

        return self::SUCCESS;
    }
}
