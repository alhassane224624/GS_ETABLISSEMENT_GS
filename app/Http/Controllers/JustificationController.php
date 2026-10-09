<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Justification d'absence : le stagiaire dépose un justificatif depuis son espace,
 * l'administration l'accepte (absence justifiée) ou le refuse (avec un commentaire).
 */
class JustificationController extends Controller
{
    /** Stagiaire : déposer un justificatif */
    public function deposer(Request $request, Absence $absence)
    {
        $stagiaire = Auth::user()->stagiaire;
        abort_unless($stagiaire && (int) $absence->stagiaire_id === (int) $stagiaire->id, 403);

        if (!$absence->peut_etre_justifiee) {
            return back()->with('error', 'Cette absence est déjà justifiée ou en cours d\'examen.');
        }

        $data = $request->validate([
            'justification_motif' => 'required|string|max:1000',
            'fichier'             => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'fichier.required' => 'Joignez un justificatif (certificat médical, convocation…).',
        ]);

        if ($absence->document_justificatif) {
            Storage::disk('public')->delete($absence->document_justificatif);
        }

        $absence->update([
            'document_justificatif'     => $request->file('fichier')->store('justificatifs/absences/' . $stagiaire->id, 'public'),
            'justification_motif'       => $data['justification_motif'],
            'justification_statut'      => 'en_attente',
            'justification_soumise_at'  => now(),
            'justification_traitee_by'  => null,
            'justification_traitee_at'  => null,
            'justification_commentaire' => null,
        ]);

        return back()->with('success', 'Justificatif envoyé. L\'administration va l\'examiner.');
    }

    /** Administration : justificatifs à traiter */
    public function index(Request $request)
    {
        $statut = $request->input('statut', 'en_attente');

        $absences = Absence::with(['stagiaire.classe', 'planning.matiere'])
            ->where('justification_statut', $statut)
            ->orderBy($statut === 'en_attente' ? 'justification_soumise_at' : 'justification_traitee_at', $statut === 'en_attente' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        $enAttente = Absence::where('justification_statut', 'en_attente')->count();

        return view('absences.justifications', compact('absences', 'statut', 'enAttente'));
    }

    /** Administration : accepter / refuser */
    public function traiter(Request $request, Absence $absence)
    {
        $data = $request->validate([
            'decision'    => 'required|in:acceptee,refusee',
            'commentaire' => 'nullable|required_if:decision,refusee|string|max:500',
        ], [
            'commentaire.required_if' => 'Indiquez au stagiaire pourquoi le justificatif est refusé.',
        ]);

        if ($absence->justification_statut !== 'en_attente') {
            return back()->with('error', 'Ce justificatif a déjà été traité.');
        }

        $absence->update([
            'justification_statut'      => $data['decision'],
            'justifiee'                 => $data['decision'] === 'acceptee',
            'motif'                     => $absence->motif ?: $absence->justification_motif,
            'justification_traitee_by'  => Auth::id(),
            'justification_traitee_at'  => now(),
            'justification_commentaire' => $data['commentaire'] ?? null,
        ]);

        return back()->with('success', $data['decision'] === 'acceptee' ? 'Absence justifiée.' : 'Justificatif refusé, le stagiaire verra le motif.');
    }
}
