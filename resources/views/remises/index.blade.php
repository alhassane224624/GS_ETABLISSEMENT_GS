@extends(auth()->user()->role === 'comptable' ? 'layouts.comptable' : 'layouts.app')
@section('title', 'Gestion des Remises')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Gestion des Remises</h1>
        <a href="{{ route('remises.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle Remise
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Remises
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['total_remises'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-gift fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Remises Actives
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['remises_actives'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                Montant Total (Fixes)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($stats['montant_total_remises'], 2) }} DH
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-coins fa-2x text-gray-300"></i>
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
            <form method="GET" action="{{ route('remises.index') }}" class="row g-3">
                <div class="col-md-4">
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
                    <label for="type" class="form-label">Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">Tous les types</option>
                        <option value="pourcentage" {{ request('type') == 'pourcentage' ? 'selected' : '' }}>Pourcentage</option>
                        <option value="montant_fixe" {{ request('type') == 'montant_fixe' ? 'selected' : '' }}>Montant Fixe</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="is_active" class="form-label">Statut</label>
                    <select name="is_active" id="is_active" class="form-select">
                        <option value="">Tous</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Actives</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactives</option>
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

    <!-- Liste des remises -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Liste des Remises</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Stagiaire</th>
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Valeur</th>
                            <th>Période</th>
                            <th>Statut</th>
                            <th>Créateur</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($remises as $remise)
                            <tr>
                                <td>{{ $remise->id }}</td>
                                <td>
                                    <strong>{{ $remise->stagiaire->nom }} {{ $remise->stagiaire->prenom }}</strong><br>
                                    <small class="text-muted">{{ $remise->stagiaire->filiere->nom ?? 'N/A' }}</small>
                                </td>
                                <td>{{ $remise->titre }}</td>
                                <td>
                                    @if($remise->type === 'pourcentage')
                                        <span class="badge bg-info">Pourcentage</span>
                                    @else
                                        <span class="badge bg-primary">Montant Fixe</span>
                                    @endif
                                </td>
                                <td>
                                    @if($remise->type === 'pourcentage')
                                        <strong>{{ $remise->valeur }}%</strong>
                                    @else
                                        <strong>{{ number_format($remise->valeur, 2) }} DH</strong>
                                    @endif
                                </td>
                                <td>
                                    <small>
                                        Du {{ \Carbon\Carbon::parse($remise->date_debut)->format('d/m/Y') }}<br>
                                        @if($remise->date_fin)
                                            Au {{ \Carbon\Carbon::parse($remise->date_fin)->format('d/m/Y') }}
                                        @else
                                            <em>Pas de fin</em>
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    @if($remise->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $remise->createur->name ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('remises.show', $remise) }}" class="btn btn-info" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('remises.edit', $remise) }}" class="btn btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('remises.toggle', $remise) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-{{ $remise->is_active ? 'secondary' : 'success' }}" title="{{ $remise->is_active ? 'Désactiver' : 'Activer' }}">
                                                <i class="fas fa-{{ $remise->is_active ? 'times' : 'check' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('remises.destroy', $remise) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette remise ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Aucune remise trouvée
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $remises->links() }}
            </div>
        </div>
    </div>
</div>
@endsection