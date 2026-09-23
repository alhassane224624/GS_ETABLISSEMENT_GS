@extends('layouts.comptable')

@section('title', 'Rapports Financiers')

@section('content')
<div class="animate-fade-in-up">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Rapports Financiers</h2>
            <p class="text-muted mb-0">Analyse et statistiques détaillées</p>
        </div>
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-download me-1"></i>Exporter
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ route('comptable.rapports', array_merge(request()->all(), ['format' => 'excel'])) }}">
                        <i class="fas fa-file-excel me-2 text-success"></i>Excel
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('admin.rapports.financier.export', array_merge(request()->all(), ['format' => 'pdf'])) }}">
                        <i class="fas fa-file-pdf me-2 text-danger"></i>PDF
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm stat-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('comptable.rapports') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date début</label>
                    <input type="date" name="date_debut" class="form-control" 
                           value="{{ request('date_debut', $dateDebut) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="date_fin" class="form-control" 
                           value="{{ request('date_fin', $dateFin) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filière</label>
                    <select name="filiere_id" class="form-select">
                        <option value="">Toutes les filières</option>
                        @foreach($filieres as $filiere)
                            <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                {{ $filiere->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-search me-1"></i>Filtrer
                    </button>
                    <a href="{{ route('comptable.rapports') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs Période -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Total encaissé</h6>
                <h3 class="mb-0 text-success">{{ number_format($stats['total_encaisse'], 2) }} DH</h3>
                <small class="text-muted">{{ $stats['nb_paiements'] }} paiements</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Total attendu</h6>
                <h3 class="mb-0">{{ number_format($stats['total_attendu'], 2) }} DH</h3>
                <small class="text-muted">Échéances période</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Total impayés</h6>
                <h3 class="mb-0 text-danger">{{ number_format($stats['total_impayes'], 2) }} DH</h3>
                <small class="text-muted">Tous les impayés</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Taux de recouvrement</h6>
                <h3 class="mb-0 {{ $stats['taux_recouvrement'] >= 80 ? 'text-success' : ($stats['taux_recouvrement'] >= 60 ? 'text-warning' : 'text-danger') }}">
                    {{ $stats['taux_recouvrement'] }}%
                </h3>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar {{ $stats['taux_recouvrement'] >= 80 ? 'bg-success' : ($stats['taux_recouvrement'] >= 60 ? 'bg-warning' : 'bg-danger') }}" 
                         style="width: {{ $stats['taux_recouvrement'] }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques avancés -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar text-primary me-2"></i>
                        Comparaison Encaissé vs Attendu
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="comparaisonChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie text-success me-2"></i>
                        Répartition Statuts
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="statutsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tops Lists -->
    <div class="row g-4">
        <!-- Top 10 meilleurs payeurs -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy text-warning me-2"></i>
                        Top 10 Meilleurs Payeurs
                    </h5>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($meilleursPayeurs as $index => $stagiaire)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div class="badge bg-{{ $index < 3 ? 'warning' : 'secondary' }} rounded-circle" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    {{ $index + 1 }}
                                </div>
                                <div>
                                    <strong>{{ $stagiaire->nom }} {{ $stagiaire->prenom }}</strong>
                                    <br><small class="text-muted">{{ $stagiaire->filiere->nom ?? 'N/A' }}</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-0 text-success">{{ number_format($stagiaire->total_paye, 2) }} DH</h6>
                                <small class="text-muted">
                                    {{ $stagiaire->total_a_payer > 0 ? number_format(($stagiaire->total_paye / $stagiaire->total_a_payer) * 100, 1) : 0 }}%
                                </small>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-users-slash fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">Aucune donnée</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Top 10 plus gros impayés -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                        Top 10 Plus Gros Impayés
                    </h5>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($plusGrosImpayes as $index => $stagiaire)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div class="badge bg-danger rounded-circle" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    {{ $index + 1 }}
                                </div>
                                <div>
                                    <strong>{{ $stagiaire->nom }} {{ $stagiaire->prenom }}</strong>
                                    <br><small class="text-muted">{{ $stagiaire->filiere->nom ?? 'N/A' }}</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-0 text-danger">{{ number_format($stagiaire->solde_restant, 2) }} DH</h6>
                                <a href="{{ route('comptable.stagiaires.show', $stagiaire) }}" class="btn btn-sm btn-outline-danger mt-1">
                                    <i class="fas fa-eye me-1"></i>Voir
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-check-circle fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">Aucun impayé</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Graphique comparaison
const comparaisonCtx = document.getElementById('comparaisonChart');
new Chart(comparaisonCtx, {
    type: 'bar',
    data: {
        labels: ['Encaissé', 'Attendu', 'Impayés'],
        datasets: [{
            label: 'Montant (DH)',
            data: [
                {{ $stats['total_encaisse'] }},
                {{ $stats['total_attendu'] }},
                {{ $stats['total_impayes'] }}
            ],
            backgroundColor: [
                'rgba(16, 185, 129, 0.8)',
                'rgba(37, 99, 235, 0.8)',
                'rgba(239, 68, 68, 0.8)'
            ],
            borderColor: [
                'rgb(16, 185, 129)',
                'rgb(37, 99, 235)',
                'rgb(239, 68, 68)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.parsed.y.toFixed(2) + ' DH';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toFixed(0) + ' DH';
                    }
                }
            }
        }
    }
});

// Graphique statuts
const statutsCtx = document.getElementById('statutsChart');
const totalEncaisse = {{ $stats['total_encaisse'] }};
const totalImpayes = {{ $stats['total_impayes'] }};

new Chart(statutsCtx, {
    type: 'doughnut',
    data: {
        labels: ['Payé', 'Impayé'],
        datasets: [{
            data: [totalEncaisse, totalImpayes],
            backgroundColor: [
                'rgba(16, 185, 129, 0.8)',
                'rgba(239, 68, 68, 0.8)'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = totalEncaisse + totalImpayes;
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