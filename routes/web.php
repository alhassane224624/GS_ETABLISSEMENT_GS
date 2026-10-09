<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StagiaireController;
use App\Http\Controllers\FiliereController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfesseurController;
use App\Http\Controllers\StagiaireSpaceController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\SalleController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\MatiereController;
use App\Http\Controllers\NiveauController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\AnneeScolaireController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\PeriodeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\EcheancierController;
use App\Http\Controllers\RemiseController;
use App\Http\Controllers\RapportFinancierController;
use App\Http\Controllers\ComptableController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\InscriptionController;
use App\Http\Controllers\EmploiDuTempsController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SeanceController;
use App\Http\Controllers\JustificationController;
use App\Http\Controllers\ExamenController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ParentController;
use Illuminate\Support\Facades\Route;

// ============================================================================
// PAGE D'ACCUEIL
// ============================================================================

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('welcome');

require __DIR__ . '/auth.php';

// ============================================================================
// ROUTES PUBLIQUES
// ============================================================================

Route::get('/inscription-stagiaire', [StagiaireController::class, 'showInscriptionForm'])
    ->name('stagiaires.inscription.form');
Route::post('/inscription-stagiaire', [StagiaireController::class, 'storeInscription'])
    ->middleware('throttle:5,1')
    ->name('stagiaires.inscription.store');

// ============================================================================
// ROUTES AUTHENTIFIÉES
// ============================================================================

Route::middleware(['auth', 'verified'])->group(function () {
    
    // ✅ REDIRECTION INTELLIGENTE SELON LE RÔLE
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        return match($user->role) {
            'administrateur' => redirect('/admin/dashboard'),
            'comptable' => redirect('/comptable/dashboard'),
            'professeur' => redirect('/professeur/dashboard'),
            'stagiaire' => redirect('/stagiaire/dashboard'),
            'parent' => redirect('/parent'),
            default => redirect()->route('login')->with('error', 'Rôle non reconnu'),
        };
    })->name('dashboard');

    // Profil utilisateur
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ============================================================================
    // MESSAGERIE ET NOTIFICATIONS
    // ============================================================================

    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/create', [MessageController::class, 'create'])->name('create');
        Route::post('/send', [MessageController::class, 'sendById'])->name('send.by-id');
        Route::post('/send/{user}', [MessageController::class, 'store'])->name('send');
        Route::get('/conversation/{user}', [MessageController::class, 'conversation'])->name('conversation');
        Route::get('/send-group', [MessageController::class, 'showSendGroupForm'])->name('send-group.form');
        Route::post('/send-group', [MessageController::class, 'sendGroup'])->name('send-group');
        Route::get('/unread-count', [MessageController::class, 'unreadCount'])->name('unread-count');
        Route::delete('/{user}/delete', [MessageController::class, 'deleteConversation'])->name('delete');
        Route::post('/bulk-delete', [MessageController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/reset', [MessageController::class, 'reset'])->name('reset');
    });

   Route::prefix('notifications')->name('notifications.')->group(function () {
        // 📄 Page principale
        Route::get('/', [NotificationController::class, 'index'])->name('index');

        // 📩 Marquer une notification comme lue
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');

        // 🗑️ Supprimer une notification (remplace DELETE par POST)
        Route::post('/{id}/delete', [NotificationController::class, 'destroy'])->name('destroy');

        // ✅ Tout marquer comme lu
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');

        // 🧹 Supprimer toutes les notifications lues (remplace DELETE par POST)
        Route::post('/delete-read', [NotificationController::class, 'deleteRead'])->name('delete-read');

        // 🔔 API — nombre non lus
        Route::get('/unread-count', [NotificationController::class, 'getUnreadCount'])->name('unread-count');

        // 🕑 API — notifications récentes
        Route::get('/recent', [NotificationController::class, 'getRecent'])->name('recent');
    });
});

// ============================================================================
// ✅ ROUTES COMPTABLE
// ============================================================================

