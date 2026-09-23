@extends('layouts.app')

@section('title', 'Détails de l\'utilisateur')

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2">
                        <i class="fas fa-user-circle text-primary"></i>
                        Détails de l'utilisateur
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Utilisateurs</a></li>
                            <li class="breadcrumb-item active">{{ $user->name }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('users.edit', $user) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Colonne principale -->
        <div class="col-lg-8">
            <!-- Informations principales -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> Informations principales
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Nom complet</label>
                            <p class="h5 mb-0">{{ $user->name }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Email</label>
                            <p class="mb-0">
                                <i class="fas fa-envelope text-primary"></i>
                                <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                            </p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Rôle</label>
                            <p class="mb-0">
                                @if($user->role === 'administrateur')
                                    <span class="badge bg-danger fs-6">
                                        <i class="fas fa-user-shield"></i> Administrateur
                                    </span>
                                @elseif($user->role === 'comptable')
                                    <span class="badge bg-warning text-dark fs-6">
                                        <i class="fas fa-calculator"></i> Comptable
                                    </span>
                                @elseif($user->role === 'professeur')
                                    <span class="badge bg-info fs-6">
                                        <i class="fas fa-chalkboard-teacher"></i> Professeur
                                    </span>
                                @else
                                    <span class="badge bg-success fs-6">
                                        <i class="fas fa-user-graduate"></i> Stagiaire
                                    </span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Statut</label>
                            <p class="mb-0">
                                @if($user->is_active)
                                    <span class="badge bg-success fs-6">
                                        <i class="fas fa-check-circle"></i> Actif
                                    </span>
                                @else
                                    <span class="badge bg-secondary fs-6">
                                        <i class="fas fa-times-circle"></i> Inactif
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>

                    @if($user->telephone)
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Téléphone</label>
                            <p class="mb-0">
                                <i class="fas fa-phone text-success"></i>
                                <a href="tel:{{ $user->telephone }}">{{ $user->telephone }}</a>
                            </p>
                        </div>
                        @if($user->specialite)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Spécialité</label>
                            <p class="mb-0">{{ $user->specialite }}</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    @if($user->bio)
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="text-muted small mb-1">Biographie</label>
                            <p class="mb-0 text-justify">{{ $user->bio }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Filières et Matières (pour professeurs) -->
            @if($user->role === 'professeur')
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-book"></i> Filières et Matières
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-graduation-cap"></i> Filières assignées
                            </h6>
                            @if($user->filieres->count() > 0)
                                <ul class="list-group">
                                    @foreach($user->filieres as $filiere)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <strong>{{ $filiere->nom }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $filiere->niveau }}</small>
                                        </span>
                                        @if($filiere->pivot->is_active)
                                            <span class="badge bg-success">Actif</span>
                                        @else
                                            <span class="badge bg-secondary">Inactif</span>
                                        @endif
                                    </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    Aucune filière assignée
                                </div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-book-open"></i> Matières enseignées
                            </h6>
                            @if($user->matieresEnseignees->count() > 0)
                                <ul class="list-group">
                                    @foreach($user->matieresEnseignees as $matiere)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <strong>{{ $matiere->nom }}</strong>
                                            <br>
                                            <small class="text-muted">Code: {{ $matiere->code }}</small>
                                        </span>
                                        @if($matiere->pivot->is_active)
                                            <span class="badge bg-success">Actif</span>
                                        @else
                                            <span class="badge bg-secondary">Inactif</span>
                                        @endif
                                    </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    Aucune matière assignée
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Statistiques d'activité -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar"></i> Statistiques d'activité
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        @if($user->role === 'professeur' || $user->role === 'administrateur')
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3">
                                <h3 class="text-primary mb-0">{{ $stats['notes_creees'] }}</h3>
                                <small class="text-muted">Notes créées</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3">
                                <h3 class="text-info mb-0">{{ $stats['plannings_crees'] }}</h3>
                                <small class="text-muted">Plannings créés</small>
                            </div>
                        </div>
                        @endif
                        
                        @if($user->role === 'administrateur')
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3">
                                <h3 class="text-success mb-0">{{ $stats['stagiaires_crees'] }}</h3>
                                <small class="text-muted">Stagiaires créés</small>
                            </div>
                        </div>
                        @endif

                        @if($user->role === 'comptable')
                        <div class="col-md-6 mb-3">
                            <div class="border rounded p-3 bg-warning bg-opacity-10">
                                <h3 class="text-warning mb-0">{{ $stats['paiements_valides'] ?? 0 }}</h3>
                                <small class="text-muted">Paiements validés</small>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="border rounded p-3 bg-success bg-opacity-10">
                                <h3 class="text-success mb-0">{{ number_format($stats['montant_valide'] ?? 0, 2) }} DH</h3>
                                <small class="text-muted">Montant total validé</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne latérale -->
        <div class="col-lg-4">
            <!-- Informations système -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cog"></i> Informations système
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Date de création</label>
                        <p class="mb-0">
                            <i class="fas fa-calendar-plus text-primary"></i>
                            {{ $user->created_at->format('d/m/Y à H:i') }}
                        </p>
                    </div>

                    @if($user->createdBy)
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Créé par</label>
                        <p class="mb-0">
                            <i class="fas fa-user text-info"></i>
                            {{ $user->createdBy->name }}
                        </p>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="text-muted small mb-1">Dernière modification</label>
                        <p class="mb-0">
                            <i class="fas fa-calendar-edit text-warning"></i>
                            {{ $user->updated_at->format('d/m/Y à H:i') }}
                        </p>
                    </div>

                    @if($user->is_active && $user->activated_at)
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Activé le</label>
                        <p class="mb-0">
                            <i class="fas fa-check-circle text-success"></i>
                            {{ $user->activated_at->format('d/m/Y à H:i') }}
                        </p>
                    </div>

                    @if($user->activatedBy)
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Activé par</label>
                        <p class="mb-0">
                            <i class="fas fa-user-check text-success"></i>
                            {{ $user->activatedBy->name }}
                        </p>
                    </div>
                    @endif
                    @endif

                    @if($user->email_verified_at)
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Email vérifié</label>
                        <p class="mb-0">
                            <i class="fas fa-envelope-circle-check text-success"></i>
                            {{ $user->email_verified_at->format('d/m/Y à H:i') }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card shadow-sm">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt"></i> Actions rapides
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Modifier l'utilisateur
                        </a>

                        @if($user->id !== auth()->id())
                        <form action="{{ route('users.toggle-active', $user->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" 
                                    class="btn w-100 {{ $user->is_active ? 'btn-secondary' : 'btn-success' }}"
                                    onclick="return confirm('{{ $user->is_active ? 'Êtes-vous sûr de vouloir désactiver cet utilisateur ?' : 'Voulez-vous activer cet utilisateur ?' }}')">
                                <i class="fas {{ $user->is_active ? 'fa-ban' : 'fa-check-circle' }}"></i>
                                {{ $user->is_active ? 'Désactiver' : 'Activer' }}
                            </button>
                        </form>

                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100" 
                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.text-justify {
    text-align: justify;
}

.border {
    border: 1px solid #dee2e6;
}

.card {
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-2px);
}

.list-group-item {
    border-left: 3px solid transparent;
    transition: all 0.3s;
}

.list-group-item:hover {
    border-left-color: #0d6efd;
    background-color: #f8f9fa;
}

.badge {
    padding: 0.5em 0.8em;
}

.fs-6 {
    font-size: 0.95rem;
}
</style>
@endsection