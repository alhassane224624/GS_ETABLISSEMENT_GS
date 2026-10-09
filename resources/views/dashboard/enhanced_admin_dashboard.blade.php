<!-- Modal Import Stagiaires -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-upload me-2"></i>Import Excel - Stagiaires
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Format attendu</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Colonnes requises :</strong>
                                    <ul class="small mb-0">
                                        <li>nom (obligatoire)</li>
                                        <li>prenom (obligatoire)</li>
                                        <li>matricule (obligatoire, unique)</li>
                                        <li>filiere_nom (optionnel si filière par défaut)</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <strong>Exemple :</strong>
                                    <div class="bg-dark text-light p-2 rounded small">
                                        <code>nom,prenom,matricule,filiere_nom<br>
DUPONT,Jean,ST001,Informatique<br>
MARTIN,Marie,ST002,Gestion</code>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="{{ route('import.template') }}" class="btn btn-sm btn-success">
                                    <i class="fas fa-download me-1"></i>Télécharger modèle
                                </a>
                            </div>
                        </div>
                        
                        <form id="importForm" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">Fichier Excel/CSV *</label>
                                <input type="file" name="file" class="form-control" accept=".xlsx,.csv,.xls" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Filière par défaut (optionnel)</label>
                                <select name="filiere_id" class="form-select">
                                    <option value="">Utiliser la colonne filiere_nom</option>
                                    @if(isset($filieres))
                                        @foreach($filieres as $filiere)
                                            <option value="{{ $filiere->id }}">{{ $filiere->nom }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="skip_duplicates" checked>
                                        <label class="form-check-label">
                                            Ignorer les doublons
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="create_missing_filieres">
                                        <label class="form-check-label">
                                            Créer filières manquantes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="bg-light p-3 rounded">
                            <h6 class="text-primary">Conseils d'import</h6>
                            <ul class="small">
                                <li>Maximum 500 lignes par fichier</li>
                                <li>Première ligne = en-têtes</li>
                                <li>Matricules doivent être uniques</li>
                                <li>Noms de filières sensibles à la casse</li>
                            </ul>
                        </div>
                        
                        <div class="mt-3">
                            <h6>Derniers imports</h6>
                            <div class="small">
                                <div class="d-flex justify-content-between">
                                    <span>15/12/2024</span>
                                    <span class="text-success">25 ✓</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>10/12/2024</span>
                                    <span class="text-success">15 ✓</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-info" onclick="previewImport()">
                    <i class="fas fa-eye me-2"></i>Aperçu
                </button>
                <button type="button" class="btn btn-success" onclick="submitImport()">
                    <i class="fas fa-upload me-2"></i>Lancer l'import
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sauvegardes -->
<div class="modal fade" id="backupModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-database me-2"></i>Gestion des Sauvegardes
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Nouvelle Sauvegarde</h6>
                        <p class="small text-muted">Créez une sauvegarde de votre système :</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="createBackup('database')">
                                <i class="fas fa-database me-2"></i>Base de données seulement
                                <br><small class="text-muted">Structure + données, rapide</small>
                            </button>
                            <button class="btn btn-outline-warning" onclick="createBackup('files')">
                                <i class="fas fa-folder me-2"></i>Fichiers seulement
                                <br><small class="text-muted">Photos, documents, exports</small>
                            </button>
                            <button class="btn btn-outline-success" onclick="createBackup('full')">
                                <i class="fas fa-server me-2"></i>Sauvegarde complète
                                <br><small class="text-muted">Tout le système (recommandé)</small>
                            </button>
                        </div>
                        
                        <div class="mt-3" id="backup-status" style="display: none;">
                            <div class="progress mb-2">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted" id="backup-message">Initialisation...</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Sauvegardes Existantes</h6>
                        <div class="backup-list" style="max-height: 300px; overflow-y: auto;">
                            <!-- Simuler quelques sauvegardes -->
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <strong>backup_full_2024_12_15</strong>
                                    <br><small class="text-muted">15/12/2024 - 2.3 MB</small>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-info" onclick="downloadBackup('backup_full_2024_12_15.zip')">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteBackup('backup_full_2024_12_15.zip')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <strong>backup_db_2024_12_10</strong>
                                    <br><small class="text-muted">10/12/2024 - 450 KB</small>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-info" onclick="downloadBackup('backup_db_2024_12_10.zip')">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteBackup('backup_db_2024_12_10.zip')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3 p-2 bg-light rounded">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Les sauvegardes sont automatiquement supprimées après 30 jours.
                                Téléchargez-les pour les conserver.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-warning" onclick="scheduleBackup()">
                    <i class="fas fa-clock me-2"></i>Programmer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Statistiques Rapides -->
<div class="modal fade" id="statsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-line me-2"></i>Statistiques Rapides
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center p-3 border rounded">
                            <i class="fas fa-users fa-2x text-primary mb-2"></i>
                            <h3>{{ $data['total_stagiaires'] ?? 0 }}</h3>
                            <p class="mb-0">Stagiaires Total</p>
                            <small class="text-muted">+5 ce mois</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 border rounded">
                            <i class="fas fa-clipboard-list fa-2x text-success mb-2"></i>
                            <h3>{{ $data['total_notes'] ?? 0 }}</h3>
                            <p class="mb-0">Notes Saisies</p>
                            <small class="text-muted">+25 ce mois</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 border rounded">
                            <i class="fas fa-percentage fa-2x text-warning mb-2"></i>
                            <h3>85%</h3>
                            <p class="mb-0">Taux Réussite</p>
                            <small class="text-muted">Moyenne générale</small>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h6>Top 5 Filières</h6>
                        <div class="list-group list-group-flush">
                            @if(isset($data['filieres_stats']))
                                @foreach($data['filieres_stats']->take(5) as $filiere)
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span>{{ $filiere->nom }}</span>
                                        <span class="badge bg-primary">{{ $filiere->stagiaires_count }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>Activité Récente</h6>
                        <div class="small">
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-user-plus text-success me-1"></i>Nouvel étudiant</span>
                                <span class="text-muted">Il y a 2h</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-edit text-warning me-1"></i>Note modifiée</span>
                                <span class="text-muted">Il y a 4h</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-file-pdf text-danger me-1"></i>Bulletin généré</span>
                                <span class="text-muted">Hier</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="{{ route('statistics.index') }}" class="btn btn-primary">
                    <i class="fas fa-chart-bar me-2"></i>Voir toutes les stats
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Fonctions pour les modales
function submitImport() {
    const form = document.getElementById('importForm');
    const formData = new FormData(form);
    
    // Simulation de l'import
    const modal = bootstrap.Modal.getInstance(document.getElementById('importModal'));
    modal.hide();
    
    // Redirection vers la page d'import réelle
    window.location.href = '{{ route("import.stagiaires.form") }}';
}

function previewImport() {
    alert('Fonctionnalité d\'aperçu : Analysera le fichier avant import');
}

function createBackup(type) {
    const statusDiv = document.getElementById('backup-status');
    const progressBar = statusDiv.querySelector('.progress-bar');
    const messageDiv = document.getElementById('backup-message');
    
    statusDiv.style.display = 'block';
    
    // Simulation du processus de sauvegarde
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 20;
        if (progress >= 100) {
            progress = 100;
            progressBar.style.width = '100%';
            messageDiv.textContent = `Sauvegarde ${type} créée avec succès!`;
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-success');
            clearInterval(interval);
            
            // Masquer après 3 secondes
            setTimeout(() => {
                statusDiv.style.display = 'none';
                progressBar.style.width = '0%';
                progressBar.classList.add('progress-bar-animated');
                progressBar.classList.remove('bg-success');
            }, 3000);
        } else {
            progressBar.style.width = progress + '%';
            messageDiv.textContent = `Création de la sauvegarde ${type}... ${Math.round(progress)}%`;
        }
    }, 200);
}

function downloadBackup(filename) {
    alert(`Téléchargement de ${filename} démarré`);
}

function deleteBackup(filename) {
    if (confirm(`Supprimer la sauvegarde ${filename} ?`)) {
        alert(`Sauvegarde ${filename} supprimée`);
    }
}

function scheduleBackup() {
    alert('Configuration des sauvegardes automatiques (à développer)');
}
</script>