@extends('layouts.app')

@section('title', 'Import des Stagiaires')
@section('page-title', 'Import Excel - Stagiaires')

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="action-card">
                <h5 class="mb-4">
                    <i class="fas fa-upload me-2 text-primary"></i>
                    Import en Lot des Stagiaires
                </h5>

                @if($errors->any())
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Erreurs :</h6>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('import_errors') && count(session('import_errors')) > 0)
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Erreurs lors de l'import :</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Ligne</th>
                                        <th>Erreur</th>
                                        <th>Données</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(session('import_errors') as $error)
                                        <tr>
                                            <td>{{ $error['ligne'] ?? 'N/A' }}</td>
                                            <td class="text-danger">{{ $error['message'] }}</td>
                                            <td><small>{{ json_encode($error['data'] ?? []) }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Instructions -->
                <div class="bg-light rounded p-4 mb-4">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-info-circle me-2"></i>Instructions d'import
                    </h6>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Format Excel/CSV requis :</h6>
                            <ul class="small">
                                <li><strong>nom</strong> : Nom du stagiaire (obligatoire)</li>
                                <li><strong>prenom</strong> : Prénom (obligatoire)</li>
                                <li><strong>email</strong> : E-mail du stagiaire (obligatoire, sert d'identifiant)</li>
                                <li><strong>telephone</strong> : Téléphone (optionnel)</li>
                                <li><strong>matricule</strong> : optionnel, généré automatiquement si vide</li>
                                <li><strong>filiere_nom</strong> : Nom de la filière (optionnel si filière par défaut)</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Conseils :</h6>
                            <ul class="small">
                                <li>Première ligne = en-têtes de colonnes</li>
                                <li>Un rapport CSV avec les mots de passe provisoires est téléchargé à la fin</li>
                                <li>Matricules doivent être uniques</li>
                                <li>Noms de filières doivent exister</li>
                                <li>Maximum 500 lignes par import</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="{{ route('import.template') }}" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-download me-2"></i>Télécharger le modèle Excel
                        </a>
                    </div>
                </div>

                <!-- Formulaire d'import -->
                <form method="POST" action="{{ route('import.stagiaires') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="row mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Fichier Excel/CSV *</label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" 
                                   accept=".xlsx,.csv,.xls" required>
                            <small class="text-muted">Formats acceptés: .xlsx, .csv, .xls (max: 2MB)</small>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Filière par défaut (optionnel)</label>
                            <select name="filiere_id" class="form-select @error('filiere_id') is-invalid @enderror">
                                <option value="">Utiliser colonne 'filiere_nom'</option>
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id }}" {{ old('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                        {{ $filiere->nom }} - {{ $filiere->niveau }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Si spécifiée, tous les stagiaires seront ajoutés à cette filière</small>
                            @error('filiere_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Options avancées -->
                    <div class="border rounded p-3 mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-cog me-2"></i>Options d'import
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="skip_duplicates" id="skipDuplicates" checked>
                                    <label class="form-check-label" for="skipDuplicates">
                                        Ignorer les doublons (matricules existants)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="update_existing" id="updateExisting">
                                    <label class="form-check-label" for="updateExisting">
                                        Mettre à jour les stagiaires existants
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="create_missing_filieres" id="createFilieres">
                                    <label class="form-check-label" for="createFilieres">
                                        Créer les filières manquantes
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="send_notifications" id="sendNotifications" checked>
                                    <label class="form-check-label" for="sendNotifications">
                                        Envoyer notifications d'import
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preview zone -->
                    <div id="previewZone" class="border rounded p-3 mb-4" style="display: none;">
                        <h6 class="text-info mb-3">
                            <i class="fas fa-eye me-2"></i>Aperçu des données
                        </h6>
                        <div id="previewContent">
                            <!-- Contenu généré dynamiquement -->
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('stagiaires.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                        
                        <div>
                            <button type="button" class="btn btn-info" onclick="previewFile()" id="previewBtn" disabled>
                                <i class="fas fa-eye me-2"></i>Aperçu
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-upload me-2"></i>Lancer l'import
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Historique des imports -->
        <div class="col-lg-4">
            <div class="action-card">
                <h5 class="mb-3">
                    <i class="fas fa-history me-2"></i>Derniers Imports
                </h5>
                
                @php
                    // Simuler un historique d'imports (remplacer par vos vraies données)
                    $derniers_imports = [
                        [
                            'date' => '2024-12-15 14:30',
                            'fichier' => 'stagiaires_2024.xlsx',
                            'success' => 25,
                            'errors' => 2,
                            'user' => 'Admin'
                        ],
                        [
                            'date' => '2024-12-10 09:15',
                            'fichier' => 'nouveaux_stagiaires.csv',
                            'success' => 15,
                            'errors' => 0,
                            'user' => 'Admin'
                        ]
                    ];
                @endphp
                
                @if(count($derniers_imports) > 0)
                    <div class="list-group list-group-flush">
                        @foreach($derniers_imports as $import)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $import['fichier'] }}</h6>
                                        <p class="mb-1 small text-muted">Par {{ $import['user'] }}</p>
                                        <small class="text-muted">{{ $import['date'] }}</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success">{{ $import['success'] }} ✓</span>
                                        @if($import['errors'] > 0)
                                            <span class="badge bg-danger">{{ $import['errors'] }} ✗</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center py-3">
                        <i class="fas fa-inbox me-2"></i>Aucun import récent
                    </p>
                @endif
            </div>

            <!-- Guide rapide -->
            <div class="action-card mt-3">
                <h6 class="mb-3">
                    <i class="fas fa-lightbulb me-2"></i>Exemple de fichier CSV
                </h6>
                <div class="bg-dark text-light p-2 rounded small">
                    <pre style="margin: 0; font-size: 10px;">nom,prenom,email,telephone,matricule,filiere_nom