Route::middleware(['auth', 'verified', 'financial'])->prefix('comptable')->name('comptable.')->group(function () {
    Route::get('/dashboard', [ComptableController::class, 'dashboard'])->name('dashboard');
    Route::get('/stagiaires', [ComptableController::class, 'stagiaires'])->name('stagiaires');
    Route::get('/stagiaires/{stagiaire}', [ComptableController::class, 'stagiaireDetail'])->name('stagiaires.show');
    Route::get('/rapports', [ComptableController::class, 'rapports'])->name('rapports');
});

// ============================================================================
// ROUTES STAGIAIRE
// ============================================================================

Route::middleware(['auth', 'verified', 'stagiaire'])->prefix('stagiaire')->name('stagiaire.')->group(function () {
    Route::get('/dashboard', [StagiaireSpaceController::class, 'dashboard'])->name('dashboard');
    Route::get('/notes', [StagiaireSpaceController::class, 'mesNotes'])->name('notes');
    Route::get('/notes/telecharger', [StagiaireSpaceController::class, 'telechargerReleve'])->name('notes.telecharger');
    Route::get('/bulletin', [StagiaireSpaceController::class, 'monBulletin'])->name('bulletin');
    Route::get('/bulletin/{bulletin}/telecharger', [StagiaireSpaceController::class, 'telechargerBulletin'])->name('bulletin.telecharger');
    Route::get('/emploi-du-temps', [StagiaireSpaceController::class, 'emploiDuTemps'])->name('emploi-du-temps');
    Route::get('/attestation', [DocumentController::class, 'monAttestation'])->name('attestation');
    Route::get('/cahier-de-textes', [StagiaireSpaceController::class, 'cahierDeTextes'])->name('cahier-de-textes');
    Route::post('/absences/{absence}/justifier', [JustificationController::class, 'deposer'])->name('absences.justifier');
    Route::get('/absences', [StagiaireSpaceController::class, 'mesAbsences'])->name('absences');
    Route::get('/profil', [StagiaireSpaceController::class, 'monProfil'])->name('profil');
    
    // 💰 PAIEMENTS STAGIAIRE
    Route::get('/mes-paiements', [PaiementController::class, 'mesPaiements'])->name('paiements');
    Route::get('/mes-echeanciers', [EcheancierController::class, 'mesEcheanciers'])->name('echeanciers');
    Route::get('/paiement/{paiement}/recu', [PaiementController::class, 'telechargerRecu'])->name('paiement.recu');
});

// ============================================================================
// ROUTES PROFESSEUR
// ============================================================================

Route::middleware(['auth', 'verified', 'professeur'])->prefix('professeur')->name('professeur.')->group(function () {
    Route::get('/dashboard', [ProfesseurController::class, 'dashboard'])->name('dashboard');
    Route::get('/stagiaires', [ProfesseurController::class, 'index'])->name('stagiaires');
    Route::get('/stagiaires/{stagiaire}/notes', [ProfesseurController::class, 'showNotes'])->name('stagiaires.notes');
    Route::post('/stagiaires/{stagiaire}/notes', [ProfesseurController::class, 'storeNote'])->name('stagiaires.notes.store');
    Route::put('/stagiaires/{stagiaire}/notes/{note}', [ProfesseurController::class, 'updateNote'])->name('stagiaires.notes.update');
    Route::get('/notes-par-matiere', [ProfesseurController::class, 'notesParMatiere'])->name('notes-par-matiere');
    Route::get('/notes/export-pdf', [ProfesseurController::class, 'exportNotesPdf'])->name('notes.export-pdf');
    Route::get('/notes/export-excel', [ProfesseurController::class, 'exportNotesExcel'])->name('notes.export-excel');
    Route::get('/stagiaires/export-pdf', [ProfesseurController::class, 'exportStagiairesPdf'])->name('stagiaires.export-pdf');
    Route::get('/stagiaires/export-excel', [ProfesseurController::class, 'exportStagiairesExcel'])->name('stagiaires.export-excel');
    
    Route::get('stagiaires-export', [ProfesseurController::class, 'exportStagiairesExcel'])->name('stagiaires.export');
    Route::get('/presences', [ProfesseurController::class, 'presences'])->name('presences');
    Route::post('/presences/marquer', [ProfesseurController::class, 'marquerAbsence'])->name('presences.marquer');
    Route::delete('/presences/{absence}', [ProfesseurController::class, 'supprimerAbsence'])->name('presences.supprimer');
    Route::get('/planning', [ProfesseurController::class, 'monPlanning'])->name('planning');
    Route::get('/seances/{planning}', [SeanceController::class, 'show'])->name('seance');
    Route::get('/epreuves', [ExamenController::class, 'mesEpreuves'])->name('epreuves');
    Route::get('/epreuves/{epreuve}/saisie', [ExamenController::class, 'saisie'])->name('epreuves.saisie');
    Route::post('/epreuves/{epreuve}/saisie', [ExamenController::class, 'enregistrerSaisie'])->name('epreuves.saisie.store');
    Route::post('/seances/{planning}', [SeanceController::class, 'enregistrer'])->name('seance.enregistrer');
    Route::get('/planning/create', [ProfesseurController::class, 'createPlanning'])->name('planning.create');
    Route::post('/planning', [ProfesseurController::class, 'storePlanning'])->name('planning.store');
});

