<?php

namespace App\Services;

use App\Models\DocumentDelivre;
use App\Models\Stagiaire;
use App\Support\Etablissement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentService
{
    /**
     * Délivre un document : vérifie l'éligibilité, fige le contenu, attribue numéro + code de vérification.
     */
    public function delivrer(string $type, Stagiaire $stagiaire): DocumentDelivre
    {
        abort_unless(array_key_exists($type, DocumentDelivre::TYPES), 404);

        $stagiaire->loadMissing(['filiere', 'inscriptionActive.classe', 'inscriptionActive.niveau', 'inscriptionActive.anneeScolaire', 'inscriptionActive.filiere']);
        $inscription = $stagiaire->inscriptionActive;

        if (!$inscription || $inscription->statut !== 'inscrit' || $stagiaire->statut !== 'actif') {
            throw ValidationException::withMessages([
                'document' => 'Document impossible : le stagiaire doit être actif et inscrit pour l\'année scolaire en cours.',
            ]);
        }

        return DB::transaction(function () use ($type, $stagiaire, $inscription) {
            $annee = now()->format('Y');
            $prefixe = DocumentDelivre::PREFIXES[$type] . '-' . $annee . '-';
            $dernier = DocumentDelivre::where('numero', 'like', $prefixe . '%')->lockForUpdate()->max('numero');
            $suivant = $dernier ? ((int) substr($dernier, strlen($prefixe))) + 1 : 1;

            do {
                $code = strtoupper(Str::random(10));
            } while (DocumentDelivre::where('code_verification', $code)->exists());

            return DocumentDelivre::create([
                'type' => $type,
                'numero' => $prefixe . str_pad((string) $suivant, 5, '0', STR_PAD_LEFT),
                'code_verification' => $code,
                'stagiaire_id' => $stagiaire->id,
                'annee_scolaire_id' => $inscription->annee_scolaire_id,
                'delivre_par' => Auth::id(),
                'contenu' => [
                    'nom' => mb_strtoupper($stagiaire->nom),
                    'prenom' => $stagiaire->prenom,
                    'sexe' => $stagiaire->sexe,
                    'matricule' => $stagiaire->matricule,
                    'date_naissance' => optional($stagiaire->date_naissance)->format('d/m/Y'),
                    'lieu_naissance' => $stagiaire->lieu_naissance,
                    'filiere' => $inscription->filiere->nom ?? $stagiaire->filiere->nom ?? null,
                    'niveau' => $inscription->niveau->nom ?? null,
                    'classe' => $inscription->classe->nom ?? null,
                    'annee' => $inscription->anneeScolaire->nom ?? null,
                    'annee_fin' => optional($inscription->anneeScolaire->fin ?? null)->format('d/m/Y'),
                    'date_inscription' => optional($inscription->date_inscription)->format('d/m/Y'),
                    'photo' => $stagiaire->photo,
                ],
            ]);
        });
    }

    /** PDF d'un document délivré (reconstruit à l'identique depuis le contenu figé) */
    public function pdf(DocumentDelivre $document)
    {
        $donnees = [
            'document' => $document,
            'c' => $document->contenu,
            'etab' => Etablissement::infos(),
            'logo' => Etablissement::logoBase64(),
            'mentions' => Etablissement::mentionsLegales(),
            'photo' => $this->photoBase64($document->contenu['photo'] ?? null),
        ];

        $pdf = Pdf::loadView('documents.' . $document->type, $donnees);

        // Carte au format bancaire (85,6 × 54 mm), sinon A4
        $document->type === 'carte'
            ? $pdf->setPaper([0, 0, 242.65, 153.07])
            : $pdf->setPaper('a4');

        return $pdf;
    }

    private function photoBase64(?string $chemin): ?string
    {
        if (!$chemin || !Storage::disk('public')->exists($chemin)) {
            return null;
        }
        $mime = Storage::disk('public')->mimeType($chemin) ?: 'image/jpeg';
        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($chemin));
    }
}
