<?php

namespace App\Imports;

use App\Models\Filiere;
use App\Models\Stagiaire;
use App\Models\User;
use App\Services\StagiaireService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Colonnes attendues : nom, prenom, email, matricule (optionnel), filiere_nom (optionnel si filière choisie)
 * Chaque ligne crée le compte + le dossier via StagiaireService.
 * Une ligne en erreur n'empêche pas les autres d'être importées.
 */
class StagiairesImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array> lignes du rapport : [ligne, nom, prenom, email, matricule, mot_de_passe, statut] */
    protected array $rapport = [];
    protected int $succes = 0;

    public function __construct(protected ?int $filiereId = null) {}

    public function collection(Collection $rows)
    {
        $service = app(StagiaireService::class);

        foreach ($rows as $index => $row) {
            $ligne = $index + 2; // +1 en-tête, +1 base 1
            $nom = trim((string) ($row['nom'] ?? ''));
            $prenom = trim((string) ($row['prenom'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $matricule = trim((string) ($row['matricule'] ?? '')) ?: null;

            try {
                if ($nom === '' || $prenom === '') {
                    throw new \RuntimeException('Nom et prénom obligatoires');
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('E-mail manquant ou invalide');
                }
                if (User::where('email', $email)->exists()) {
                    throw new \RuntimeException('E-mail déjà utilisé');
                }
                if ($matricule && Stagiaire::where('matricule', $matricule)->exists()) {
                    throw new \RuntimeException("Matricule {$matricule} déjà existant");
                }

                $filiereId = $this->filiereId;
                if (!$filiereId && !empty($row['filiere_nom'])) {
                    $filiereId = Filiere::where('nom', trim($row['filiere_nom']))->value('id')
                        ?? Filiere::where('code', trim($row['filiere_nom']))->value('id');
                }
                if (!$filiereId) {
                    throw new \RuntimeException('Filière introuvable');
                }

                [$stagiaire, $motDePasse] = $service->creer([
                    'nom'        => mb_strtoupper($nom),
                    'prenom'     => mb_convert_case($prenom, MB_CASE_TITLE),
                    'email'      => $email,
                    'matricule'  => $matricule,
                    'telephone'  => $row['telephone'] ?? null,
                    'filiere_id' => $filiereId,
                ], Auth::id());

                $this->succes++;
                $this->rapport[] = [$ligne, $stagiaire->nom, $stagiaire->prenom, $email, $stagiaire->matricule, $motDePasse, 'Importé'];
            } catch (ValidationException $e) {
                $this->rapport[] = [$ligne, $nom, $prenom, $email, $matricule, '', 'Erreur : ' . $e->validator->errors()->first()];
            } catch (\Throwable $e) {
                $this->rapport[] = [$ligne, $nom, $prenom, $email, $matricule, '', 'Erreur : ' . $e->getMessage()];
            }
        }
    }

    public function getSucces(): int
    {
        return $this->succes;
    }

    public function getRapport(): array
    {
        return $this->rapport;
    }
}
