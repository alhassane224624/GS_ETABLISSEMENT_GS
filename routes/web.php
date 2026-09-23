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
    ->name('stagiaires.inscription.store');

// ============================================================================
// ROUTES AUTHENTIFIÃ‰ES
// ============================================================================

Route::middleware(['auth', 'verified'])->group(function () {
    
    // âœ… REDIRECTION INTELLIGENTE SELON LE RÃ”LE
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
            default => redirect()->route('login')->with('error', 'RÃ´le non reconnu'),
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
// âœ… ROUTES COMPTABLE
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
    Route::get('/absences', [StagiaireSpaceController::class, 'mesAbsences'])->name('absences');
    Route::get('/profil', [StagiaireSpaceController::class, 'monProfil'])->name('profil');
    
    // ðŸ’° PAIEMENTS STAGIAIRE
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
    
    Route::get('stagiaires-export', [StagiaireController::class, 'export'])->name('stagiaires.export');
    Route::get('/presences', [ProfesseurController::class, 'presences'])->name('presences');
    Route::post('/presences/marquer', [ProfesseurController::class, 'marquerAbsence'])->name('presences.marquer');
    Route::delete('/presences/{absence}', [ProfesseurController::class, 'supprimerAbsence'])->name('presences.supprimer');
    Route::get('/planning', [ProfesseurController::class, 'monPlanning'])->name('planning');
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
    
    Route::resource('notes', NoteController::class);
    Route::get('/notes/export', [NoteController::class, 'export'])->name('notes.export');
    Route::get('stagiaires/{stagiaire}/releve', [NoteController::class, 'releveStagiaire'])->name('notes.releve');
    
    Route::resource('salles', SalleController::class);
    Route::patch('salles/{salle}/toggle-disponibilite', [SalleController::class, 'toggleDisponibilite'])->name('salles.toggle-disponibilite');
    Route::get('salles/{salle}/planning', [SalleController::class, 'planning'])->name('salles.planning');
    Route::post('salles/check-disponibilite', [SalleController::class, 'checkDisponibilite'])->name('salles.check-disponibilite');
    
    Route::resource('planning', PlanningController::class);
    Route::post('planning/{planning}/valider', [PlanningController::class, 'valider'])->name('planning.valider');
    Route::post('planning/{planning}/annuler', [PlanningController::class, 'annuler'])->name('planning.annuler');
    
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
    Route::get('/statistics/export', [StatisticsController::class, 'export'])->name('statistics.export');
});

// ============================================================================
// ðŸ’³ SYSTÃˆME DE PAIEMENT - ACCESSIBLE ADMIN & COMPTABLE
// ============================================================================

Route::middleware(['auth', 'verified', 'financial'])->group(function () {
    // PAIEMENTS
    Route::prefix('paiements')->name('paiements.')->group(function () {
        Route::get('/', [PaiementController::class, 'index'])->name('index');
        Route::get('/create', [PaiementController::class, 'create'])->name('create');
        Route::post('/', [PaiementController::class, 'store'])->name('store');
        Route::get('/{paiement}', [PaiementController::class, 'show'])->name('show');
        Route::post('/{paiement}/valider', [PaiementController::class, 'valider'])->name('valider');
        Route::post('/{paiement}/refuser', [PaiementController::class, 'refuser'])->name('refuser');
        Route::get('/{paiement}/recu', [PaiementController::class, 'telechargerRecu'])->name('recu');
        Route::get('/stagiaire/{stagiaire}/historique', [PaiementController::class, 'historique'])->name('historique');
    });

    // Ã‰CHÃ‰ANCIERS
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