// ============================================================================
// ROUTES ADMINISTRATEUR
// ============================================================================

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');
    
    Route::resource('stagiaires', StagiaireController::class);
    Route::post('stagiaires/{stagiaire}/change-statut', [StagiaireController::class, 'changeStatut'])->name('stagiaires.change-statut');
    Route::get('stagiaires-export', [StagiaireController::class, 'export'])->name('stagiaires.export');
    
    Route::resource('filieres', FiliereController::class);
    Route::resource('classes', ClasseController::class)->parameters(['classes' => 'classe']);
    Route::resource('niveaux', NiveauController::class);
    Route::resource('matieres', MatiereController::class);
    
    // ⚠️ /notes/export doit être déclarée AVANT la ressource (sinon "export" est pris pour un {note})
    Route::get('/notes/export', [NoteController::class, 'export'])->name('notes.export');
    Route::resource('notes', NoteController::class);
    Route::get('stagiaires/{stagiaire}/releve', [NoteController::class, 'releveStagiaire'])->name('notes.releve');
    
    Route::resource('salles', SalleController::class);
    Route::patch('salles/{salle}/toggle-disponibilite', [SalleController::class, 'toggleDisponibilite'])->name('salles.toggle-disponibilite');
    Route::get('salles/{salle}/planning', [SalleController::class, 'planning'])->name('salles.planning');
    Route::post('salles/check-disponibilite', [SalleController::class, 'checkDisponibilite'])->name('salles.check-disponibilite');
    
    Route::resource('planning', PlanningController::class);
    Route::post('planning/{planning}/valider', [PlanningController::class, 'valider'])->name('planning.valider');
    Route::post('planning/{planning}/annuler', [PlanningController::class, 'annuler'])->name('planning.annuler');
    
    Route::get('absences/justifications', [JustificationController::class, 'index'])->name('absences.justifications');
    Route::post('absences/{absence}/justification', [JustificationController::class, 'traiter'])->name('absences.justification.traiter');
    Route::resource('absences', AbsenceController::class);
    Route::get('absences-rapport', [AbsenceController::class, 'rapportAbsences'])->name('absences.rapport');
    Route::get('absences-export', [AbsenceController::class, 'exportAbsences'])->name('absences.export');
    
    Route::resource('annees-scolaires', AnneeScolaireController::class);
    Route::post('annees-scolaires/{annees_scolaire}/activate', [AnneeScolaireController::class, 'activate'])->name('annees-scolaires.activate');
    Route::post('annees-scolaires/{annees_scolaire}/duplicate', [AnneeScolaireController::class, 'duplicate'])->name('annees-scolaires.duplicate');
    Route::get('annees-scolaires-active', [AnneeScolaireController::class, 'getActive'])->name('annees-scolaires.active');
    
    Route::resource('periodes', PeriodeController::class);
    Route::post('periodes/{periode}/activer', [PeriodeController::class, 'activerPeriode'])->name('periodes.activer');
    
    Route::prefix('bulletins')->name('bulletins.')->group(function () {
        Route::get('/', [BulletinController::class, 'index'])->name('index');
        Route::get('/pending-count', [BulletinController::class, 'pendingCount'])->name('pending-count');
        Route::get('/pending', [BulletinController::class, 'pending'])->name('pending');
        Route::post('/generate', [BulletinController::class, 'generate'])->name('generate');
        Route::post('/validate-multiple', [BulletinController::class, 'validateMultiple'])->name('validate-multiple');
        Route::get('/{bulletin}', [BulletinController::class, 'show'])->name('show');
        Route::get('/{bulletin}/download-pdf', [BulletinController::class, 'downloadPdf'])->name('download-pdf');
        Route::patch('/{bulletin}/validate', [BulletinController::class, 'validateBulletin'])->name('validate');
        Route::patch('/{bulletin}/invalidate', [BulletinController::class, 'invalidateBulletin'])->name('invalidate');
    });
    
    Route::resource('users', UserController::class);
    Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    
    Route::get('professeurs/{professeur}/filieres', [ProfesseurController::class, 'editFilieres'])->name('professeurs.filieres.edit');
    Route::put('professeurs/{professeur}/filieres', [ProfesseurController::class, 'updateFilieres'])->name('professeurs.filieres.update');
    Route::get('professeurs/{professeur}/matieres', [ProfesseurController::class, 'editMatieres'])->name('professeurs.matieres.edit');
    Route::put('professeurs/{professeur}/matieres', [ProfesseurController::class, 'updateMatieres'])->name('professeurs.matieres.update');
    
    Route::get('import/stagiaires', [ImportController::class, 'showImportForm'])->name('import.stagiaires.form');
    Route::post('import/stagiaires', [ImportController::class, 'importStagiaires'])->name('import.stagiaires');
    Route::get('import/template', [ImportController::class, 'downloadTemplate'])->name('import.template');
    
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');

    // Examens : sessions, épreuves, résultats, PV
    Route::get('examens', [ExamenController::class, 'index'])->name('examens.index');
    Route::post('examens', [ExamenController::class, 'store'])->name('examens.store');
    Route::get('examens/{examen}', [ExamenController::class, 'show'])->name('examens.show');
    Route::post('examens/{examen}/epreuves', [ExamenController::class, 'storeEpreuve'])->name('examens.epreuves.store');
    Route::post('examens/{examen}/cloturer', [ExamenController::class, 'cloturer'])->name('examens.cloturer');
    Route::delete('epreuves/{epreuve}', [ExamenController::class, 'destroyEpreuve'])->name('epreuves.destroy');
    Route::get('epreuves/{epreuve}/saisie', [ExamenController::class, 'saisie'])->name('epreuves.saisie');
    Route::post('epreuves/{epreuve}/saisie', [ExamenController::class, 'enregistrerSaisie'])->name('epreuves.saisie.store');
    Route::get('inscriptions/classes/{classe}/pv', [InscriptionController::class, 'pv'])->name('inscriptions.pv');

    // Documents administratifs
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('stagiaires/{stagiaire}/documents', [DocumentController::class, 'delivrer'])->name('documents.delivrer');
    Route::post('documents/{document}/annuler', [DocumentController::class, 'annuler'])->name('documents.annuler');

    // Emploi du temps hebdomadaire (créneaux) et génération des séances
    Route::get('emploi-du-temps', [EmploiDuTempsController::class, 'index'])->name('emploi-du-temps.index');
    Route::post('emploi-du-temps/creneaux', [EmploiDuTempsController::class, 'store'])->name('emploi-du-temps.store');
    Route::patch('emploi-du-temps/creneaux/{creneau}', [EmploiDuTempsController::class, 'update'])->name('emploi-du-temps.update');
    Route::delete('emploi-du-temps/creneaux/{creneau}', [EmploiDuTempsController::class, 'destroy'])->name('emploi-du-temps.destroy');
    Route::post('emploi-du-temps/generer', [EmploiDuTempsController::class, 'generer'])->name('emploi-du-temps.generer');

    // Inscriptions annuelles : délibérations et passage d'année
    Route::get('inscriptions', [InscriptionController::class, 'index'])->name('inscriptions.index');
    Route::get('inscriptions/classes/{classe}/deliberation', [InscriptionController::class, 'deliberation'])->name('inscriptions.deliberation');
    Route::post('inscriptions/classes/{classe}/deliberation', [InscriptionController::class, 'enregistrerDeliberation'])->name('inscriptions.deliberation.store');
    Route::get('inscriptions/passage', [InscriptionController::class, 'passageForm'])->name('inscriptions.passage.form');
    Route::post('inscriptions/passage', [InscriptionController::class, 'passage'])->name('inscriptions.passage');
    Route::post('inscriptions/copier-classes', [InscriptionController::class, 'copierClasses'])->name('inscriptions.copier-classes');

    // Paramètres de l'établissement (en-têtes des documents)
    Route::get('parametres/etablissement', [ParametreController::class, 'edit'])->name('parametres.etablissement');
    Route::put('parametres/etablissement', [ParametreController::class, 'update'])->name('parametres.etablissement.update');

    // Sauvegardes
    Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('backups', [BackupController::class, 'create'])->name('backups.create');
    Route::get('backups/{filename}/download', [BackupController::class, 'download'])->name('backups.download');
    Route::delete('backups/{filename}', [BackupController::class, 'delete'])->name('backups.delete');
    Route::get('/statistics/export', [StatisticsController::class, 'export'])->name('statistics.export');
});

