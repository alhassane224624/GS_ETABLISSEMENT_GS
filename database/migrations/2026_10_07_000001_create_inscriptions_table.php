<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Inscriptions annuelles : un stagiaire a UNE inscription par année scolaire
 * (filière, niveau, classe de l'année) + la décision de fin d'année.
 * La classe "courante" du stagiaire (stagiaires.classe_id) = son inscription de l'année active.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stagiaire_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->restrictOnDelete();
            $table->foreignId('filiere_id')->constrained()->restrictOnDelete();
            $table->foreignId('niveau_id')->nullable()->constrained('niveaux')->nullOnDelete();
            $table->foreignId('classe_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date_inscription');
            // inscrit : en cours d'année ; termine : année close ; abandon : a quitté en cours d'année
            $table->string('statut', 20)->default('inscrit');
            // Décision de fin d'année (délibération)
            $table->string('decision', 20)->nullable(); // admis, redouble, diplome, exclu
            $table->decimal('moyenne_annuelle', 5, 2)->nullable();
            $table->text('observation')->nullable();
            $table->timestamp('decide_at')->nullable();
            $table->foreignId('decide_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['stagiaire_id', 'annee_scolaire_id']);
            $table->index(['annee_scolaire_id', 'classe_id', 'statut']);
        });

        // Reprise de l'existant : chaque stagiaire est inscrit dans l'année de sa classe actuelle
        // (ou l'année active s'il n'a pas de classe)
        $anneeActive = DB::table('annee_scolaires')->where('is_active', true)->value('id')
            ?? DB::table('annee_scolaires')->orderByDesc('debut')->value('id');

        DB::table('stagiaires')->orderBy('id')->chunkById(500, function ($stagiaires) use ($anneeActive) {
            foreach ($stagiaires as $s) {
                $annee = $s->classe_id
                    ? DB::table('classes')->where('id', $s->classe_id)->value('annee_scolaire_id')
                    : $anneeActive;
                if (!$annee) {
                    continue;
                }

                DB::table('inscriptions')->insertOrIgnore([
                    'stagiaire_id' => $s->id,
                    'annee_scolaire_id' => $annee,
                    'filiere_id' => $s->filiere_id,
                    'niveau_id' => $s->niveau_id,
                    'classe_id' => $s->classe_id,
                    'date_inscription' => $s->date_inscription ?? now()->toDateString(),
                    'statut' => in_array($s->statut, ['abandonne', 'transfere']) ? 'abandon' : 'inscrit',
                    'created_by' => $s->created_by ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        // Effectifs des classes = inscriptions en cours
        foreach (DB::table('classes')->pluck('id') as $classeId) {
            $n = DB::table('inscriptions')->where('classe_id', $classeId)->where('statut', 'inscrit')->count();
            $max = (int) DB::table('classes')->where('id', $classeId)->value('effectif_max');
            DB::table('classes')->where('id', $classeId)->update([
                'effectif_max' => max($max, $n), // ne jamais violer la contrainte d'effectif sur l'existant
                'effectif_actuel' => $n,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};
