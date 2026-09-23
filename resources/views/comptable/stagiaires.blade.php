@extends('layouts.comptable')

@section('title', 'Liste des Stagiaires')

@section('content')
<div class="animate-fade-in-up">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Gestion des Stagiaires</h2>
            <p class="text-muted mb-0">
                <i class="fas fa-users me-1"></i>
                {{ $stagiaires->total() }} stagiaire(s) actif(s)
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="window.print()">
                <i class="fas fa-print me-1"></i>Imprimer
            </button>
            <a href="{{ route('stagiaires.export') }}" class="btn btn-success">
                <i class="fas fa-file-excel me-1"></i>Exporter
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm stat-card mb-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">
                <i class="fas fa-filter text-primary me-2"></i>
                Filtres de recherche
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('comptable.stagiaires') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Recherche</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Nom, prénom, matricule..." 
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Filière</label>
                    <select name="filiere_id" class="form-select">
                        <option value="">Toutes les filières</option>
                        @foreach($filieres as $filiere)
                            <option value="{{ $filiere->id }}" 
                                    {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                {{ $filiere->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Statut de paiement</label>
                    <select name="statut_paiement" class="form-select">
                        <option value="">Tous</option>
                        <option value="a_jour" {{ request('statut_paiement') == 'a_jour' ? 'selected' : '' }}>
                            À jour
                        </option>
                        <option value="paiement_partiel" {{ request('statut_paiement') == 'paiement_partiel' ? 'selected' : '' }}>
                            Paiement partiel
                        </option>
                        <option value="en_retard" {{ request('statut_paiement') == 'en_retard' ? 'selected' : '' }}>
                            En retard
                        </option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-search me-1"></i>Filtrer
                    </button>
                    <a href="{{ route('comptable.stagiaires') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des stagiaires -->
    <div class="card border-0 shadow-sm stat-card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">
                <i class="fas fa-list text-primary me-2"></i>
                Liste des stagiaires
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Matricule</th>
                        <th>Stagiaire</th>
                        <th>Filière</th>
                        <th>Classe</th>
                        <th class="text-end">Total à payer</th>
                        <th class="text-end">Total payé</th>
                        <th class="text-end">Reste à payer</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stagiaires as $stagiaire)
                    <tr>
                        <td>
                            <span class="badge bg-secondary">{{ $stagiaire->matricule }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="user-avatar bg-primary text-white" style="width: 36px; height: 36px; font-size: 0.85rem;">
                                    {{ strtoupper(substr($stagiaire->prenom, 0, 1)) }}{{ strtoupper(substr($stagiaire->nom, 0, 1)) }}
                                </div>
                                <div>
                                    <strong>{{ $stagiaire->nom }} {{ $stagiaire->prenom }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-envelope me-1"></i>{{ $stagiaire->email ?? 'N/A' }}
                                    </small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info">
                                {{ $stagiaire->filiere->nom ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            {{ $stagiaire->classe->nom ?? 'N/A' }}
                        </td>
                        <td class="text-end">
                            <strong>{{ number_format($stagiaire->total_a_payer, 2) }} DH</strong>
                        </td>
                        <td class="text-end">
                            <strong class="text-success">{{ number_format($stagiaire->total_paye, 2) }} DH</strong>
                        </td>
                        <td class="text-end">
                            <strong class="{{ $stagiaire->solde_restant > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($stagiaire->solde_restant, 2) }} DH
                            </strong>
                        </td>
                        <td class="text-center">
                            @php
                                $hasRetard = $stagiaire->echeanciers()->where('statut', 'en_retard')->exists();
                                $tauxPaiement = $stagiaire->total_a_payer > 0 
                                    ? ($stagiaire->total_paye / $stagiaire->total_a_payer) * 100 
                                    : 0;
                            @endphp

                            @if($hasRetard)
                                <span class="badge bg-danger">
                                    <i class="fas fa-exclamation-triangle me-1"></i>En retard
                                </span>
                            @elseif($stagiaire->solde_restant <= 0)
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i>À jour
                                </span>
                            @elseif($tauxPaiement > 0)
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-clock me-1"></i>Partiel ({{ number_format($tauxPaiement, 0) }}%)
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="fas fa-times-circle me-1"></i>Aucun paiement
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('comptable.stagiaires.show', $stagiaire) }}" 
                                   class="btn btn-outline-primary" 
                                   title="Détails financiers">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('paiements.create', ['stagiaire_id' => $stagiaire->id]) }}" 
                                   class="btn btn-outline-success" 
                                   title="Nouveau paiement">
                                    <i class="fas fa-plus"></i>
                                </a>
                                <a href="{{ route('echeanciers.create', ['stagiaire_id' => $stagiaire->id]) }}" 
                                   class="btn btn-outline-info" 
                                   title="Nouvel échéancier">
                                    <i class="fas fa-calendar-plus"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="fas fa-users-slash fa-3x text-muted mb-3 opacity-25"></i>
                            <p class="text-muted mb-0">Aucun stagiaire trouvé</p>
                            @if(request()->hasAny(['search', 'filiere_id', 'statut_paiement']))
                                <a href="{{ route('comptable.stagiaires') }}" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="fas fa-redo me-1"></i>Réinitialiser les filtres
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($stagiaires->hasPages())
        <div class="card-footer bg-white border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $stagiaires->firstItem() }} à {{ $stagiaires->lastItem() }} 
                    sur {{ $stagiaires->total() }} stagiaires
                </div>
                <div>
                    {{ $stagiaires->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    .user-avatar {
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }

    .table tbody tr {
        transition: all 0.2s ease;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
        transform: scale(1.01);
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .stat-card {
        transition: all 0.3s ease;
    }

    .btn-group-sm .btn {
        padding: 0.375rem 0.5rem;
    }

    @media print {
        .btn, .card-header, .card-footer, .pagination {
            display: none !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Auto-submit du formulaire lors du changement de filtre
    document.querySelectorAll('select[name="filiere_id"], select[name="statut_paiement"]').forEach(select => {
        select.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });

    // Animation au chargement
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            setTimeout(() => {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 50);
        });
    });
</script>
@endpush
@endsection