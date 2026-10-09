<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 3 :
 *  - dépenses de l'établissement
 *  - rémunération des professeurs (taux horaire ou fixe) et salaires mensuels calculés sur les séances réalisées
 *  - rôle « parent » et lien parent ↔ stagiaire(s)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('administrateur','comptable','professeur','stagiaire','parent') NOT NULL DEFAULT 'stagiaire'");

        Schema::table('users', function (Blueprint $table) {
            $table->string('mode_remuneration', 10)->nullable()->after('telephone'); // horaire, fixe
            $table->decimal('taux_horaire', 8, 2)->nullable()->after('mode_remuneration');
            $table->decimal('salaire_fixe', 10, 2)->nullable()->after('taux_horaire');
        });

        Schema::create('salaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professeur_id')->constrained('users')->restrictOnDelete();
            $table->date('mois'); // 1er jour du mois
            $table->string('mode_remuneration', 10);
            $table->decimal('heures', 7, 2)->default(0);
            $table->unsignedInteger('nb_seances')->default(0);
            $table->decimal('taux_horaire', 8, 2)->nullable();
            $table->decimal('montant_base', 10, 2)->default(0);
            $table->decimal('primes', 10, 2)->default(0);
            $table->decimal('retenues', 10, 2)->default(0);
            $table->decimal('montant_net', 10, 2)->default(0);
            $table->string('statut', 10)->default('calcule'); // calcule, paye
            $table->date('date_paiement')->nullable();
            $table->string('mode_paiement', 20)->nullable();
            $table->text('observation')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['professeur_id', 'mois']);
        });

        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->string('categorie', 30);
            $table->string('libelle');
            $table->decimal('montant', 12, 2);
            $table->date('date_depense');
            $table->string('mode_paiement', 20)->default('especes');
            $table->string('reference', 100)->nullable();
            $table->string('fournisseur', 150)->nullable();
            $table->string('justificatif')->nullable();
            $table->foreignId('salaire_id')->nullable()->constrained('salaires')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date_depense', 'categorie']);
        });

        Schema::create('parent_stagiaire', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('stagiaire_id')->constrained()->cascadeOnDelete();
            $table->string('lien', 20)->default('tuteur'); // pere, mere, tuteur
            $table->timestamps();

            $table->unique(['user_id', 'stagiaire_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_stagiaire');
        Schema::dropIfExists('depenses');
        Schema::dropIfExists('salaires');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mode_remuneration', 'taux_horaire', 'salaire_fixe']);
        });
        DB::statement("UPDATE users SET is_active = 0, role = 'stagiaire' WHERE role = 'parent'");
        DB::statement("ALTER TABLE users MODIFY role ENUM('administrateur','comptable','professeur','stagiaire') NOT NULL DEFAULT 'stagiaire'");
    }
};
