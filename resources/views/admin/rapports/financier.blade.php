@extends('layouts.app')

@section('title', 'Rapport Financier')

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Rapport Financier</h1>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success" onclick="exporterRapport('excel')">
                <i class="fas fa-file-excel"></i> Exporter Excel
            </button>
            <button type="button" class="btn btn-danger" onclick="exporterRapport('pdf')">
                <i class="fas fa-file-pdf"></i> Exporter PDF
            </button>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.rapports.financier') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="date_debut" class="form-label">Date début</label>
                    <input type="date" class="form-control" id="date_debut" name="date_debut" 
                           value="{{ request('date_debut', now()->startOfMonth()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label for="date_fin" class="form-label">Date fin</label>
                    <input type="date" class="form-control" id="date_fin" name="date_fin" 
                           value="{{ request('date_fin', now()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label for="filiere_id" class="form-label">Filière</label>
                    <select class="form-select" id="filiere_id" name="filiere_id">
                        <option value="">Toutes les filières</option>
                        @foreach($filieres as $filiere)
                            <option value="{{ $filiere->id }}" 
                                {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                {{ $filiere->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Encaissé
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($stats['total_encaisse'], 2) }} DH
                            </div>
                            @if($stats['evolution_encaisse'] != 0)
                                <small class="text-{{ $stats['evolution_encaisse'] > 0 ? 'success' : 'danger' }}">
                                    <i class="fas fa-arrow-{{ $stats['evolution_encaisse'] > 0 ? 'up' : 'down' }}"></i>
                                    {{ abs($stats['evolution_encaisse']) }}% vs mois précédent
                                </small>
                            @endif
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-coins fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Attendu
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($stats['total_attendu'], 2) }} DH
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Total Impayés
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($stats['total_impayes'], 2) }} DH
                            </div>
                            <small class="text-muted">{{ $stats['nb_retards'] }} retards</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Taux de Recouvrement
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['taux_recouvrement'] }}%
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: {{ $stats['taux_recouvrement'] }}%"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Évolution des Paiements (30 jours)</h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary" onclick="chargerGraphique('evolution', 7)">7j</button>
                        <button type="button" class="btn btn-outline-primary active" onclick="chargerGraphique('evolution', 30)">30j</button>
                        <button type="button" class="btn btn-outline-primary" onclick="chargerGraphique('evolution', 90)">90j</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="evolutionChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Répartition par Méthode</h6>
                </div>
                <div class="card-body">
                    <canvas id="methodesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Remises -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Remises Accordées</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">{{ number_format($stats['total_remises'], 2) }} DH</h3>
                            <small class="text-muted">{{ $stats['nb_remises'] }} remises actives</small>
                        </div>
                        <i class="fas fa-percentage fa-3x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Répartition par Filière</h6>
                </div>
                <div class="card-body">
                    <canvas id="filieresChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top 10 Retards -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-danger">Top 10 Retards de Paiement</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Stagiaire</th>
                            <th>Filière</th>
                            <th>Date échéance</th>
                            <th>Montant restant</th>
                            <th>Jours de retard</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($retards as $retard)
                            <tr>
                                <td>
                                    <strong>{{ $retard->stagiaire->nom }} {{ $retard->stagiaire->prenom }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $retard->stagiaire->filiere->nom }}</span>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($retard->date_echeance)->format('d/m/Y') }}</td>
                                <td class="text-danger font-weight-bold">
                                    {{ number_format($retard->montant_restant, 2) }} DH
                                </td>
                                <td>
                                    <span class="badge bg-danger">
                                        {{ \Carbon\Carbon::parse($retard->date_echeance)->diffInDays(now()) }} jours
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ auth()->user()->isAdmin() ? route('stagiaires.show', $retard->stagiaire_id) : route('comptable.stagiaires.show', $retard->stagiaire_id) }}" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Aucun retard</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Derniers Paiements -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">Derniers Paiements</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Stagiaire</th>
                            <th>Filière</th>
                            <th>Montant</th>
                            <th>Méthode</th>
                            <th>Référence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($derniers_paiements as $paiement)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y H:i') }}</td>
                                <td>{{ $paiement->stagiaire->nom }} {{ $paiement->stagiaire->prenom }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $paiement->stagiaire->filiere->nom }}</span>
                                </td>
                                <td class="font-weight-bold text-success">
                                    {{ number_format($paiement->montant, 2) }} DH
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $paiement->methode_paiement }}</span>
                                </td>
                                <td><small class="text-muted">{{ $paiement->reference ?? '-' }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Aucun paiement récent</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Graphique d'évolution
    const evolutionCtx = document.getElementById('evolutionChart').getContext('2d');
    const evolutionChart = new Chart(evolutionCtx, {
        type: 'line',
        data: {
            labels: @json($stats['evolution_labels']),
            datasets: [{
                label: 'Paiements (DH)',
                data: @json($stats['evolution_data']),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: true },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + 
                                   context.parsed.y.toFixed(2) + ' DH';
                        }
                    }
                }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Graphique des méthodes
    const methodesCtx = document.getElementById('methodesChart').getContext('2d');
    const methodesData = @json($stats['par_methode']);
    new Chart(methodesCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(methodesData),
            datasets: [{
                data: Object.values(methodesData),
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 99, 132, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Charger graphique filières
    fetch('{{ route("admin.rapports.financier.graphique") }}?type=filieres&periode=30')
        .then(response => response.json())
        .then(data => {
            const filieresCtx = document.getElementById('filieresChart').getContext('2d');
            new Chart(filieresCtx, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        });

    // Fonction pour charger les graphiques
    function chargerGraphique(type, periode) {
        fetch(`{{ route('admin.rapports.financier.graphique') }}?type=${type}&periode=${periode}`)
            .then(response => response.json())
            .then(data => {
                evolutionChart.data = data;
                evolutionChart.update();
            });
    }

    // Fonction d'export
    function exporterRapport(format) {
        const params = new URLSearchParams(window.location.search);
        params.set('format', format);
        window.location.href = '{{ route("admin.rapports.financier.export") }}?' + params.toString();
    }
</script>
@endpush

@push('styles')
<style>
    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }
    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }
    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }
    .border-left-danger {
        border-left: 0.25rem solid #e74a3b !important;
    }
    .progress-sm {
        height: 0.5rem;
    }
</style>
@endpush