@extends('layouts.comptable')

@section('title', 'Tableau de bord Comptable')

@section('content')
<div class="animate-fade-in-up">
    <!-- En-tête avec date -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Tableau de bord</h2>
            <p class="text-muted mb-0">
                <i class="fas fa-calendar me-1"></i>
                {{ now()->isoFormat('dddd D MMMM YYYY') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="window.print()">
                <i class="fas fa-print me-1"></i>Imprimer
            </button>
            <a href="{{ route('comptable.rapports') }}" class="btn btn-primary">
                <i class="fas fa-chart-line me-1"></i>Rapports détaillés
            </a>
        </div>
    </div>

    <!-- KPIs principaux -->
    <div class="row g-4 mb-4">
        <!-- Paiements du jour -->
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="text-muted mb-1 small">Paiements du jour</p>
                        <h3 class="mb-0">{{ number_format($stats['paiements_jour'], 2) }} DH</h3>
                    </div>
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center text-success small">
                    <i class="fas fa-arrow-up me-1"></i>
                    Aujourd'hui
                </div>
            </div>
        </div>

        <!-- Paiements du mois -->
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="text-muted mb-1 small">Paiements du mois</p>
                        <h3 class="mb-0">{{ number_format($stats['paiements_mois'], 2) }} DH</h3>
                    </div>
                    <div class="stat-icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="text-muted small">
                    {{ now()->format('F Y') }}
                </div>
            </div>
        </div>

        <!-- En attente -->
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="text-muted mb-1 small">En attente</p>
                        <h3 class="mb-0">{{ $stats['en_attente'] }}</h3>
                        <small class="text-muted">{{ number_format($stats['montant_attente'], 2) }} DH</small>
                    </div>
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <a href="{{ route('paiements.index', ['statut' => 'en_attente']) }}" class="btn btn-sm btn-warning w-100">
                    <i class="fas fa-check me-1"></i>Valider maintenant
                </a>
            </div>
        </div>

        <!-- Retards -->
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="text-muted mb-1 small">Échéances en retard</p>
                        <h3 class="mb-0 text-danger">{{ $stats['echeances_retard'] }}</h3>
                        <small class="text-danger">{{ number_format($stats['montant_retard'], 2) }} DH</small>
                    </div>
                    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
                <a href="{{ route('echeanciers.index', ['en_retard' => true]) }}" class="btn btn-sm btn-outline-danger w-100">
                    <i class="fas fa-eye me-1"></i>Voir les retards
                </a>
            </div>
        </div>
    </div>

    <!-- Stats secondaires -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-2">Échéances à venir (7 jours)</h6>
                        <h4 class="mb-0">{{ $stats['echeances_prochaines'] }}</h4>
                    </div>
                    <i class="fas fa-calendar-day fa-2x text-primary opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-2">Total impayés</h6>
                        <h4 class="mb-0 text-danger">{{ number_format($stats['total_impayes'], 2) }} DH</h4>
                    </div>
                    <i class="fas fa-exclamation-circle fa-2x text-danger opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-2">Remises actives</h6>
                        <h4 class="mb-0">{{ $stats['remises_actives'] }}</h4>
                    </div>
                    <i class="fas fa-percentage fa-2x text-success opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="row g-4 mb-4">
        <!-- Évolution des paiements -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-area text-primary me-2"></i>
                        Évolution des paiements (30 derniers jours)
                    </h5>
                    <span class="badge bg-primary">{{ count($evolutionData) }} jours</span>
                </div>
                <div class="card-body">
                    <canvas id="evolutionChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Répartition par méthode -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card text-success me-2"></i>
                        Méthodes de paiement
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="methodesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Listes -->
    <div class="row g-4">
        <!-- Paiements en attente -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock text-warning me-2"></i>
                        Paiements en attente
                    </h5>
                    <a href="{{ route('paiements.index', ['statut' => 'en_attente']) }}" class="btn btn-sm btn-outline-primary">
                        Voir tout ({{ $stats['en_attente'] }})
                    </a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($paiementsEnAttente as $paiement)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h6 class="mb-0">{{ $paiement->stagiaire->nom }} {{ $paiement->stagiaire->prenom }}</h6>
                                    <span class="badge bg-secondary">{{ $paiement->stagiaire->filiere->nom ?? 'N/A' }}</span>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-hashtag"></i> {{ $paiement->numero_transaction }} •
                                    <i class="fas fa-calendar"></i> {{ $paiement->date_paiement->format('d/m/Y') }} •
                                    <i class="fas fa-{{ $paiement->methode_paiement === 'especes' ? 'money-bill' : ($paiement->methode_paiement === 'carte' ? 'credit-card' : 'university') }}"></i> {{ $paiement->methode_libelle }}
                                </small>
                            </div>
                            <div class="text-end ms-3">
                                <h6 class="mb-2 text-success">{{ number_format($paiement->montant, 2) }} DH</h6>
                                <a href="{{ route('paiements.show', $paiement) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-check me-1"></i>Valider
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-check-circle fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">Aucun paiement en attente</p>
                        <small>Tous les paiements sont à jour</small>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Top retards -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                        Top 10 retards
                    </h5>
                    <a href="{{ route('echeanciers.index', ['en_retard' => true]) }}" class="btn btn-sm btn-outline-danger">
                        Voir tout ({{ $stats['echeances_retard'] }})
                    </a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($topRetards as $echeancier)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h6 class="mb-0">{{ $echeancier->stagiaire->nom }} {{ $echeancier->stagiaire->prenom }}</h6>
                                    <span class="badge bg-secondary">{{ $echeancier->stagiaire->filiere->nom ?? 'N/A' }}</span>
                                </div>
                                <small class="text-danger">
                                    <i class="fas fa-calendar-times"></i> Échéance: {{ $echeancier->date_echeance->format('d/m/Y') }}
                                    ({{ $echeancier->date_echeance->diffForHumans() }})
                                </small>
                                <div class="mt-1">
                                    <small class="text-muted">{{ $echeancier->titre }}</small>
                                </div>
                            </div>
                            <div class="text-end ms-3">
                                <h6 class="mb-2 text-danger">{{ number_format($echeancier->montant_restant, 2) }} DH</h6>
                                <span class="badge bg-danger">{{ $echeancier->statut_libelle }}</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-smile fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">Aucun retard</p>
                        <small>Tous les stagiaires sont à jour</small>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Échéances prochaines -->
    @if($echeancesProchaines->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-check text-info me-2"></i>
                        Échéances à venir (7 prochains jours)
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Stagiaire</th>
                                <th>Titre</th>
                                <th>Date</th>
                                <th class="text-end">Montant</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($echeancesProchaines as $echeancier)
                            <tr>
                                <td>
                                    <div>
                                        <strong>{{ $echeancier->stagiaire->nom }} {{ $echeancier->stagiaire->prenom }}</strong>
                                        <br><small class="text-muted">{{ $echeancier->stagiaire->matricule }}</small>
                                    </div>
                                </td>
                                <td>{{ $echeancier->titre }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $echeancier->date_echeance->format('d/m/Y') }}
                                    </span>
                                    <br><small class="text-muted">{{ $echeancier->date_echeance->diffForHumans() }}</small>
                                </td>
                                <td class="text-end">
                                    <strong>{{ number_format($echeancier->montant_restant, 2) }} DH</strong>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('paiements.create', ['stagiaire_id' => $echeancier->stagiaire_id]) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Configuration commune
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#64748b';

// Graphique d'évolution
const evolutionCtx = document.getElementById('evolutionChart');
new Chart(evolutionCtx, {
    type: 'line',
    data: {
        labels: @json($evolutionLabels),
        datasets: [{
            label: 'Paiements (DH)',
            data: @json($evolutionData),
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#2563eb',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: {
                    size: 14
                },
                bodyFont: {
                    size: 13
                },
                callbacks: {
                    label: function(context) {
                        return 'Montant: ' + context.parsed.y.toFixed(2) + ' DH';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                },
                ticks: {
                    callback: function(value) {
                        return value.toFixed(0) + ' DH';
                    }
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Graphique méthodes
const methodesData = @json($parMethode);
const methodesLabels = Object.keys(methodesData);
const methodesValues = Object.values(methodesData);

const methodesCtx = document.getElementById('methodesChart');
new Chart(methodesCtx, {
    type: 'doughnut',
    data: {
        labels: methodesLabels,
        datasets: [{
            data: methodesValues,
            backgroundColor: [
                'rgba(16, 185, 129, 0.8)',
                'rgba(37, 99, 235, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(139, 92, 246, 0.8)',
                'rgba(239, 68, 68, 0.8)'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    usePointStyle: true,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return label + ': ' + value.toFixed(2) + ' DH (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
</script>
@endpush
@endsection