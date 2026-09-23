@extends(auth()->user()->role === 'comptable' ? 'layouts.comptable' : 'layouts.app')

@section('title', 'Enregistrer un Paiement')

@section('content')
<div class="container-fluid py-4">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-cash-register text-primary me-2"></i>
                        Enregistrer un Paiement
                    </h2>
                    <p class="text-muted mb-0">Enregistrez un nouveau paiement pour un stagiaire</p>
                </div>
                <a href="{{ route('paiements.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                </a>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('paiements.store') }}" enctype="multipart/form-data">
        @csrf
        
        <div class="row g-4">
            {{-- Colonne principale --}}
            <div class="col-lg-8">
                {{-- Sélection du stagiaire --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-gradient-primary text-white border-0">
                        <h5 class="mb-0 d-flex align-items-center">
                            <div class="icon-shape bg-white bg-opacity-25 rounded me-3">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            Informations du Stagiaire
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Sélectionner un stagiaire 
                                <span class="text-danger">*</span>
                            </label>
                            <select name="stagiaire_id" 
                                    id="stagiaireSelect" 
                                    class="form-select form-select-lg @error('stagiaire_id') is-invalid @enderror" 
                                    required>
                                <option value="">-- Choisir un stagiaire --</option>
                                @foreach($stagiaires as $s)
                                <option value="{{ $s->id }}" 
                                        data-filiere="{{ $s->filiere->nom ?? 'N/A' }}"
                                        data-matricule="{{ $s->matricule }}"
                                        data-solde="{{ $s->solde_restant }}"
                                        {{ old('stagiaire_id', $stagiaire->id ?? '') == $s->id ? 'selected' : '' }}>
                                    {{ $s->nom_complet }} - {{ $s->matricule }}
                                </option>
                                @endforeach
                            </select>
                            @error('stagiaire_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Informations du stagiaire sélectionné --}}
                        <div id="stagiaireInfo" class="d-none">
                            <div class="alert alert-info border-0 shadow-sm" role="alert">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-graduation-cap text-primary me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Filière</small>
                                                <strong id="infoFiliere">-</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-id-card text-primary me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Matricule</small>
                                                <strong id="infoMatricule">-</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-wallet text-danger me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Solde restant</small>
                                                <strong class="text-danger fs-5" id="infoSolde">-</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($stagiaire)
                        {{-- Échéanciers impayés --}}
                        <div class="mt-4">
                            <h6 class="mb-3 d-flex align-items-center">
                                <i class="fas fa-calendar-alt text-warning me-2"></i>
                                Échéanciers à payer
                            </h6>
                            @if($stagiaire->echeanciersImpayes->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="50">
                                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                                </th>
                                                <th>Titre</th>
                                                <th>Échéance</th>
                                                <th>Montant</th>
                                                <th>Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($stagiaire->echeanciersImpayes as $ech)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" 
                                                           name="echeanciers[]" 
                                                           value="{{ $ech->id }}" 
                                                           class="form-check-input echeancier-checkbox">
                                                </td>
                                                <td>
                                                    <strong>{{ $ech->titre }}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        {{ $ech->date_echeance->format('d/m/Y') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong class="text-success">
                                                        {{ number_format($ech->montant_restant, 2) }} DH
                                                    </strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $ech->is_en_retard ? 'danger' : 'warning' }}">
                                                        <i class="fas fa-{{ $ech->is_en_retard ? 'exclamation-triangle' : 'clock' }} me-1"></i>
                                                        {{ $ech->statut_libelle }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="alert alert-warning border-0 mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <small>Si aucun échéancier n'est sélectionné, le paiement sera affecté automatiquement aux plus anciens.</small>
                                </div>
                            @else
                                <div class="alert alert-success border-0">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Aucun échéancier impayé pour ce stagiaire
                                </div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Détails du paiement --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gradient-success text-white border-0">
                        <h5 class="mb-0 d-flex align-items-center">
                            <div class="icon-shape bg-white bg-opacity-25 rounded me-3">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            Détails du Paiement
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <!-- Montant -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Montant (DH) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-money-bill-wave text-success"></i>
                                    </span>
                                    <input type="number" 
                                           name="montant" 
                                           class="form-control @error('montant') is-invalid @enderror" 
                                           step="0.01" 
                                           min="1" 
                                           value="{{ old('montant') }}"
                                           placeholder="0.00"
                                           required>
                                    <span class="input-group-text bg-light">DH</span>
                                    @error('montant')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Date du paiement -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Date du paiement <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-calendar text-primary"></i>
                                    </span>
                                    <input type="date" 
                                           name="date_paiement" 
                                           class="form-control @error('date_paiement') is-invalid @enderror" 
                                           value="{{ old('date_paiement', now()->format('Y-m-d')) }}" 
                                           required>
                                    @error('date_paiement')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Type de paiement -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Type de paiement <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-tag text-info"></i>
                                    </span>
                                    <select name="type_paiement" 
                                            class="form-select @error('type_paiement') is-invalid @enderror" 
                                            required>
                                        <option value="">-- Choisir --</option>
                                        <option value="inscription" {{ old('type_paiement') == 'inscription' ? 'selected' : '' }}>
                                            Frais d'inscription
                                        </option>
                                        <option value="mensualite" {{ old('type_paiement') == 'mensualite' ? 'selected' : '' }}>
                                            Mensualité
                                        </option>
                                        <option value="examen" {{ old('type_paiement') == 'examen' ? 'selected' : '' }}>
                                            Frais d'examen
                                        </option>
                                        <option value="autre" {{ old('type_paiement') == 'autre' ? 'selected' : '' }}>
                                            Autre
                                        </option>
                                    </select>
                                    @error('type_paiement')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Méthode de paiement -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Méthode de paiement <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-credit-card text-warning"></i>
                                    </span>
                                    <select name="methode_paiement" 
                                            class="form-select @error('methode_paiement') is-invalid @enderror" 
                                            required>
                                        <option value="">-- Choisir --</option>
                                        <option value="especes" {{ old('methode_paiement') == 'especes' ? 'selected' : '' }}>
                                            💵 Espèces
                                        </option>
                                        <option value="virement" {{ old('methode_paiement') == 'virement' ? 'selected' : '' }}>
                                            🏦 Virement bancaire
                                        </option>
                                        <option value="cheque" {{ old('methode_paiement') == 'cheque' ? 'selected' : '' }}>
                                            📝 Chèque
                                        </option>
                                        <option value="carte" {{ old('methode_paiement') == 'carte' ? 'selected' : '' }}>
                                            💳 Carte bancaire
                                        </option>
                                        <option value="mobile_money" {{ old('methode_paiement') == 'mobile_money' ? 'selected' : '' }}>
                                            📱 Mobile Money
                                        </option>
                                    </select>
                                    @error('methode_paiement')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" 
                                          class="form-control @error('description') is-invalid @enderror" 
                                          rows="3" 
                                          placeholder="Ex: Paiement mensualité Janvier 2024">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Justificatif -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-paperclip me-1"></i>
                                    Justificatif (optionnel)
                                </label>
                                <input type="file" 
                                       name="justificatif" 
                                       class="form-control form-control-lg @error('justificatif') is-invalid @enderror" 
                                       accept=".pdf,.jpg,.jpeg,.png">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Formats acceptés : PDF, JPG, PNG (Max: 5 Mo)
                                </small>
                                @error('justificatif')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Colonne latérale --}}
            <div class="col-lg-4">
                {{-- Notes administratives --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light border-0">
                        <h6 class="mb-0 d-flex align-items-center">
                            <i class="fas fa-sticky-note text-secondary me-2"></i>
                            Notes Administratives
                        </h6>
                    </div>
                    <div class="card-body">
                        <textarea name="notes_admin" 
                                  class="form-control" 
                                  rows="4" 
                                  placeholder="Notes internes (non visibles par le stagiaire)">{{ old('notes_admin') }}</textarea>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-lock me-1"></i>
                            Ces notes sont privées et à usage interne uniquement
                        </small>
                    </div>
                </div>

                {{-- Informations importantes --}}
                <div class="card border-0 border-start border-primary border-4 bg-light mb-4">
                    <div class="card-body">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Informations Importantes
                        </h6>
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2 d-flex">
                                <i class="fas fa-check text-success me-2 mt-1"></i>
                                <span>Les paiements en <strong>espèces</strong> sont validés automatiquement</span>
                            </li>
                            <li class="mb-2 d-flex">
                                <i class="fas fa-clock text-warning me-2 mt-1"></i>
                                <span>Les autres méthodes nécessitent une validation manuelle</span>
                            </li>
                            <li class="mb-2 d-flex">
                                <i class="fas fa-file-pdf text-danger me-2 mt-1"></i>
                                <span>Un reçu sera généré automatiquement après validation</span>
                            </li>
                            <li class="d-flex">
                                <i class="fas fa-bell text-info me-2 mt-1"></i>
                                <span>Le stagiaire recevra une notification par email</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Boutons d'action --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="fas fa-save me-2"></i>
                            Enregistrer le paiement
                        </button>
                        <a href="{{ route('paiements.index') }}" class="btn btn-outline-secondary btn-lg w-100">
                            <i class="fas fa-times me-2"></i>
                            Annuler
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
    .icon-shape {
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .bg-gradient-success {
        background: linear-gradient(135deg, #0ba360 0%, #3cba92 100%);
    }
    
    .form-select-lg,
    .form-control-lg {
        font-size: 1rem;
        padding: 0.75rem 1rem;
    }
    
    .input-group-text {
        border: 1px solid #dee2e6;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
    
    .border-4 {
        border-width: 4px !important;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stagiaireSelect = document.getElementById('stagiaireSelect');
    const stagiaireInfo = document.getElementById('stagiaireInfo');
    const selectAllCheckbox = document.getElementById('selectAll');
    const echeancierCheckboxes = document.querySelectorAll('.echeancier-checkbox');
    
    // Afficher les informations du stagiaire sélectionné
    stagiaireSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        
        if (this.value) {
            document.getElementById('infoFiliere').textContent = option.dataset.filiere;
            document.getElementById('infoMatricule').textContent = option.dataset.matricule;
            document.getElementById('infoSolde').textContent = parseFloat(option.dataset.solde).toFixed(2) + ' DH';
            stagiaireInfo.classList.remove('d-none');
        } else {
            stagiaireInfo.classList.add('d-none');
        }
    });
    
    // Sélectionner/Désélectionner tous les échéanciers
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            echeancierCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Mettre à jour le checkbox "Tout sélectionner"
        echeancierCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(echeancierCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(echeancierCheckboxes).some(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            });
        });
    }
    
    // Trigger au chargement si un stagiaire est pré-sélectionné
    if (stagiaireSelect.value) {
        stagiaireSelect.dispatchEvent(new Event('change'));
    }
    
    // Validation du formulaire
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const montant = parseFloat(document.querySelector('input[name="montant"]').value);
        const soldeText = document.getElementById('infoSolde').textContent;
        const soldeRestant = parseFloat(soldeText.replace(' DH', ''));
        
        if (montant > soldeRestant && !isNaN(soldeRestant)) {
            e.preventDefault();
            alert('⚠️ Le montant saisi (' + montant.toFixed(2) + ' DH) dépasse le solde restant (' + soldeRestant.toFixed(2) + ' DH)');
            return false;
        }
    });
});
</script>
@endpush
@endsection