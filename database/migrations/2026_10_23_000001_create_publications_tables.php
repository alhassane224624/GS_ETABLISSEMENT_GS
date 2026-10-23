<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Publications des professeurs (devoirs, documents, résultats), rendus des stagiaires,
 * et publication différée des notes saisies par les professeurs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            // NULL = note saisie par un professeur, pas encore visible par le stagiaire
            $table->timestamp('publie_at')->nullable()->after('commentaire');
            $table->index(['classe_id', 'matiere_id', 'publie_at']);
        });
        // Les notes existantes restent visibles
        DB::table('notes')->update(['publie_at' => DB::raw('created_at')]);

        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professeur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained()->restrictOnDelete();
            $table->string('type', 20); // devoir, document, resultats
            $table->string('titre');
            $table->text('contenu')->nullable();
            $table->string('fichier_path')->nullable();
            $table->string('fichier_nom')->nullable();
            $table->dateTime('date_limite')->nullable(); // devoirs
            $table->boolean('accepte_rendu')->default(false); // devoirs : dépôt en ligne
            $table->unsignedInteger('notes_publiees')->default(0); // résultats
            $table->timestamps();

            $table->index(['classe_id', 'type', 'created_at']);
        });

        Schema::create('rendus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_id')->constrained('publications')->cascadeOnDelete();
            $table->foreignId('stagiaire_id')->constrained()->cascadeOnDelete();
            $table->string('fichier_path');
            $table->string('fichier_nom');
            $table->text('commentaire')->nullable();
            $table->dateTime('rendu_at');
            $table->timestamps();

            $table->unique(['publication_id', 'stagiaire_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rendus');
        Schema::dropIfExists('publications');
        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex(['classe_id', 'matiere_id', 'publie_at']);
            $table->dropColumn('publie_at');
        });
    }
};
