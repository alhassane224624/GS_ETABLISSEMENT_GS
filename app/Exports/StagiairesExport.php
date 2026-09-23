<?php

namespace App\Exports;

use App\Models\Stagiaire;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StagiairesExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithEvents
{
    protected $filiereId;
    protected $classeId;

    /**
     * Constructeur
     */
    public function __construct($filiereId = null, $classeId = null)
    {
        $this->filiereId = $filiereId;
        $this->classeId = $classeId;
    }

    /**
     * Récupérer les stagiaires à exporter
     */
    public function collection()
    {
        $query = Stagiaire::with([
            'filiere',
            'classe',
            'niveau',
        ])
        ->where('statut', 'actif')
        ->where('is_active', true);

        /*
        |--------------------------------------------------------------------------
        | FILTRE FILIÈRE
        |--------------------------------------------------------------------------
        */
        if (!empty($this->filiereId)) {
            $query->where('filiere_id', $this->filiereId);
        }

        /*
        |--------------------------------------------------------------------------
        | FILTRE CLASSE
        |--------------------------------------------------------------------------
        */
        if (!empty($this->classeId)) {
            $query->where('classe_id', $this->classeId);
        }

        return $query
            ->orderBy('nom', 'asc')
            ->orderBy('prenom', 'asc')
            ->get();
    }

    /**
     * En-têtes du fichier Excel
     */
    public function headings(): array
    {
        return [
            'Matricule',
            'Nom',
            'Prénom',
            'Email',
            'Téléphone',
            'Date de naissance',
            'Filière',
            'Classe',
            'Niveau',
            'Statut',
            'Date d’inscription',
        ];
    }

    /**
     * Formatage de chaque ligne
     */
    public function map($stagiaire): array
    {
        return [
            $stagiaire->matricule ?? 'N/A',

            $stagiaire->nom ?? 'N/A',

            $stagiaire->prenom ?? 'N/A',

            $stagiaire->email ?? 'N/A',

            $stagiaire->telephone ?? 'N/A',

            $stagiaire->date_naissance
                ? \Carbon\Carbon::parse($stagiaire->date_naissance)->format('d/m/Y')
                : 'N/A',

            optional($stagiaire->filiere)->nom ?? 'N/A',

            optional($stagiaire->classe)->nom ?? 'N/A',

            optional($stagiaire->niveau)->nom ?? 'N/A',

            $stagiaire->statut
                ? ucfirst($stagiaire->statut)
                : 'N/A',

            $stagiaire->date_inscription
                ? \Carbon\Carbon::parse($stagiaire->date_inscription)->format('d/m/Y')
                : (
                    $stagiaire->created_at
                        ? \Carbon\Carbon::parse($stagiaire->created_at)->format('d/m/Y')
                        : 'N/A'
                ),
        ];
    }

    /**
     * Largeur des colonnes
     */
    public function columnWidths(): array
    {
        return [
            'A' => 18, // Matricule
            'B' => 22, // Nom
            'C' => 22, // Prénom
            'D' => 32, // Email
            'E' => 18, // Téléphone
            'F' => 20, // Date naissance
            'G' => 28, // Filière
            'H' => 25, // Classe
            'I' => 20, // Niveau
            'J' => 15, // Statut
            'K' => 22, // Date inscription
        ];
    }

    /**
     * Styles de base
     */
    public function styles(Worksheet $sheet)
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | EN-TÊTE
            |--------------------------------------------------------------------------
            */
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => [
                        'rgb' => 'FFFFFF',
                    ],
                ],

                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => '2563EB',
                    ],
                ],

                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
    }

    /**
     * Événements Excel après génération de la feuille
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                /*
                |--------------------------------------------------------------------------
                | Déterminer la dernière ligne
                |--------------------------------------------------------------------------
                */
                $highestRow = $sheet->getHighestRow();

                /*
                |--------------------------------------------------------------------------
                | Figer la première ligne
                |--------------------------------------------------------------------------
                */
                $sheet->freezePane('A2');

                /*
                |--------------------------------------------------------------------------
                | Activer le filtre automatique
                |--------------------------------------------------------------------------
                */
                if ($highestRow >= 1) {
                    $sheet->setAutoFilter(
                        'A1:K' . $highestRow
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Hauteur de l'en-tête
                |--------------------------------------------------------------------------
                */
                $sheet->getRowDimension(1)->setRowHeight(28);

                /*
                |--------------------------------------------------------------------------
                | Bordures du tableau
                |--------------------------------------------------------------------------
                */
                if ($highestRow >= 1) {

                    $sheet
                        ->getStyle('A1:K' . $highestRow)
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(
                            Border::BORDER_THIN
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Alignement général
                |--------------------------------------------------------------------------
                */
                if ($highestRow >= 2) {

                    $sheet
                        ->getStyle('A2:K' . $highestRow)
                        ->getAlignment()
                        ->setVertical(
                            Alignment::VERTICAL_CENTER
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Centrer certaines colonnes
                |--------------------------------------------------------------------------
                */
                $sheet
                    ->getStyle('A2:A' . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    );

                $sheet
                    ->getStyle('E2:F' . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    );

                $sheet
                    ->getStyle('I2:K' . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    );

                /*
                |--------------------------------------------------------------------------
                | Retour à la ligne pour les longues valeurs
                |--------------------------------------------------------------------------
                */
                $sheet
                    ->getStyle('A1:K' . $highestRow)
                    ->getAlignment()
                    ->setWrapText(true);
            },
        ];
    }
}