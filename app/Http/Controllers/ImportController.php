<?php

namespace App\Http\Controllers;

use App\Models\Stagiaire;
use App\Models\Filiere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StagiairesImport;

class ImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function showImportForm()
    {
        $filieres = Filiere::all();
        return view('imports.stagiaires', compact('filieres'));
    }

    public function importStagiaires(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls|max:5120',
            'filiere_id' => 'nullable|exists:filieres,id'
        ]);

        try {
            $import = new StagiairesImport($request->filiere_id ? (int) $request->filiere_id : null);
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
        }

        // Rapport CSV : identifiants des comptes créés + lignes en erreur
        $nom = 'rapport_import_' . now()->format('Y-m-d_H-i') . '.csv';

        return response()->streamDownload(function () use ($import) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM pour Excel
            fputcsv($out, ['Ligne', 'Nom', 'Prénom', 'E-mail', 'Matricule', 'Mot de passe provisoire', 'Statut'], ';');
            foreach ($import->getRapport() as $ligne) {
                fputcsv($out, $ligne, ';');
            }
            fclose($out);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename=template_stagiaires.csv'
        ];

        $template = "nom,prenom,email,telephone,matricule,filiere_nom\n";
        $template .= "DUPONT,Jean,jean.dupont@exemple.com,0600000000,,Informatique\n";
        $template .= "MARTIN,Marie,marie.martin@exemple.com,0611111111,,Gestion\n";

        return response($template, 200, $headers);
    }
}

