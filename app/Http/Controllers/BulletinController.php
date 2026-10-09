<?php

namespace App\Http\Controllers;

use App\Models\Bulletin;
use App\Models\Stagiaire;
use App\Models\Classe;
use App\Models\Periode;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;

// 🔔 AJOUT : Importer la notification
use App\Notifications\BulletinGenerated;

class BulletinController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Affiche la liste de tous les bulletins avec filtres
     */
    public function index(Request $request)
    {
        $query = Bulletin::with(['stagiaire', 'classe', 'periode', 'creator']);

        // Filtre par classe
        if ($request->filled('classe_id')) {
            $query->where('classe_id', $request->classe_id);
        }

        // Filtre par période
        if ($request->filled('periode_id')) {
            $query->where('periode_id', $request->periode_id);
        }

        // ✅ Filtre par statut de validation
        if ($request->filled('statut')) {
            if ($request->statut === 'valide') {
                $query->whereNotNull('validated_at');
            } elseif ($request->statut === 'en_attente') {
                $query->whereNull('validated_at');
            }
        }

        $bulletins = $query->latest()->paginate(20);
        
        $classes = Classe::with('niveau', 'filiere')->get();
        $periodes = Periode::with('anneeScolaire')->get();

        return view('bulletins.index', compact('bulletins', 'classes', 'periodes'));
    }

    /**
     * ✅ NOUVEAU : Affiche uniquement les bulletins en attente de validation
     */
    public function pending()
    {
        $bulletins = Bulletin::with(['stagiaire', 'classe', 'periode'])
            ->whereNull('validated_at')
            ->latest()
            ->get();

        return view('bulletins.pending', compact('bulletins'));
    }

    /**
     * ✅ NOUVEAU : API - Retourne le nombre de bulletins en attente
     */
    public function pendingCount()
    {
        $count = Bulletin::whereNull('validated_at')->count();
        
        return response()->json([
            'count' => $count,
            'message' => $count > 0 
                ? "{$count} bulletin(s) en attente" 
                : 'Aucun bulletin en attente'
        ]);
    }

    /**
     * Génère des bulletins pour une classe et une période
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'periode_id' => 'required|exists:periodes,id',
        ]);

        $classe = Classe::with('stagiaires')->findOrFail($validated['classe_id']);
        $periode = Periode::findOrFail($validated['periode_id']);

        // 1. Calcul de toutes les moyennes de la classe en une seule passe
        $resultats = $this->calculerMoyennesClasse($classe, $periode);

        if ($resultats->isEmpty()) {
            return back()->with('error', 'Aucune note saisie pour cette classe sur cette période.');
        }

        // 2. Classement (ex æquo gérés : même moyenne = même rang)
        $classement = $this->calculerRangs($resultats);
        $total = $resultats->count();

        $generated = 0;
        $updated = 0;

        DB::transaction(function () use ($resultats, $classement, $total, $classe, $periode, &$generated, &$updated) {
            foreach ($resultats as $stagiaireId => $res) {
                $rang = $classement[$stagiaireId];
                $bulletin = Bulletin::where('stagiaire_id', $stagiaireId)
                    ->where('periode_id', $periode->id)
                    ->first();

                $donnees = [
                    'classe_id' => $classe->id,
                    'moyenne_generale' => $res['moyenne'],
                    'rang' => $rang,
                    'total_classe' => $total,
                    'appreciation_generale' => $this->genererAppreciation($res['moyenne'], $rang, $total),
                    'moyennes_matieres' => $res['matieres'],
                ];

                if (!$bulletin) {
                    Bulletin::create($donnees + [
                        'stagiaire_id' => $stagiaireId,
                        'periode_id' => $periode->id,
                        'created_by' => Auth::id(),
                        'validated_at' => null,
                        'validated_by' => null,
                    ]);
                    $generated++;
                } elseif (!$bulletin->validated_at) {
                    // Les bulletins non validés sont recalculés (notes ajoutées depuis)
                    $bulletin->update($donnees);
                    $updated++;
                }
            }
        });

        return redirect()->back()
            ->with('success', "{$generated} bulletin(s) généré(s), {$updated} recalculé(s). Pensez à les valider !");
    }

    /**
     * Affiche les détails d'un bulletin
     */
    public function show(Bulletin $bulletin)
    {
        $bulletin->load(['stagiaire', 'classe.niveau', 'classe.filiere', 'periode']);
        return view('bulletins.show', compact('bulletin'));
    }

    /**
     * Télécharge un bulletin en PDF
     */
    public function downloadPdf(Bulletin $bulletin)
    {
        $bulletin->load(['stagiaire', 'classe.niveau', 'classe.filiere', 'periode']);
        
        $pdf = PDF::loadView('bulletins.pdf', compact('bulletin'));
        
        $filename = 'bulletin_' . $bulletin->stagiaire->matricule . '_' . 
                    $bulletin->periode->nom . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Valide un bulletin (action individuelle)
     */
    public function validateBulletin(Bulletin $bulletin)
    {
        if ($bulletin->validated_at) {
            return redirect()->back()
                ->with('error', 'Ce bulletin est déjà validé.');
        }

        $bulletin->update([
            'validated_at' => now(),
            'validated_by' => Auth::id(),
        ]);

        // 🔔 NOTIFICATION : Notifier le stagiaire que son bulletin est validé
        if ($bulletin->stagiaire && $bulletin->stagiaire->user) {
            $bulletin->stagiaire->user->notify(new BulletinGenerated($bulletin));
        }

        return redirect()->back()
            ->with('success', 'Bulletin validé avec succès. Le stagiaire a été notifié.');
    }

    /**
     * ✅ NOUVEAU : Valide plusieurs bulletins en une seule fois
     */
    /**
     * Rouvre un bulletin validé (correction de note). Le stagiaire ne le voit plus
     * jusqu'à la nouvelle validation ; régénérer les bulletins le recalcule.
     */
    public function invalidateBulletin(Request $request, Bulletin $bulletin)
    {
        $request->validate(['motif' => 'required|string|max:500']);

        if (!$bulletin->validated_at) {
            return back()->with('error', 'Ce bulletin n\'est pas validé.');
        }

        $bulletin->update(['validated_at' => null, 'validated_by' => null]);

        \Illuminate\Support\Facades\Log::info('Bulletin dévalidé', [
            'bulletin_id' => $bulletin->id,
            'par' => Auth::id(),
            'motif' => $request->motif,
        ]);

        return back()->with('success', 'Bulletin rouvert : corrigez les notes, régénérez les bulletins de la classe puis validez à nouveau.');
    }

    public function validateMultiple(Request $request)
    {
        $validated = $request->validate([
            'bulletin_ids' => 'required|array',
            'bulletin_ids.*' => 'exists:bulletins,id'
        ]);

        $count = 0;
        
        foreach ($validated['bulletin_ids'] as $bulletinId) {
            $bulletin = Bulletin::find($bulletinId);
            
            if ($bulletin && !$bulletin->validated_at) {
                $bulletin->update([
                    'validated_at' => now(),
                    'validated_by' => Auth::id(),
                ]);
                
                // 🔔 NOTIFICATION : Notifier chaque stagiaire
                if ($bulletin->stagiaire && $bulletin->stagiaire->user) {
                    $bulletin->stagiaire->user->notify(new BulletinGenerated($bulletin));
                }
                
                $count++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} bulletin(s) validé(s) avec succès. Les stagiaires ont été notifiés."
        ]);
    }

    /**
     * Génère un bulletin pour un stagiaire spécifique
     * @private
     */
    /**
     * Calcule, pour chaque stagiaire de la classe, la moyenne par matière et la
     * moyenne générale pondérée. Toutes les notes sont ramenées sur 20
     * (une note de 8/10 compte 16/20).
     *
     * @return \Illuminate\Support\Collection [stagiaire_id => ['moyenne' => float, 'matieres' => array]]
     */
    private function calculerMoyennesClasse(Classe $classe, Periode $periode)
    {
        $notes = Note::whereIn('stagiaire_id', $classe->stagiaires->pluck('id'))
            ->where('periode_id', $periode->id)
            ->with('matiere')
            ->get()
            ->groupBy('stagiaire_id');

        return $notes->map(function ($notesStagiaire) {
            $matieres = $notesStagiaire->groupBy('matiere_id')->map(function ($notesMatiere) {
                $matiere = $notesMatiere->first()->matiere;
                if (!$matiere) {
                    return null;
                }

                $moyenne = $notesMatiere->avg(function ($n) {
                    $sur = (float) ($n->note_sur ?: 20);
                    return $sur > 0 ? ($n->note / $sur) * 20 : 0;
                });

                return [
                    'matiere' => $matiere->nom ?? 'N/A',
                    'code' => $matiere->code ?? 'N/A',
                    'coefficient' => $matiere->coefficient ?: 1,
                    'moyenne' => round($moyenne, 2),
                    'note_sur' => 20,
                ];
            })->filter()->values();

            $totalCoef = $matieres->sum('coefficient');
            $totalPoints = $matieres->sum(fn ($m) => $m['moyenne'] * $m['coefficient']);

            return [
                'moyenne' => $totalCoef > 0 ? round($totalPoints / $totalCoef, 2) : 0,
                'matieres' => $matieres->toArray(),
            ];
        });
    }

    /**
     * Rang de chaque stagiaire (classement « olympique » : 1, 2, 2, 4)
     */
    private function calculerRangs($resultats): array
    {
        $tries = $resultats->sortByDesc('moyenne');
        $rangs = [];
        $position = 0;
        $rangCourant = 0;
        $precedente = null;

        foreach ($tries as $stagiaireId => $res) {
            $position++;
            if ($precedente === null || $res['moyenne'] < $precedente) {
                $rangCourant = $position;
            }
            $rangs[$stagiaireId] = $rangCourant;
            $precedente = $res['moyenne'];
        }

        return $rangs;
    }

    /**
     * Génère une appréciation selon la moyenne
     * @private
     */
    private function genererAppreciation($moyenne, $rang, $totalEleves)
    {
        if ($moyenne >= 16) {
            return "Excellent travail ! Continuez ainsi.";
        } elseif ($moyenne >= 14) {
            return "Très bon travail. Félicitations !";
        } elseif ($moyenne >= 12) {
            return "Bon travail dans l'ensemble.";
        } elseif ($moyenne >= 10) {
            return "Travail satisfaisant. Peut mieux faire.";
        } else {
            return "Travail insuffisant. Des efforts sont nécessaires.";
        }
    }
}