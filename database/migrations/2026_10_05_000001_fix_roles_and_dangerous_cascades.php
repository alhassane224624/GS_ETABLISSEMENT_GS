<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrections structurelles :
 *  1. Ajout du rôle "comptable" dans l'enum users.role
 *  2. Suppression des cascades qui effaçaient des données métier
 *     quand on supprimait un utilisateur (admin/prof) ou une classe.
 *
 * SQL brut (MySQL/MariaDB) pour ne pas dépendre de doctrine/dbal.
 */
return new class extends Migration
{
    /** [table, colonne] : created_by devient nullable + ON DELETE SET NULL */
    private array $auteurs = [
        ['stagiaires', 'created_by'],
        ['notes', 'created_by'],
        ['absences', 'created_by'],
        ['plannings', 'created_by'],
        ['bulletins', 'created_by'],
        ['remises', 'created_by'],
        ['professeur_filiere', 'created_by'],
        ['professeur_matiere', 'assigned_by'],
    ];

    public function up(): void
    {
        // 1. Rôle comptable
        DB::statement("ALTER TABLE users MODIFY role ENUM('administrateur','comptable','professeur','stagiaire') NOT NULL DEFAULT 'stagiaire'");

        // 2. Auteurs : SET NULL au lieu de CASCADE
        foreach ($this->auteurs as [$table, $col]) {
            $this->refaireFk($table, $col, 'users', 'SET NULL', true);
        }

        // 3. Supprimer une classe ne doit plus effacer les notes
        $this->refaireFk('notes', 'classe_id', 'classes', 'SET NULL', true);

        // 4. Une classe qui a des bulletins ne peut pas être supprimée
        $this->refaireFk('bulletins', 'classe_id', 'classes', 'RESTRICT', false);

        // 5. Supprimer une période ne doit plus effacer les absences
        $this->refaireFk('absences', 'periode_id', 'periodes', 'SET NULL', true);
    }

    public function down(): void
    {
        foreach ($this->auteurs as [$table, $col]) {
            $this->refaireFk($table, $col, 'users', 'CASCADE', true);
        }
        $this->refaireFk('notes', 'classe_id', 'classes', 'CASCADE', true);
        $this->refaireFk('bulletins', 'classe_id', 'classes', 'CASCADE', false);
        $this->refaireFk('absences', 'periode_id', 'periodes', 'CASCADE', true);

        DB::statement("UPDATE users SET role = 'administrateur' WHERE role = 'comptable'");
        DB::statement("ALTER TABLE users MODIFY role ENUM('administrateur','professeur','stagiaire') NOT NULL DEFAULT 'stagiaire'");
    }

    private function refaireFk(string $table, string $col, string $ref, string $onDelete, bool $nullable): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $col)) {
            return;
        }

        // Retrouver le nom réel de la contrainte
        $fk = DB::selectOne("
            SELECT CONSTRAINT_NAME AS name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table, $col]);

        if ($fk) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->name}`");
        }

        $null = $nullable ? 'NULL' : 'NOT NULL';
        DB::statement("ALTER TABLE `{$table}` MODIFY `{$col}` BIGINT UNSIGNED {$null}");
        DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$table}_{$col}_foreign`
            FOREIGN KEY (`{$col}`) REFERENCES `{$ref}`(`id`) ON DELETE {$onDelete}");
    }
};