// ============================================================================
// 💳 SYSTÈME DE PAIEMENT - ACCESSIBLE ADMIN & COMPTABLE
// ============================================================================

Route::middleware(['auth', 'verified', 'financial'])->group(function () {
    // Dépenses, salaires des professeurs, bilan
    Route::get('finances/depenses', [FinanceController::class, 'depenses'])->name('finances.depenses');
    Route::post('finances/depenses', [FinanceController::class, 'storeDepense'])->name('finances.depenses.store');
    Route::delete('finances/depenses/{depense}', [FinanceController::class, 'destroyDepense'])->name('finances.depenses.destroy');
    Route::get('finances/salaires', [FinanceController::class, 'salaires'])->name('finances.salaires');
    Route::post('finances/salaires/calculer', [FinanceController::class, 'calculerSalaires'])->name('finances.salaires.calculer');
    Route::post('finances/professeurs/{professeur}/remuneration', [FinanceController::class, 'remuneration'])->name('finances.remuneration');
    Route::get('finances/salaires/{salaire}', [FinanceController::class, 'showSalaire'])->name('finances.salaires.show');
    Route::post('finances/salaires/{salaire}/ajuster', [FinanceController::class, 'ajusterSalaire'])->name('finances.salaires.ajuster');
    Route::post('finances/salaires/{salaire}/payer', [FinanceController::class, 'payerSalaire'])->name('finances.salaires.payer');
    Route::post('finances/salaires/{salaire}/annuler-paiement', [FinanceController::class, 'annulerPaiementSalaire'])->name('finances.salaires.annuler');
    Route::get('finances/bilan', [FinanceController::class, 'bilan'])->name('finances.bilan');

    // PAIEMENTS
    Route::prefix('paiements')->name('paiements.')->group(function () {
        Route::get('/', [PaiementController::class, 'index'])->name('index');
        Route::get('/create', [PaiementController::class, 'create'])->name('create');
        Route::post('/', [PaiementController::class, 'store'])->name('store');
        Route::get('/{paiement}', [PaiementController::class, 'show'])->name('show');
        Route::post('/{paiement}/valider', [PaiementController::class, 'valider'])->name('valider');
        Route::post('/{paiement}/refuser', [PaiementController::class, 'refuser'])->name('refuser');
        Route::post('/{paiement}/annuler', [PaiementController::class, 'annuler'])->name('annuler');
        Route::get('/{paiement}/recu', [PaiementController::class, 'telechargerRecu'])->name('recu');
        Route::get('/stagiaire/{stagiaire}/historique', [PaiementController::class, 'historique'])->name('historique');
    });

    // ÉCHÉANCIERS
    Route::prefix('echeanciers')->name('echeanciers.')->group(function () {
        Route::get('/', [EcheancierController::class, 'index'])->name('index');
        Route::get('/create', [EcheancierController::class, 'create'])->name('create');
        Route::post('/', [EcheancierController::class, 'store'])->name('store');
        Route::get('/{echeancier}', [EcheancierController::class, 'show'])->name('show');
        Route::get('/{echeancier}/edit', [EcheancierController::class, 'edit'])->name('edit');
        Route::put('/{echeancier}', [EcheancierController::class, 'update'])->name('update');
        Route::delete('/{echeancier}', [EcheancierController::class, 'destroy'])->name('destroy');
        Route::post('/generer-mensuels', [EcheancierController::class, 'genererMensuels'])->name('generer-mensuels');
        Route::post('/verifier-retards', [EcheancierController::class, 'verifierRetards'])->name('verifier-retards');
        Route::get('/{echeancier}/print', [EcheancierController::class, 'imprimer'])->name('print');
    });

    // REMISES
    Route::prefix('remises')->name('remises.')->group(function () {
        Route::get('/', [RemiseController::class, 'index'])->name('index');
        Route::get('/create', [RemiseController::class, 'create'])->name('create');
        Route::post('/', [RemiseController::class, 'store'])->name('store');
        Route::get('/{remise}', [RemiseController::class, 'show'])->name('show');
        Route::get('/{remise}/edit', [RemiseController::class, 'edit'])->name('edit');
        Route::put('/{remise}', [RemiseController::class, 'update'])->name('update');
        Route::delete('/{remise}', [RemiseController::class, 'destroy'])->name('destroy');
        Route::patch('/{remise}/toggle', [RemiseController::class, 'toggleActive'])->name('toggle');
    });

    // RAPPORTS FINANCIERS
    Route::prefix('admin/rapports')->name('admin.rapports.')->group(function () {
        Route::get('/financier', [RapportFinancierController::class, 'index'])->name('financier');
        Route::get('/financier/export', [RapportFinancierController::class, 'exporter'])->name('financier.export');
        Route::get('/financier/graphique', [RapportFinancierController::class, 'donneesGraphique'])->name('financier.graphique');
    });
});

