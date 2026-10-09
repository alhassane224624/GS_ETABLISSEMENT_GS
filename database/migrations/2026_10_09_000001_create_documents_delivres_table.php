<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registre des documents administratifs délivrés (attestation, certificat, carte).
 * Le contenu est figé au moment de la délivrance (snapshot) : un document réimprimé est identique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents_delivres', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // attestation, certificat, carte
            $table->string('numero', 30)->unique();
            $table->string('code_verification', 16)->unique();
            $table->foreignId('stagiaire_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->nullable()->constrained('annee_scolaires')->nullOnDelete();
            $table->json('contenu');
            $table->foreignId('delivre_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('annule_at')->nullable();
            $table->timestamps();

            $table->index(['stagiaire_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents_delivres');
    }
};