DUPONT,Jean,jean@mail.com,0600000000,,Informatique
MARTIN,Marie,marie@mail.com,0611111111,,Gestion</pre>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.querySelector('input[name="file"]');
    const previewBtn = document.getElementById('previewBtn');
    const previewZone = document.getElementById('previewZone');
    const previewContent = document.getElementById('previewContent');

    // Activer le bouton aperçu quand un fichier est sélectionné
    fileInput.addEventListener('change', function() {
        previewBtn.disabled = !this.files.length;
        previewZone.style.display = 'none';
    });

    // Gérer les conflits d'options
    document.getElementById('skipDuplicates').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('updateExisting').checked = false;
        }
    });

    document.getElementById('updateExisting').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('skipDuplicates').checked = false;
        }
    });
});

function previewFile() {
    const fileInput = document.querySelector('input[name="file"]');
    const file = fileInput.files[0];
    
    if (!file) {
        alert('Veuillez sélectionner un fichier');
        return;
    }

    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    // Afficher un loader
    document.getElementById('previewContent').innerHTML = `
        <div class="text-center py-3">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2">Analyse du fichier en cours...</p>
        </div>
    `;
    document.getElementById('previewZone').style.display = 'block';

    // Simuler un aperçu (remplacer par votre logique réelle)
    setTimeout(() => {
        document.getElementById('previewContent').innerHTML = `
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Ligne</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Matricule</th>
                            <th>Filière</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>DUPONT</td>
                            <td>Jean</td>
                            <td>ST001</td>
                            <td>Informatique</td>
                            <td><span class="badge bg-success">OK</span></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>MARTIN</td>
                            <td>Marie</td>
                            <td>ST002</td>
                            <td>Gestion</td>
                            <td><span class="badge bg-success">OK</span></td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>BERNARD</td>
                            <td>Paul</td>
                            <td>ST001</td>
                            <td>Informatique</td>
                            <td><span class="badge bg-warning">Doublon</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                <small class="text-success">✓ 2 lignes valides</small> |
                <small class="text-warning">⚠ 1 doublon détecté</small>
            </div>
        `;
    }, 2000);
}
</script>
@endpush