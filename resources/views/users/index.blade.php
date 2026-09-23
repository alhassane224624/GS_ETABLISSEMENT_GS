@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-users"></i> Gestion des Utilisateurs</h2>
                <a href="{{ route('users.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouvel Utilisateur
                </a>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="mb-1">Total</h6>
                    <h3 class="mb-0">{{ $stats['total_users'] }}</h3>
                    <small>Utilisateurs</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="mb-1">Administrateurs</h6>
                    <h3 class="mb-0">{{ $stats['admins'] }}</h3>
                    <small><i class="fas fa-user-shield"></i></small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="mb-1">Comptables</h6>
                    <h3 class="mb-0">{{ $stats['comptables'] }}</h3>
                    <small><i class="fas fa-calculator"></i></small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="mb-1">Professeurs</h6>
                    <h3 class="mb-0">{{ $stats['professeurs'] }}</h3>
                    <small><i class="fas fa-chalkboard-teacher"></i></small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h6 class="mb-1">Stagiaires</h6>
                    <h3 class="mb-0">{{ $stats['stagiaires'] }}</h3>
                    <small><i class="fas fa-user-graduate"></i></small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="mb-1">Actifs</h6>
                    <h3 class="mb-0">{{ $stats['actifs'] }}</h3>
                    <small><i class="fas fa-check-circle"></i></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('users.index') }}">
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Rechercher par nom ou email..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="role" class="form-control">
                            <option value="">Tous les rôles</option>
                            <option value="administrateur" {{ request('role') == 'administrateur' ? 'selected' : '' }}>Administrateur</option>
                            <option value="comptable" {{ request('role') == 'comptable' ? 'selected' : '' }}>Comptable</option>
                            <option value="professeur" {{ request('role') == 'professeur' ? 'selected' : '' }}>Professeur</option>
                            <option value="stagiaire" {{ request('role') == 'stagiaire' ? 'selected' : '' }}>Stagiaire</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="is_active" class="form-control">
                            <option value="">Tous les statuts</option>
                            <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Actifs</option>
                            <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactifs</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Liste des utilisateurs -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Spécialité</th>
                            <th>Téléphone</th>
                            <th>Créé le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>
                                    <strong>{{ $user->name }}</strong>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->role == 'administrateur')
                                        <span class="badge bg-danger">
                                            <i class="fas fa-user-shield"></i> Administrateur
                                        </span>
                                    @elseif($user->role == 'comptable')
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-calculator"></i> Comptable
                                        </span>
                                    @elseif($user->role == 'professeur')
                                        <span class="badge bg-info">
                                            <i class="fas fa-chalkboard-teacher"></i> Professeur
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-user-graduate"></i> Stagiaire
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->is_active)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle"></i> Actif
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle"></i> Inactif
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $user->specialite ?? '-' }}</td>
                                <td>{{ $user->telephone ?? '-' }}</td>
                                <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-info" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($user->id != auth()->id())
                                            <form action="{{ route('users.toggle-active', $user->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-secondary' : 'btn-success' }}" title="{{ $user->is_active ? 'Désactiver' : 'Activer' }}">
                                                    <i class="fas {{ $user->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                                </button>
                                            </form>

                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteUser({{ $user->id }})" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">Aucun utilisateur trouvé</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>

<script>
function deleteUser(userId) {
    if(confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        fetch(`/users/${userId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la suppression');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}
</script>
@endsection