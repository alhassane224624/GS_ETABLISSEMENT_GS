<?php
// ============================================================================
// DatabaseSeeder.php - POINT D'ENTRÉE PRINCIPAL (ORDRE CORRECT)
// ============================================================================

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Exécuter les seeders dans l'ordre correct
     * ⚠️ L'ordre est CRITIQUE - respecter les dépendances!
     */
    public function run(): void
    {
        // Jeu de démonstration complet et cohérent (toutes les fonctionnalités).
        // Les anciens seeders restent disponibles : php artisan db:seed --class=NomDuSeeder
        $this->call(DemoSeeder::class);
    }

    /**
     * Afficher les identifiants de connexion
     */
    private function displayLoginCredentials(): void
    {
        $this->command->line('🔐 IDENTIFIANTS DE CONNEXION:');
        $this->command->line('');
        $this->command->line('👨‍💼 ADMINISTRATEUR:');
        $this->command->line('   Email:    admin@emsi.ma');
        $this->command->line('   Mot de passe: password123');
        $this->command->line('');
        $this->command->line('👨‍🏫 PROFESSEURS:');
        $this->command->line('   ahmed.bennani@emsi.ma');
        $this->command->line('   fatima.karim@emsi.ma');
        $this->command->line('   hassan.idrissi@emsi.ma');
        $this->command->line('   laila.moumine@emsi.ma');
        $this->command->line('   karim.aziz@emsi.ma');
        $this->command->line('   Mot de passe: password123 (pour tous)');
        $this->command->line('');
        $this->command->line('📊 STATISTIQUES CRÉÉES:');
        $this->command->line('   • 1 Administrateur + 5 Professeurs');
        $this->command->line('   • 7 Filières avec 2 niveaux chacune');
        $this->command->line('   • 14 Classes');
        $this->command->line('   • 10 Matières');
        $this->command->line('   • 8 Salles');
        $this->command->line('   • ~350 Stagiaires');
        $this->command->line('   • ~2800 Notes');
        $this->command->line('   • ~350 Absences');
        $this->command->line('   • ~210 Plannings');
        $this->command->line('   • ~350 Bulletins');
        $this->command->line('   • 50 Messages');
        $this->command->line('');
    }
}