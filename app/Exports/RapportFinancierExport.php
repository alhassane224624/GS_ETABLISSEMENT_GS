<?php

namespace App\Exports;

use App\Models\Paiement;
use App\Support\Etablissement;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Rapport financier Excel : Synthèse, Encaissements, Échéances, Retards.
 * Les montants sont des nombres (sommes, filtres et tris possibles dans Excel).
 */
class RapportFinancierExport implements WithMultipleSheets
{
    public function __construct(private array $d) {}

    public function sheets(): array
    {
        $d = $this->d;
        $methodes = Paiement::METHODES;
        $nom = fn ($s) => trim(($s->nom ?? '') . ' ' . ($s->prenom ?? ''));

        // --- Synthèse
        $synthese = [
            [Etablissement::get('nom')],
            ['Rapport financier du ' . $d['dateDebut']->format('d/m/Y') . ' au ' . $d['dateFin']->format('d/m/Y')],
            ['Filière : ' . ($d['filiere']->nom ?? 'toutes')],
            [],
            ['Indicateur', 'Valeur'],
            ['Total encaissé', (float) $d['stats']['total_encaisse']],
            ['Attendu sur la période (après remises)', (float) $d['stats']['total_attendu']],
            ['Remises accordées sur la période', (float) $d['stats']['total_remises']],
            ['Taux de recouvrement (%)', (float) $d['stats']['taux_recouvrement']],
            ['Impayés à ce jour', (float) $d['stats']['total_impayes']],
            ['Échéances en retard', (int) $d['stats']['nb_retards']],
            [],
            ['Mode de règlement', 'Montant'],
        ];
        foreach ($d['stats']['par_methode'] as $m => $montant) {
            $synthese[] = [$methodes[$m] ?? $m, (float) $montant];
        }
        $synthese[] = [];
        $ligneFiliere = count($synthese) + 1;
        $synthese[] = ['Filière', 'Montant encaissé', 'Nombre de paiements'];
        foreach ($d['par_filiere'] as $filiere => $l) {
            $synthese[] = [$filiere, $l['montant'], $l['nombre']];
        }

        // --- Encaissements
        $encaissements = [['Date', 'N° transaction', 'Matricule', 'Stagiaire', 'Filière', 'Échéances réglées', 'Mode', 'Référence', 'Montant (DH)']];
        foreach ($d['paiements'] as $p) {
            $encaissements[] = [
                $p->date_paiement->format('d/m/Y'), $p->numero_transaction, $p->stagiaire->matricule ?? '',
                $nom($p->stagiaire), $p->stagiaire->filiere->nom ?? '', $p->echeanciers->pluck('titre')->implode(', '),
                $p->methode_libelle, $p->reference_externe ?? '', (float) $p->montant,
            ];
        }
        $encaissements[] = ['', '', '', '', '', '', '', 'TOTAL', (float) $d['paiements']->sum('montant')];

        // --- Échéances
        $echeances = [['Date', 'Matricule', 'Stagiaire', 'Filière', 'Échéance', 'Type', 'Montant', 'Remise', 'Dû net', 'Réglé', 'Reste', 'Statut']];
        foreach ($d['echeanciers'] as $e) {
            $echeances[] = [
                $e->date_echeance->format('d/m/Y'), $e->stagiaire->matricule ?? '', $nom($e->stagiaire),
                $e->stagiaire->filiere->nom ?? '', $e->titre, $e->type_libelle, (float) $e->montant,
                (float) $e->montant_remise, $e->montant_net, (float) $e->montant_paye, (float) $e->montant_restant, $e->statut_libelle,
            ];
        }

        // --- Retards
        $retards = [['Échéance du', 'Jours de retard', 'Matricule', 'Stagiaire', 'Filière', 'Échéance', 'Reste dû (DH)']];
        foreach ($d['retards'] as $e) {
            $retards[] = [
                $e->date_echeance->format('d/m/Y'), (int) $e->date_echeance->diffInDays(now()), $e->stagiaire->matricule ?? '',
                $nom($e->stagiaire), $e->stagiaire->filiere->nom ?? '', $e->titre, (float) $e->montant_restant,
            ];
        }

        return [
            new FeuilleRapport('Synthèse', $synthese, ['B' => '#,##0.00'], [5, 13, $ligneFiliere]),
            new FeuilleRapport('Encaissements', $encaissements, ['I' => '#,##0.00'], [1]),
            new FeuilleRapport('Échéances', $echeances, ['G' => '#,##0.00', 'H' => '#,##0.00', 'I' => '#,##0.00', 'J' => '#,##0.00', 'K' => '#,##0.00'], [1]),
            new FeuilleRapport('Retards', $retards, ['G' => '#,##0.00'], [1]),
        ];
    }
}

class FeuilleRapport implements FromArray, WithTitle, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    public function __construct(
        private string $titre,
        private array $lignes,
        private array $formats = [],
        private array $lignesEntete = [1]
    ) {}

    public function array(): array
    {
        return $this->lignes;
    }

    public function title(): string
    {
        return $this->titre;
    }

    public function columnFormats(): array
    {
        return $this->formats;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [];
        foreach ($this->lignesEntete as $ligne) {
            $styles[$ligne] = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']],
            ];
        }
        if ($this->titre === 'Synthèse') {
            $styles[1] = ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '4F46E5']]];
        } else {
            $sheet->freezePane('A2');
        }
        return $styles;
    }
}
