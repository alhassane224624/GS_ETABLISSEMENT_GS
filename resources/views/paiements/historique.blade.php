@extends(auth()->user()->role === 'comptable' ? 'layouts.comptable' : 'layouts.app')

@section('title', 'Historique des Paiements - ' . $stagiaire->nom_complet)

@section('content')
<div class="animate-fade-in-up">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ auth()->user()->role === 'comptable' ? route('comptable.dashboard') : route('admin.dashboard') }}">
                    Tableau de bord
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ auth()->user()->role === 'comptable' ? route('comptable.stagiaires') : route('stagiaires.index') }}">
                    Stagiaires
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ auth()->user()->role === 'comptable' ? route('comptable.stagiaires.show', $stagiaire) : route('stagiaires.show', $stagiaire) }}">
                    {{ $stagiaire->nom_complet }}
                </a>
            </li>
            <li class="breadcrumb-item active">Historique des paiements</li>
        </ol>
    </nav>

    <!-- En-tête avec infos stagiaire -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-start gap-3">
                        <div class="user-avatar bg-primary text-white" style="width: 64px; height: 64px; font-size: 1.5rem;">
                            {{ strtoupper(substr($stagiaire->prenom, 0, 1)) }}{{ strtoupper(substr($stagiaire->nom, 0, 1)) }}
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="mb-1">{{ $stagiaire->nom }} {{ $stagiaire->prenom }}</h3>
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                <span class="badge bg-primary">{{ $stagiaire->matricule }}</span>
                                <span class="badge bg-secondary">{{ $stagiaire->filiere->nom ?? 'N/A' }}</span>
                                @if($stagiaire->classe)
                                    <span class="badge bg-info">{{ $stagiaire->classe->nom }}</span>
                                @endif
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-envelope me-1"></i>{{ $stagiaire->email ?? 'N/A' }}
                                @if($stagiaire->telephone)
                                    <span class="ms-3"><i class="fas fa-phone me-1"></i>{{ $stagiaire->telephone }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="{{ route('paiements.create', ['stagiaire_id' => $stagiaire->id]) }}" 
                       class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Nouveau paiement
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small">Total à payer</p>
                            <h4 class="mb-0">{{ number_format($stagiaire->total_a_payer, 2) }} DH</h4>
                        </div>
                        <div class="stat-icon" style="background: rgba(79, 70, 229, 0.1); color: #4f46e5;">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small">Total payé</p>
                            <h4 class="mb-0 text-success">{{ number_format($stagiaire->total_paye, 2) }} DH</h4>
                        </div>
                        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small">Reste à payer</p>
                            <h4 class="mb-0 {{ $stagiaire->solde_restant > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($stagiaire->solde_restant, 2) }} DH
                            </h4>
                        </div>
                        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small">Nombre de paiements</p>
                            <h4 class="mb-0">{{ $paiements->total() }}</h4>
                        </div>
                        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des paiements -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history text-primary me-2"></i>
                    Historique des paiements
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>Imprimer
                    </button>
                    <button class="btn btn-sm btn-success" onclick="exportToExcel()">
                        <i class="fas fa-file-excel me-1"></i>Exporter
                    </button>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="paymentsTable">
                <thead class="bg-light">
                    <tr>
                        <th>N° Transaction</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Méthode</th>
                        <th class="text-end">Montant</th>
                        <th>Statut</th>
                        <th>Enregistré par</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paiements as $paiement)
                    <tr>
                        <td>
                            <strong class="text-primary">{{ $paiement->numero_transaction }}</strong>
                            @if($paiement->description)
                                <br><small class="text-muted">{{ Str::limit($paiement->description, 30) }}</small>
                            @endif
                        </td>
                        <td>
                            <div>
                                <strong>{{ $paiement->date_paiement->format('d/m/Y') }}</strong>
                                <br><small class="text-muted">{{ $paiement->date_paiement->format('H:i') }}</small>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info">
                                {{ $paiement->type_libelle }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-{{ $paiement->methode_paiement === 'especes' ? 'money-bill' : ($paiement->methode_paiement === 'carte' ? 'credit-card' : 'university') }}"></i>
                                <span>{{ $paiement->methode_libelle }}</span>
                            </div>
                        </td>
                        <td class="text-end">
                            <strong class="text-success fs-5">{{ number_format($paiement->montant, 2) }} DH</strong>
                        </td>
                        <td>
                            @if($paiement->statut === 'valide')
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i>{{ $paiement->statut_libelle }}
                                </span>
                                @if($paiement->valide_at)
                                    <br><small class="text-muted">{{ $paiement->valide_at->format('d/m/Y H:i') }}</small>
                                @endif
                            @elseif($paiement->statut === 'en_attente')
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-clock me-1"></i>{{ $paiement->statut_libelle }}
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    <i class="fas fa-times-circle me-1"></i>{{ $paiement->statut_libelle }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <small>{{ $paiement->user->name ?? 'N/A' }}</small>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('paiements.show', $paiement) }}" 
                                   class="btn btn-outline-primary" 
                                   title="Détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                @if($paiement->statut === 'valide' && $paiement->recu_path)
                                    <a href="{{ route('paiements.recu', $paiement) }}" 
                                       class="btn btn-outline-success" 
                                       title="Télécharger reçu"
                                       target="_blank">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <i class="fas fa-receipt fa-3x text-muted mb-3 opacity-25"></i>
                            <p class="text-muted mb-0">Aucun paiement trouvé</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-light">
                    <tr>
                        <th colspan="4" class="text-end">TOTAL :</th>
                        <th class="text-end">
                            <strong class="text-success fs-5">
                                {{ number_format($paiements->sum('montant'), 2) }} DH
                            </strong>
                        </th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Pagination -->
        @if($paiements->hasPages())
        <div class="card-footer bg-white border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $paiements->firstItem() }} à {{ $paiements->lastItem() }} 
                    sur {{ $paiements->total() }} paiements
                </div>
                <div>
                    {{ $paiements->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Section échéanciers liés -->
    @php
        $echeanciers = $stagiaire->echeanciers()->orderBy('date_echeance', 'desc')->get();
    @endphp

    @if($echeanciers->count() > 0)
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">
                <i class="fas fa-calendar-alt text-primary me-2"></i>
                Échéanciers associés
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Titre</th>
                        <th>Date échéance</th>
                        <th class="text-end">Montant</th>
                        <th class="text-end">Payé</th>
                        <th class="text-end">Reste</th>
                        <th>Statut</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($echeanciers as $echeancier)
                    <tr class="{{ $echeancier->statut === 'en_retard' ? 'table-danger' : '' }}">
                        <td>
                            <strong>{{ $echeancier->titre }}</strong>
                        </td>
                        <td>
                            {{ $echeancier->date_echeance->format('d/m/Y') }}
                            @if($echeancier->is_en_retard)
                                <br><small class="text-danger">{{ $echeancier->date_echeance->diffForHumans() }}</small>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format($echeancier->montant, 2) }} DH</td>
                        <td class="text-end text-success">{{ number_format($echeancier->montant_paye, 2) }} DH</td>
                        <td class="text-end">
                            <strong class="{{ $echeancier->montant_restant > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($echeancier->montant_restant, 2) }} DH
                            </strong>
                        </td>
                        <td>
                            @if($echeancier->statut === 'paye')
                                <span class="badge bg-success">Payé</span>
                            @elseif($echeancier->statut === 'paye_partiel')
                                <span class="badge bg-warning text-dark">Partiel</span>
                            @elseif($echeancier->statut === 'en_retard')
                                <span class="badge bg-danger">En retard</span>
                            @else
                                <span class="badge bg-secondary">Impayé</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('echeanciers.show', $echeancier) }}" 
                               class="btn btn-sm btn-outline-primary" 
                               title="Voir détails">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
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

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .table tbody tr {
        transition: all 0.2s ease;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa !important;
        transform: scale(1.005);
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    @media print {
        .btn, .breadcrumb, .card-header .d-flex > div:last-child {
            display: none !important;
        }
        
        .card {
            box-shadow: none !important;
            border: 1px solid #dee2e6 !important;
        }
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.5s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
    // Export vers Excel
    function exportToExcel() {
        const table = document.getElementById('paymentsTable');
        const wb = XLSX.utils.table_to_book(table, {sheet: "Paiements"});
        const filename = 'historique_paiements_{{ $stagiaire->matricule }}_' + new Date().toISOString().slice(0,10) + '.xlsx';
        XLSX.writeFile(wb, filename);
    }

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