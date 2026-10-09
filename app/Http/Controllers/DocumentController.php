<?php

namespace App\Http\Controllers;

use App\Models\DocumentDelivre;
use App\Models\Stagiaire;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $service) {}

    /** Registre des documents délivrés (administration) */
    public function index(Request $request)
    {
        $documents = DocumentDelivre::with(['stagiaire', 'auteur'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->string('search');
                $q->where(fn ($qq) => $qq->where('numero', 'like', "%{$s}%")
                    ->orWhere('code_verification', $s)
                    ->orWhereHas('stagiaire', fn ($sq) => $sq->where('nom', 'like', "%{$s}%")->orWhere('prenom', 'like', "%{$s}%")->orWhere('matricule', 'like', "%{$s}%")));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('documents.index', compact('documents'));
    }

    /** Délivrer un nouveau document à un stagiaire (administration) */
    public function delivrer(Request $request, Stagiaire $stagiaire)
    {
        $data = $request->validate(['type' => ['required', Rule::in(array_keys(DocumentDelivre::TYPES))]]);

        try {
            $document = $this->service->delivrer($data['type'], $stagiaire);
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return $this->service->pdf($document)->download($this->nomFichier($document));
    }

    /** Réimprimer un document déjà délivré (identique, même numéro) */
    public function telecharger(DocumentDelivre $document)
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            abort_unless($user->stagiaire && (int) $user->stagiaire->id === (int) $document->stagiaire_id, 403);
        }
        abort_unless($document->estValide(), 410, 'Ce document a été annulé.');

        return $this->service->pdf($document)->download($this->nomFichier($document));
    }

    /** Annuler un document (perdu, erreur) : la vérification le signalera comme non valide */
    public function annuler(DocumentDelivre $document)
    {
        $document->update(['annule_at' => now()]);
        return back()->with('success', "Document {$document->numero} annulé.");
    }

    /** Espace stagiaire : attestation de scolarité en libre-service */
    public function monAttestation()
    {
        $stagiaire = auth()->user()->stagiaire;
        abort_unless($stagiaire, 403);

        // Une attestation par année suffit : on réutilise celle déjà délivrée
        $document = DocumentDelivre::where('stagiaire_id', $stagiaire->id)
            ->where('type', 'attestation')
            ->whereNull('annule_at')
            ->whereHas('anneeScolaire', fn ($q) => $q->where('is_active', true))
            ->latest()->first();

        if (!$document) {
            try {
                $document = $this->service->delivrer('attestation', $stagiaire);
            } catch (ValidationException $e) {
                return back()->with('error', $e->validator->errors()->first());
            }
        }

        return $this->service->pdf($document)->download($this->nomFichier($document));
    }

    /** Page publique : vérifier l'authenticité d'un document à partir de son code */
    public function verifier(?string $code = null)
    {
        $code = $code ?? request('code');
        $document = $code ? DocumentDelivre::with('stagiaire')->where('code_verification', strtoupper(trim($code)))->first() : null;

        return view('documents.verifier', compact('code', 'document'));
    }

    private function nomFichier(DocumentDelivre $document): string
    {
        return $document->numero . '_' . str($document->contenu['nom'] ?? 'stagiaire')->slug() . '.pdf';
    }
}
