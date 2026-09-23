@extends(auth()->user()->role === 'comptable' ? 'layouts.comptable' : 'layouts.app')

@section('title', 'Gestion des Échéanciers')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Gestion des Échéanciers</h1>
        <div class="btn-group">
            <a href="{{ route('echeanciers.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouvel Échéancier
            </a>
            <button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ route('echeanciers.generer-mensuels') }}">
                        <i class="fas fa-calendar-alt"></i> Générer Mensuels
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('echeanciers.verifier-retards') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="fas fa-sync"></i> Vérifier les Retards
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-4">
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
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                En Retard
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['en_retard'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                À Venir
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['a_venir'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtres</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('echeanciers.index') }}" class="row g-3">
                <div class="col-md-5">
                    <label for="stagiaire_id" class="form-label">Stagiaire</label>
                    <select name="stagiaire_id" id="stagiaire_id" class="form-select">
                        <option value="">Tous les stagiaires</option>
                        @foreach(\App\Models\Stagiaire::actifs()->orderBy('nom')->get() as $stag)
                            <option value="{{ $stag->id }}" {{ request('stagiaire_id') == $stag->id ? 'selected' : '' }}>
                                {{ $stag->nom }} {{ $stag->prenom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="statut" class="form-label">Statut</label>
                    <select name="statut" id="statut" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="impaye" {{ request('statut') == 'impaye' ? 'selected' : '' }}>Impayé</option>
                        <option value="paye_partiel" {{ request('statut') == 'paye_partiel' ? 'selected' : '' }}>Payé Partiellement</option>
                        <option value="paye" {{ request('statut') == 'paye' ? 'selected' : '' }}>Payé</option>
                        <option value="en_retard" {{ request('statut') == 'en_retard' ? 'selected' : '' }}>En Retard</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="en_retard" class="form-label">Retards uniquement</label>
                    <select name="en_retard" id="en_retard" class="form-select">
                        <option value="">Non</option>
                        <option value="1" {{ request('en_retard') == '1' ? 'selected' : '' }}>Oui</option>
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

    <!-- Liste des échéanciers -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Liste des Échéanciers</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Stagiaire</th>
                            <th>Titre</th>
                            <th>Année Scolaire</th>
                            <th>Date Échéance</th>
                            <th>Montant</th>
                            <th>Payé</th>
                            <th>Restant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($echeanciers as $echeancier)
                            <tr class="{{ $echeancier->statut === 'en_retard' ? 'table-danger' : '' }}">
                                <td>{{ $echeancier->id }}</td>
                                <td>
                                    <strong>{{ $echeancier->stagiaire->nom }} {{ $echeancier->stagiaire->prenom }}</strong><br>
                                    <small class="text-muted">{{ $echeancier->stagiaire->filiere->nom ?? 'N/A' }}</small>
                                </td>
                                <td>{{ $echeancier->titre }}</td>
                                <td>
                                    <small>{{ $echeancier->anneeScolaire->nom ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::parse($echeancier->date_echeance)->format('d/m/Y') }}
                                    @if(\Carbon\Carbon::parse($echeancier->date_echeance)->isPast() && $echeancier->statut !== 'paye')
                                        <br><span class="badge bg-danger">En retard</span>
                                    @endif
                                </td>
                                <td><strong>{{ number_format($echeancier->montant, 2) }} DH</strong></td>
                                <td class="text-success">{{ number_format($echeancier->montant_paye, 2) }} DH</td>
                                <td class="text-danger">{{ number_format($echeancier->montant_restant, 2) }} DH</td>
                                <td>
                                    @if($echeancier->statut === 'paye')
                                        <span class="badge bg-success">Payé</span>
                                    @elseif($echeancier->statut === 'paye_partiel')
                                        <span class="badge bg-warning">Partiel</span>
                                    @elseif($echeancier->statut === 'en_retard')
                                        <span class="badge bg-danger">En Retard</span>
                                    @else
                                        <span class="badge bg-secondary">Impayé</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('echeanciers.show', $echeancier) }}" class="btn btn-info" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($echeancier->statut !== 'paye')
                                            <a href="{{ route('echeanciers.edit', $echeancier) }}" class="btn btn-warning" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @if($echeancier->montant_paye == 0)
                                            <form action="{{ route('echeanciers.destroy', $echeancier) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet échéancier ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Aucun échéancier trouvé
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $echeanciers->links() }}
            </div>
        </div>
    </div>
</div>
@endsection