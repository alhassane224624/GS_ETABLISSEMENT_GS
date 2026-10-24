<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Annonces / notes circulaires publiées par l'administration ou la comptabilité, et suivi des lectures.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annonces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titre');
            $table->text('contenu');
            $table->string('importance', 20)->default('info'); // info, important, urgent
            $table->json('roles');                              // rôles destinataires
            $table->foreignId('filiere_id')->nullable()->constrained()->nullOnDelete(); // limite à une filière
            $table->json('classe_ids')->nullable();             // limite à des classes
            $table->string('fichier_path')->nullable();
            $table->string('fichier_nom')->nullable();
            $table->dateTime('publie_le');
            $table->dateTime('expire_le')->nullable();
            $table->boolean('epinglee')->default(false);
            $table->dateTime('notifiee_at')->nullable();        // notifications envoyées
            $table->timestamps();

            $table->index(['publie_le', 'expire_le']);
        });

        Schema::create('annonce_lectures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annonce_id')->constrained('annonces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('lu_at');

            $table->unique(['annonce_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annonce_lectures');
        Schema::dropIfExists('annonces');
    }
};
