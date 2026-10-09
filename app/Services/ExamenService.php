<?php

namespace App\Services;

use App\Models\Bulletin;
use App\Models\Epreuve;
use App\Models\Examen;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\Planning;
use App\Models\ResultatEpreuve;
use App\Models\Stagiaire;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamenService
{
    /** Crée une épreuve et, si salle + surveillant sont connus, la séance « examen » au planning */
    public function creerEpreuve(Examen $examen, array $data): Epreuve
    {
        if ($data['date'] < $examen->date_debut->toDateString() || $data['date'] > $examen->date_fin->toDateString()) {
            throw ValidationException::withMessages(['date' => 'La date doit être comprise dans la session (' . $examen->date_debut->format('d/m') . ' – ' . $examen->date_fin->format('d/m') . ').']);
        }

        return DB::transaction(function () use ($examen, $data) {
            $epreuve = Epreuve::create($data + ['examen_id' => $examen->id]);

            if (!empty($data['salle_id']) && !empty($data['surveillant_id'])) {
                $conflit = Planning::whereDate('date', $data['date'])
                    ->whereIn('statut', ['brouillon', 'valide', 'en_cours'])
                    ->where('heure_debut', '<', $data['heure_fin'])
                    ->where('heure_fin', '>', $data['heure_debut'])
                    ->where(fn ($q) => $q->where('classe_id', $data['classe_id'])
                        ->orWhere('professeur_id', $data['surveillant_id'])
                        ->orWhere('salle_id', $data['salle_id']))
                    ->first();

                if ($conflit) {
                    throw ValidationException::withMessages(['date' => "Créneau déjà occupé au planning (séance #{$conflit->id} : même classe, salle ou surveillant)."]);
                }

                $planning = Planning::create([
                    'professeur_id' => $data['surveillant_id'],
                    'salle_id'      => $data['salle_id'],
                    'matiere_id'    => $data['matiere_id'],
                    'classe_id'     => $data['classe_id'],
                    'date'          => $data['date'],
                    'heure_debut'   => $data['heure_debut'],
                    'heure_fin'     => $data['heure_fin'],
                    'type_cours'    => 'examen',
                    'description'   => $examen->nom,
                    'statut'        => 'valide',
                    'created_by'    => Auth::id(),
                    'validated_by'  => Auth::id(),
                    'validated_at'  => now(),
                ]);
                $epreuve->update(['planning_id' => $planning->id]);
            }

            return $epreuve;
        });
    }

    public function supprimerEpreuve(Epreuve $epreuve): void
    {
        if ($epreuve->resultats()->whereNotNull('note')->exists()) {
            throw ValidationException::withMessages(['epreuve' => 'Des notes sont déjà saisies pour cette épreuve.']);
        }
        DB::transaction(function () use ($epreuve) {
            $epreuve->planning?->delete();
            $epreuve->delete();
        });
    }

    /**
     * Convoqués : session normale = stagiaires actifs de la classe ;
     * rattrapage = inscrits de la classe dont la décision est « rattrapage ».
     */
    public function convoques(Epreuve $epreuve): Collection
    {
        $epreuve->loadMissing('examen', 'classe');

        if ($epreuve->examen->estRattrapage()) {
            // Admis au rattrapage + ceux qui ont déjà une note (leur décision a pu changer depuis)
            $ids = Inscription::where('classe_id', $epreuve->classe_id)->where('decision', 'rattrapage')->pluck('stagiaire_id')
                ->merge($epreuve->resultats()->pluck('stagiaire_id'))->unique();
            return Stagiaire::whereIn('id', $ids)->orderBy('nom')->orderBy('prenom')->get();
        }

        return Stagiaire::where('classe_id', $epreuve->classe_id)->where('statut', 'actif')
            ->orderBy('nom')->orderBy('prenom')->get();
    }

    /**
     * Saisie des résultats.
     *  - normale : chaque résultat devient une note « examen » de la période (absent = 0)
     *  - rattrapage : résultats conservés pour le calcul de la moyenne après rattrapage
     *
     * @param array $notes   [stagiaire_id => note|null]
     * @param array $absents [stagiaire_id => 1]
     */
    public function enregistrerResultats(Epreuve $epreuve, array $notes, array $absents): array
    {
        $epreuve->loadMissing('examen', 'matiere');
        $examen = $epreuve->examen;

        if ($examen->estCloturee()) {
            throw ValidationException::withMessages(['epreuve' => 'Session clôturée : les résultats ne sont plus modifiables.']);
        }
        if (!$examen->estRattrapage() && !$examen->periode_id) {
            throw ValidationException::withMessages(['epreuve' => 'La session normale doit être rattachée à une période pour que les notes comptent dans les bulletins.']);
        }

        $convoques = $this->convoques($epreuve)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sur = (float) $epreuve->note_sur;
        $rapport = ['saisis' => 0, 'verrouilles' => []];

        DB::transaction(function () use ($epreuve, $examen, $notes, $absents, $convoques, $sur, &$rapport) {
            foreach ($convoques as $sid) {
                $absent = !empty($absents[$sid]);
                $valeur = $notes[$sid] ?? null;
                $valeur = ($valeur === '' || $valeur === null) ? null : (float) str_replace(',', '.', $valeur);

                if ($valeur !== null && ($valeur < 0 || $valeur > $sur)) {
                    throw ValidationException::withMessages(['notes' => "Une note dépasse le barème de l'épreuve ({$sur})."]);
                }
                if ($absent) {
                    $valeur = 0;
                }
                if ($valeur === null) {
                    continue; // pas encore corrigé
                }

                $resultat = ResultatEpreuve::firstOrNew(['epreuve_id' => $epreuve->id, 'stagiaire_id' => $sid]);
                $resultat->fill(['note' => $valeur, 'absent' => $absent, 'saisi_par' => Auth::id()]);

                if (!$examen->estRattrapage()) {
                    if (Note::periodeVerrouillee($sid, $examen->periode_id)) {
                        $rapport['verrouilles'][] = $sid;
                        continue;
                    }
                    $note = $resultat->note_id ? Note::find($resultat->note_id) : null;
                    $donnees = [
                        'stagiaire_id' => $sid,
                        'matiere_id'   => $epreuve->matiere_id,
                        'classe_id'    => $epreuve->classe_id,
                        'periode_id'   => $examen->periode_id,
                        'note'         => $valeur,
                        'note_sur'     => $sur,
                        'type_note'    => 'examen',
                        'commentaire'  => $examen->nom . ($absent ? ' — absent' : ''),
                        'created_by'   => Auth::id(),
                    ];
                    $note ? $note->update($donnees) : ($note = Note::create($donnees));
                    $resultat->note_id = $note->id;
                }

                $resultat->save();
                $rapport['saisis']++;
            }
        });

        return $rapport;
    }

    // ------------------------------------------------------------------
    // Moyennes annuelles par matière (bulletins validés) et rattrapage
    // ------------------------------------------------------------------

    /** [code => ['nom', 'coefficient', 'moyenne']] à partir des bulletins validés de l'année */
    public function moyennesMatieres(int $stagiaireId, int $anneeId): array
    {
        $bulletins = Bulletin::where('stagiaire_id', $stagiaireId)
            ->whereNotNull('validated_at')
            ->whereHas('periode', fn ($q) => $q->where('annee_scolaire_id', $anneeId))
            ->get();

        $cumul = [];
        foreach ($bulletins as $b) {
            $lignes = is_array($b->moyennes_matieres) ? $b->moyennes_matieres : (json_decode($b->moyennes_matieres, true) ?? []);
            foreach ($lignes as $l) {
                $code = $l['code'] ?? $l['matiere'] ?? null;
                if ($code === null || !isset($l['moyenne'])) {
                    continue;
                }
                $cumul[$code]['nom'] = $l['matiere'] ?? $code;
                $cumul[$code]['coefficient'] = (float) ($l['coefficient'] ?? 1);
                $cumul[$code]['valeurs'][] = (float) $l['moyenne'];
            }
        }

        return collect($cumul)->map(fn ($m) => [
            'nom' => $m['nom'],
            'coefficient' => $m['coefficient'],
            'moyenne' => round(array_sum($m['valeurs']) / count($m['valeurs']), 2),
        ])->all();
    }

    /** Notes de rattrapage de l'année, ramenées sur 20 : [code matière => note] */
    public function notesRattrapage(int $stagiaireId, int $anneeId): array
    {
        return ResultatEpreuve::with('epreuve.matiere')
            ->where('stagiaire_id', $stagiaireId)
            ->whereNotNull('note')
            ->whereHas('epreuve.examen', fn ($q) => $q->where('type', 'rattrapage')->where('annee_scolaire_id', $anneeId))
            ->get()
            ->mapWithKeys(fn ($r) => [
                ($r->epreuve->matiere->code ?? $r->epreuve->matiere->nom) => round((float) $r->note / max(1, (float) $r->epreuve->note_sur) * 20, 2),
            ])->all();
    }

    /**
     * Moyenne annuelle après rattrapage : la note de rattrapage remplace la moyenne de la matière si elle est meilleure.
     * Renvoie null s'il n'y a pas de rattrapage passé.
     */
    public function moyenneApresRattrapage(int $stagiaireId, int $anneeId): ?float
    {
        $rattrapage = $this->notesRattrapage($stagiaireId, $anneeId);
        if (!$rattrapage) {
            return null;
        }

        $matieres = $this->moyennesMatieres($stagiaireId, $anneeId);
        if (!$matieres) {
            return null;
        }

        $points = 0;
        $coefs = 0;
        foreach ($matieres as $code => $m) {
            $moyenne = isset($rattrapage[$code]) ? max($m['moyenne'], $rattrapage[$code]) : $m['moyenne'];
            $points += $moyenne * $m['coefficient'];
            $coefs += $m['coefficient'];
        }

        return $coefs > 0 ? round($points / $coefs, 2) : null;
    }
}
