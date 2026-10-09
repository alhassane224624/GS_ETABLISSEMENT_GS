<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Examens :
 *  - examens          : une session (normale ou rattrapage) d'une année / période
 *  - epreuves         : une matière passée par une classe (date, salle, surveillant) → séance « examen » au planning
 *  - resultats_epreuves : la note (ou l'absence) de chaque convoqué
 * Session normale : les résultats deviennent des notes « examen » (donc comptent dans les bulletins).
 * Rattrapage : les résultats remplacent la moyenne annuelle de la matière si elle est meilleure.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->foreignId('periode_id')->nullable()->constrained('periodes')->nullOnDelete();
            $table->string('nom');
            $table->string('type', 20)->default('normale'); // normale, rattrapage
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('statut', 20)->default('planifiee'); // planifiee, cloturee
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('epreuves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examens')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->foreignId('salle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('surveillant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('note_sur', 4, 1)->default(20);
            $table->foreignId('planning_id')->nullable()->constrained('plannings')->nullOnDelete();
            $table->timestamps();

            $table->unique(['examen_id', 'classe_id', 'matiere_id']);
        });

        Schema::create('resultats_epreuves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epreuve_id')->constrained('epreuves')->cascadeOnDelete();
            $table->foreignId('stagiaire_id')->constrained()->cascadeOnDelete();
            $table->decimal('note', 5, 2)->nullable();
            $table->boolean('absent')->default(false);
            $table->foreignId('note_id')->nullable()->constrained('notes')->nullOnDelete();
            $table->foreignId('saisi_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['epreuve_id', 'stagiaire_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultats_epreuves');
        Schema::dropIfExists('epreuves');
        Schema::dropIfExists('examens');
    }
};