// Vérification publique de l'authenticité d'un document (code imprimé sur le document)
Route::get('/verifier-document/{code?}', [DocumentController::class, 'verifier'])
    ->middleware('throttle:30,1')
    ->name('documents.verifier');

// Réimpression d'un document délivré (administration, ou le stagiaire concerné)
Route::get('/documents/{document}/telecharger', [DocumentController::class, 'telecharger'])
    ->middleware('auth')
    ->name('documents.telecharger');

// Portail parents (lecture seule)
Route::middleware(['auth', 'parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/', [ParentController::class, 'index'])->name('index');
    Route::get('/enfants/{stagiaire}', [ParentController::class, 'enfant'])->name('enfant');
    Route::get('/enfants/{stagiaire}/bulletins/{bulletin}', [ParentController::class, 'bulletin'])->name('bulletin');
    Route::get('/enfants/{stagiaire}/recus/{paiement}', [ParentController::class, 'recu'])->name('recu');
});

// Administration : accès parents depuis la fiche stagiaire
Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::post('stagiaires/{stagiaire}/parents', [ParentController::class, 'lier'])->name('stagiaires.parents.lier');
    Route::delete('stagiaires/{stagiaire}/parents/{parent}', [ParentController::class, 'delier'])->name('stagiaires.parents.delier');
});
