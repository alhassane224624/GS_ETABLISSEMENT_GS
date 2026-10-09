<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vie scolaire :
 *  - séance (plannings) : appel fait + cahier de textes (contenu, travail à faire)
 *  - absence : liée à la séance de l'appel, retards, justification déposée par le stagiaire
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plannings', function (Blueprint $table) {
            $table->timestamp('appel_fait_at')->nullable()->after('statut');
            $table->text('contenu_seance')->nullable()->after('appel_fait_at');
            $table->text('devoirs')->nullable()->after('contenu_seance');
            $table->date('devoirs_pour')->nullable()->after('devoirs');
        });

        Schema::table('absences', function (Blueprint $table) {
            $table->foreignId('planning_id')->nullable()->after('stagiaire_id')->constrained('plannings')->nullOnDelete();
            $table->unsignedSmallInteger('retard_minutes')->nullable()->after('type');
            // Justification déposée par le stagiaire : en_attente → acceptee / refusee
            $table->string('justification_statut', 20)->nullable()->after('document_justificatif');
            $table->text('justification_motif')->nullable()->after('justification_statut');
            $table->timestamp('justification_soumise_at')->nullable()->after('justification_motif');
            $table->foreignId('justification_traitee_by')->nullable()->after('justification_soumise_at')->constrained('users')->nullOnDelete();
            $table->timestamp('justification_traitee_at')->nullable()->after('justification_traitee_by');
            $table->text('justification_commentaire')->nullable()->after('justification_traitee_at');

            $table->index(['planning_id']);
            $table->index(['justification_statut']);
        });
    }

    public function down(): void
    {
        Schema::table('absences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('planning_id');
            $table->dropConstrainedForeignId('justification_traitee_by');
            $table->dropColumn(['retard_minutes', 'justification_statut', 'justification_motif', 'justification_soumise_at', 'justification_traitee_at', 'justification_commentaire']);
        });
        Schema::table('plannings', function (Blueprint $table) {
            $table->dropColumn(['appel_fait_at', 'contenu_seance', 'devoirs', 'devoirs_pour']);
        });
    }
};
