<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Refonte de la logique de paiement :
 *  - une échéance a un TYPE (inscription, mensualité, examen, autre) et une REMISE
 *    montant_restant = montant - montant_remise - montant_paye
 *  - une remise précise à quoi elle s'applique (mensualités seulement ou tout)
 *  - seuls les paiements VALIDÉS sont imputés sur les échéances
 *    → réparation des données existantes (imputations de paiements en attente / refusés supprimées)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('echeanciers', function (Blueprint $table) {
            if (!Schema::hasColumn('echeanciers', 'type')) {
                $table->string('type', 20)->default('mensualite')->after('titre');
            }
            if (!Schema::hasColumn('echeanciers', 'montant_remise')) {
                $table->decimal('montant_remise', 10, 2)->default(0)->after('montant');
            }
        });

        Schema::table('remises', function (Blueprint $table) {
            if (!Schema::hasColumn('remises', 'porte')) {
                // mensualite = s'applique aux mensualités ; tout = à toutes les échéances
                $table->string('porte', 20)->default('mensualite')->after('valeur');
            }
        });

        // Typage des échéances existantes d'après leur titre
        DB::table('echeanciers')->where('titre', 'like', '%inscription%')->update(['type' => 'inscription']);
        DB::table('echeanciers')->where('titre', 'like', '%examen%')->update(['type' => 'examen']);

        // Supprimer les imputations des paiements non validés (ils seront imputés à la validation)
        $nonValides = DB::table('paiements')->where('statut', '!=', 'valide')->pluck('id');
        if ($nonValides->isNotEmpty()) {
            DB::table('echeancier_paiement')->whereIn('paiement_id', $nonValides)->delete();
        }

        // Recalcul complet des échéances à partir des seules imputations validées
        $aujourdhui = now()->toDateString();
        DB::table('echeanciers')->orderBy('id')->chunkById(500, function ($echeances) use ($aujourdhui) {
            foreach ($echeances as $e) {
                $paye = (float) DB::table('echeancier_paiement')->where('echeancier_id', $e->id)->sum('montant_affecte');
                $restant = max(0, round($e->montant - $e->montant_remise - $paye, 2));

                $statut = $restant <= 0 ? 'paye'
                    : ($e->date_echeance < $aujourdhui ? 'en_retard'
                    : ($paye > 0 ? 'paye_partiel' : 'impaye'));

                DB::table('echeanciers')->where('id', $e->id)->update([
                    'montant_paye' => $paye,
                    'montant_restant' => $restant,
                    'statut' => $statut,
                ]);
            }
        });

        // Soldes des stagiaires
        DB::table('stagiaires')->orderBy('id')->chunkById(500, function ($stagiaires) {
            foreach ($stagiaires as $s) {
                $e = DB::table('echeanciers')->where('stagiaire_id', $s->id);
                $du = (float) (clone $e)->sum(DB::raw('montant - montant_remise'));
                $paye = (float) (clone $e)->sum('montant_paye');
                $reste = (float) (clone $e)->sum('montant_restant');
                $retard = (clone $e)->where('statut', 'en_retard')->exists();

                DB::table('stagiaires')->where('id', $s->id)->update([
                    'total_a_payer' => $du,
                    'total_paye' => $paye,
                    'solde_restant' => $reste,
                    'statut_paiement' => $du <= 0 ? 'en_attente' : ($reste <= 0 ? 'a_jour' : ($retard ? 'en_retard' : 'en_cours')),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('echeanciers', function (Blueprint $table) {
            $table->dropColumn(['type', 'montant_remise']);
        });
        Schema::table('remises', function (Blueprint $table) {
            $table->dropColumn('porte');
        });
    }
};
