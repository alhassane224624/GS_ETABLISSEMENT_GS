@extends('layouts.comptable')

@section('title', 'Détail Stagiaire - ' . $stagiaire->nom)

@section('content')
<div class="animate-fade-in-up">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('comptable.dashboard') }}">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="{{ route('comptable.stagiaires') }}">Stagiaires</a></li>
            <li class="breadcrumb-item active">{{ $stagiaire->nom }} {{ $stagiaire->prenom }}</li>
        </ol>
    </nav>

    <!-- En-tête stagiaire -->
    <div class="card border-0 shadow-sm stat-card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-start gap-3">
                        <div class="user-avatar" style="width: 64px; height: 64px; font-size: 1.5rem;">
                            {{ strtoupper(substr($stagiaire->prenom, 0, 1)) }}{{ strtoupper(substr($stagiaire->nom, 0, 1)) }}
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="mb-1">{{ $stagiaire->nom }} {{ $stagiaire->prenom }}</h3>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <span class="badge bg-primary">{{ $stagiaire->matricule }}</span>
                                <span class="badge bg-secondary">{{ $stagiaire->filiere->nom ?? 'N/A' }}</span>
                                @if($stagiaire->classe)
                                    <span class="badge bg-info">{{ $stagiaire->classe->nom }}</span>
                                @endif
                                @if($stagiaire->is_active)
                                    <span class="badge bg-success">Actif</span>
                                @else
                                    <span class="badge bg-danger">Inactif</span>
                                @endif
                            </div>
                            <div class="mt-2 text-muted small">
                                <i class="fas fa-envelope me-1"></i>{{ $stagiaire->email }}
                                @if($stagiaire->telephone)
                                    <span class="ms-3"><i class="fas fa-phone me-1"></i>{{ $stagiaire->telephone }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="btn-group">
                        <a href="{{ route('paiements.create', ['stagiaire_id' => $stagiaire->id]) }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Nouveau paiement
                        </a>
                        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('echeanciers.create', ['stagiaire_id' => $stagiaire->id]) }}">
                                <i class="fas fa-calendar-plus me-2"></i>Nouvel échéancier
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('remises.create', ['stagiaire_id' => $stagiaire->id]) }}">
                                <i class="fas fa-percentage me-2"></i>Nouvelle remise
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('paiements.historique', $stagiaire) }}">
                                <i class="fas fa-history me-2"></i>Historique complet
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats financières -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Total à payer</h6>
                <h3 class="mb-0">{{ number_format($stats['total_a_payer'], 2) }} DH</h3>
                <small class="text-muted">{{ $stats['nb_echeances'] }} échéances</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Total payé</h6>
                <h3 class="mb-0 text-success">{{ number_format($stats['total_paye'], 2) }} DH</h3>
                <small class="text-muted">{{ $stats['nb_paiements'] }} paiements</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Reste à payer</h6>
                <h3 class="mb-0 {{ $stats['solde_restant'] > 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($stats['solde_restant'], 2) }} DH
                </h3>
                @if($stats['nb_retards'] > 0)
                    <span class="badge bg-danger">{{ $stats['nb_retards'] }} retard(s)</span>
                @else
                    <span class="badge bg-success">À jour</span>
                @endif
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Remises</h6>
                <h3 class="mb-0 text-info">{{ number_format($stats['remises_total'], 2) }} DH</h3>
                <small class="text-muted">{{ $stagiaire->remises->count() }} remise(s)</small>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#echeanciers">
                <i class="fas fa-calendar-alt me-1"></i>Échéanciers
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#paiements">
                <i class="fas fa-money-bill-wave me-1"></i>Paiements
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#remises">
                <i class="fas fa-percentage me-1"></i>Remises
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Échéanciers -->
        <div class="tab-pane fade show active" id="echeanciers">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Échéanciers</h5>
                    <a href="{{ route('echeanciers.create', ['stagiaire_id' => $stagiaire->id]) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>Nouvel échéancier
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
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
                            @forelse($stagiaire->echeanciers as $echeancier)
                            <tr class="{{ $echeancier->statut === 'en_retard' ? 'table-danger' : '' }}">
                                <td>
                                    <strong>{{ $echeancier->titre }}</strong>
                                    @if($echeancier->anneeScolaire)
                                        <br><small class="text-muted">{{ $echeancier->anneeScolaire->nom }}</small>
                                    @endif
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
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('echeanciers.show', $echeancier) }}" class="btn btn-outline-primary" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($echeancier->statut !== 'paye')
                                            <a href="{{ route('paiements.create', ['stagiaire_id' => $stagiaire->id]) }}" 
                                               class="btn btn-outline-success" title="Payer">
                                                <i class="fas fa-plus"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-calendar-times fa-3x mb-3 opacity-25"></i>
                                    <p class="mb-0">Aucun échéancier</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Paiements -->
        <div class="tab-pane fade" id="paiements">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Historique des paiements</h5>
                    <a href="{{ route('paiements.historique', $stagiaire) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-history me-1"></i>Historique complet
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>N° Transaction</th>
                                <th>Date</th>
                                <th>Méthode</th>
                                <th class="text-end">Montant</th>
                                <th>Statut</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stagiaire->paiements->take(10) as $paiement)
                            <tr>
                                <td>
                                    <strong>{{ $paiement->numero_transaction }}</strong>
                                    <br><small class="text-muted">{{ $paiement->type_libelle }}</small>
                                </td>
                                <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                                <td>
                                    <i class="fas fa-{{ $paiement->methode_paiement === 'especes' ? 'money-bill' : ($paiement->methode_paiement === 'carte' ? 'credit-card' : 'university') }} me-1"></i>
                                    {{ $paiement->methode_libelle }}
                                </td>
                                <td class="text-end">
                                    <strong class="text-success">{{ number_format($paiement->montant, 2) }} DH</strong>
                                </td>
                                <td>
                                    @if($paiement->statut === 'valide')
                                        <span class="badge bg-success">Validé</span>
                                    @elseif($paiement->statut === 'en_attente')
                                        <span class="badge bg-warning text-dark">En attente</span>
                                    @else
                                        <span class="badge bg-danger">Refusé</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('paiements.show', $paiement) }}" class="btn btn-outline-primary" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($paiement->statut === 'valide' && $paiement->recu_path)
                                            <a href="{{ route('paiements.recu', $paiement) }}" class="btn btn-outline-success" 
                                               title="Télécharger reçu" target="_blank">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-receipt fa-3x mb-3 opacity-25"></i>
                                    <p class="mb-0">Aucun paiement</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Remises -->
        <div class="tab-pane fade" id="remises">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Remises accordées</h5>
                    <a href="{{ route('remises.create', ['stagiaire_id' => $stagiaire->id]) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>Nouvelle remise
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Titre</th>
                                <th>Type</th>
                                <th>Valeur</th>
                                <th>Période</th>
                                <th>Statut</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stagiaire->remises as $remise)
                            <tr>
                                <td>
                                    <strong>{{ $remise->titre }}</strong>
                                    <br><small class="text-muted">{{ $remise->motif }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $remise->type === 'pourcentage' ? 'Pourcentage' : 'Montant fixe' }}
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ $remise->type_libelle }}</strong>
                                </td>
                                <td>
                                    Du {{ $remise->date_debut->format('d/m/Y') }}
                                    @if($remise->date_fin)
                                        <br>au {{ $remise->date_fin->format('d/m/Y') }}
                                    @else
                                        <br><small class="text-muted">Sans limite</small>
                                    @endif
                                </td>
                                <td>
                                    @if($remise->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('remises.show', $remise) }}" class="btn btn-outline-primary" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('remises.edit', $remise) }}" class="btn btn-outline-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-percentage fa-3x mb-3 opacity-25"></i>
                                    <p class="mb-0">Aucune remise</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection