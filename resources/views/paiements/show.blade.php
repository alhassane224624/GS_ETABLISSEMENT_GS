@extends(auth()->user()->role === 'comptable' ? 'layouts.comptable' : 'layouts.app')

@section('title', 'Détails du Paiement')

@section('content')
<div class="container-fluid py-4">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-receipt text-primary me-2"></i>
                        Détails du Paiement
                    </h2>
                    <p class="text-muted mb-0">
                        Transaction N° <strong>{{ $paiement->numero_transaction }}</strong>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    @if($paiement->statut === 'valide' && $paiement->recu_path)
                        <a href="{{ route('paiements.recu', $paiement) }}" 
                           class="btn btn-success" 
                           target="_blank">
                            <i class="fas fa-file-pdf me-2"></i>Télécharger le reçu
                        </a>
                    @endif
                    <a href="{{ route('paiements.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Colonne principale -->
        <div class="col-lg-8">
            <!-- Statut du paiement -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Statut du paiement</h5>
                            <p class="text-muted mb-0">État actuel de la transaction</p>
                        </div>
                        <div>
                            @if($paiement->statut === 'valide')
                                <span class="badge bg-success px-4 py-3 fs-6">
                                    <i class="fas fa-check-circle me-2"></i>{{ $paiement->statut_libelle }}
                                </span>
                            @elseif($paiement->statut === 'en_attente')
                                <span class="badge bg-warning px-4 py-3 fs-6">
                                    <i class="fas fa-clock me-2"></i>{{ $paiement->statut_libelle }}
                                </span>
                            @elseif($paiement->statut === 'refuse')
                                <span class="badge bg-danger px-4 py-3 fs-6">
                                    <i class="fas fa-times-circle me-2"></i>{{ $paiement->statut_libelle }}
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($paiement->valide_at)
                        <div class="alert alert-success border-0 mt-3 mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Validé le {{ $paiement->valide_at->format('d/m/Y à H:i') }}
                            @if($paiement->validateur)
                                par <strong>{{ $paiement->validateur->name }}</strong>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Informations du paiement -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-gradient-primary text-white border-0">
                    <h5 class="mb-0 d-flex align-items-center">
                        <div class="icon-shape bg-white bg-opacity-25 rounded me-3">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        Informations du Paiement
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="text-muted mb-1 d-block">
                                    <i class="fas fa-hashtag me-1"></i>Numéro de transaction
                                </label>
                                <strong class="fs-5 text-primary">{{ $paiement->numero_transaction }}</strong>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="text-muted mb-1 d-block">
                                    <i class="fas fa-calendar me-1"></i>Date du paiement
                                </label>
                                <strong class="fs-5">{{ $paiement->date_paiement->format('d/m/Y') }}</strong>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="text-muted mb-1 d-block">
                                    <i class="fas fa-wallet me-1"></i>Montant
                                </label>
                                <strong class="fs-4 text-success">{{ number_format($paiement->montant, 2) }} DH</strong>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="text-muted mb-1 d-block">
                                    <i class="fas fa-tag me-1"></i>Type de paiement
                                </label>
                                <span class="badge bg-info fs-6 px-3 py-2">{{ $paiement->type_libelle }}</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="text-muted mb-1 d-block">
                                    <i class="fas fa-credit-card me-1"></i>Méthode de paiement
                                </label>
                                <strong>{{ $paiement->methode_libelle }}</strong>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="text-muted mb-1 d-block">
                                    <i class="fas fa-user me-1"></i>Enregistré par
                                </label>
                                <strong>{{ $paiement->user->name ?? 'N/A' }}</strong>
                            </div>
                        </div>

                        @if($paiement->description)
                        <div class="col-12">
                            <div class="info-item">
                                <label class="text-muted mb-1 d-block">
                                    <i class="fas fa-comment me-1"></i>Description
                                </label>
                                <p class="mb-0">{{ $paiement->description }}</p>
                            </div>
                        </div>
                        @endif

                        @if($paiement->justificatif_path)
                        <div class="col-12">
                            <div class="info-item">
                                <label class="text-muted mb-1 d-block">
                                    <i class="fas fa-paperclip me-1"></i>Justificatif
                                </label>
                                <a href="{{ Storage::url($paiement->justificatif_path) }}" 
                                   target="_blank" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download me-1"></i>Télécharger le justificatif
                                </a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Échéanciers affectés -->
            @if($paiement->echeanciers->count() > 0)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="fas fa-calendar-check text-success me-2"></i>
                        Échéanciers Affectés
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Titre</th>
                                    <th>Date d'échéance</th>
                                    <th>Montant affecté</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paiement->echeanciers as $ech)
                                <tr>
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
                                            {{ number_format($ech->pivot->montant_affecte, 2) }} DH
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $ech->statut === 'paye' ? 'success' : 'warning' }}">
                                            {{ $ech->statut_libelle }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Colonne latérale -->
        <div class="col-lg-4">
            <!-- Informations du stagiaire -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-gradient-info text-white border-0">
                    <h6 class="mb-0 d-flex align-items-center">
                        <i class="fas fa-user-graduate me-2"></i>
                        Stagiaire
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <div class="avatar-circle bg-primary text-white mx-auto mb-3">
                            <span class="fs-3">{{ substr($paiement->stagiaire->nom, 0, 1) }}{{ substr($paiement->stagiaire->prenom, 0, 1) }}</span>
                        </div>
                        <h5 class="mb-1">{{ $paiement->stagiaire->nom_complet }}</h5>
                        <p class="text-muted mb-0">{{ $paiement->stagiaire->matricule }}</p>
                    </div>

                    <hr>

                    <div class="info-group">
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Filière</label>
                            <div>
                                <i class="fas fa-graduation-cap text-primary me-2"></i>
                                <strong>{{ $paiement->stagiaire->filiere->nom ?? 'N/A' }}</strong>
                            </div>
                        </div>

                        @if($paiement->stagiaire->classe)
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Classe</label>
                            <div>
                                <i class="fas fa-users text-primary me-2"></i>
                                <strong>{{ $paiement->stagiaire->classe->nom }}</strong>
                            </div>
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="text-muted small mb-1">Total payé</label>
                            <div>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong class="text-success">{{ number_format($paiement->stagiaire->total_paye, 2) }} DH</strong>
                            </div>
                        </div>

                        <div>
                            <label class="text-muted small mb-1">Solde restant</label>
                            <div>
                                <i class="fas fa-exclamation-circle text-danger me-2"></i>
                                <strong class="text-danger">{{ number_format($paiement->stagiaire->solde_restant, 2) }} DH</strong>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <a href="{{ route('paiements.historique', $paiement->stagiaire) }}" 
                       class="btn btn-outline-primary w-100">
                        <i class="fas fa-history me-2"></i>Historique des paiements
                    </a>
                </div>
            </div>

            <!-- Actions -->
            @if($paiement->statut === 'en_attente')
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0">
                        <i class="fas fa-tools me-2"></i>Actions
                    </h6>
                </div>
                <div class="card-body p-3">
                    <button type="button" 
                            class="btn btn-success w-100 mb-2" 
                            data-bs-toggle="modal" 
                            data-bs-target="#validerModal">
                        <i class="fas fa-check me-2"></i>Valider le paiement
                    </button>
                    
                    <button type="button" 
                            class="btn btn-danger w-100" 
                            data-bs-toggle="modal" 
                            data-bs-target="#refuserModal">
                        <i class="fas fa-times me-2"></i>Refuser le paiement
                    </button>
                </div>
            </div>
            @endif

            <!-- Notes administratives -->
            @if($paiement->notes_admin)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0">
                        <i class="fas fa-sticky-note me-2"></i>Notes Administratives
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0 small">{{ $paiement->notes_admin }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Valider -->
@if($paiement->statut === 'en_attente')
<div class="modal fade" id="validerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('paiements.valider', $paiement) }}">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>Valider le paiement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Confirmez-vous la validation de ce paiement ?</p>
                    
                    <div class="alert alert-info border-0">
                        <ul class="list-unstyled mb-0">
                            <li><strong>Montant :</strong> {{ number_format($paiement->montant, 2) }} DH</li>
                            <li><strong>Stagiaire :</strong> {{ $paiement->stagiaire->nom_complet }}</li>
                            <li><strong>Méthode :</strong> {{ $paiement->methode_libelle }}</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes complémentaires (optionnel)</label>
                        <textarea name="notes_admin" class="form-control" rows="3" placeholder="Ajouter des notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Valider le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Refuser -->
<div class="modal fade" id="refuserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('paiements.refuser', $paiement) }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-times-circle me-2"></i>Refuser le paiement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning border-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Cette action notifiera le stagiaire du refus.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            Motif du refus <span class="text-danger">*</span>
                        </label>
                        <textarea name="motif_refus" 
                                  class="form-control" 
                                  rows="4" 
                                  required
                                  placeholder="Expliquez la raison du refus..."></textarea>
                        <small class="text-muted">Ce message sera visible par le stagiaire.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Refuser le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

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
    
    .bg-gradient-info {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    
    .avatar-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    
    .info-item {
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 0.5rem;
        transition: all 0.3s;
    }
    
    .info-item:hover {
        background: #e9ecef;
    }
</style>
@endpush
@endsection