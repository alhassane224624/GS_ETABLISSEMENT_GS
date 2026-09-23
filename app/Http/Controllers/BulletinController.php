<?php

namespace App\Http\Controllers;

use App\Models\Bulletin;
use App\Models\Stagiaire;
use App\Models\Classe;
use App\Models\Periode;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $generated = 0;
        
        foreach ($classe->stagiaires as $stagiaire) {
            $exists = Bulletin::where('stagiaire_id', $stagiaire->id)
                ->where('periode_id', $periode->id)
                ->exists();

            if (!$exists) {
                $bulletin = $this->generateBulletinForStagiaire($stagiaire, $classe, $periode);
                
                // 🔔 Note : Ne pas notifier lors de la génération, seulement lors de la validation
                // if ($bulletin && $stagiaire->user) {
                //     $stagiaire->user->notify(new BulletinGenerated($bulletin));
                // }
                
                $generated++;
            }
        }

        return redirect()->back()
            ->with('success', "{$generated} bulletin(s) généré(s) avec succès. Pensez à les valider !");
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
    private function generateBulletinForStagiaire(Stagiaire $stagiaire, Classe $classe, Periode $periode)
    {
        $notes = Note::where('stagiaire_id', $stagiaire->id)
            ->where('periode_id', $periode->id)
            ->with('matiere')
            ->get();

        if ($notes->isEmpty()) {
            return null;
        }

        $moyennesParMatiere = $notes->groupBy('matiere_id')->map(function ($notesMatiere) {
            $matiere = $notesMatiere->first()->matiere;
            
            if (!$matiere) {
                return null;
            }
            
            $moyenne = $notesMatiere->avg('note');
            
            return [
                'matiere' => $matiere->nom ?? 'N/A',
                'code' => $matiere->code ?? 'N/A',
                'coefficient' => $matiere->coefficient ?? 1,
                'moyenne' => round($moyenne, 2),
                'note_sur' => 20
            ];
        })->filter();

        $totalPoints = 0;
        $totalCoefficients = 0;
        
        foreach ($moyennesParMatiere as $moyenneMatiere) {
            $totalPoints += $moyenneMatiere['moyenne'] * $moyenneMatiere['coefficient'];
            $totalCoefficients += $moyenneMatiere['coefficient'];
        }

        $moyenneGenerale = $totalCoefficients > 0 ? 
            round($totalPoints / $totalCoefficients, 2) : 0;

        // Calculer le rang dans la classe
        $stagiairesDeLaClasse = $classe->stagiaires()
            ->whereHas('notes', function($q) use ($periode) {
                $q->where('periode_id', $periode->id);
            })
            ->get();

        $moyennesClasse = $stagiairesDeLaClasse->map(function ($s) use ($periode) {
            $notesS = Note::where('stagiaire_id', $s->id)
                ->where('periode_id', $periode->id)
                ->with('matiere')
                ->get();

            $moyennesS = $notesS->groupBy('matiere_id')->map(function ($nm) {
                $matiere = $nm->first()->matiere;
                if (!$matiere) return null;
                
                return [
                    'moyenne' => $nm->avg('note'),
                    'coefficient' => $matiere->coefficient ?? 1
                ];
            })->filter();

            $totalP = 0;
            $totalC = 0;
            foreach ($moyennesS as $m) {
                $totalP += $m['moyenne'] * $m['coefficient'];
                $totalC += $m['coefficient'];
            }

            return [
                'stagiaire_id' => $s->id,
                'moyenne' => $totalC > 0 ? $totalP / $totalC : 0
            ];
        })->sortByDesc('moyenne')->values();

        $rang = $moyennesClasse->search(function ($item) use ($stagiaire) {
            return $item['stagiaire_id'] === $stagiaire->id;
        }) + 1;

        $appreciation = $this->genererAppreciation($moyenneGenerale, $rang, $stagiairesDeLaClasse->count());

        return Bulletin::create([
            'stagiaire_id' => $stagiaire->id,
            'classe_id' => $classe->id,
            'periode_id' => $periode->id,
            'moyenne_generale' => $moyenneGenerale,
            'rang' => $rang,
            'total_classe' => $stagiairesDeLaClasse->count(),
            'appreciation_generale' => $appreciation,
            'moyennes_matieres' => $moyennesParMatiere->values()->toArray(),
            'created_by' => Auth::id(),
            // ✅ Important : Le bulletin n'est PAS validé automatiquement
            'validated_at' => null,
            'validated_by' => null,
        ]);
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