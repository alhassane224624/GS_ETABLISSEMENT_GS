@extends('layouts.app-professeur')

@section('content')
    <div class="container-fluid px-3 px-md-4 py-4 py-md-5">
        <h1 class="fw-bold text-primary mb-4">
            <i class="fas fa-users me-2"></i> 
            <span class="d-none d-sm-inline">Liste des Stagiaires</span>
            <span class="d-inline d-sm-none">Stagiaires</span>
        </h1>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Formulaire de recherche et filtres -->
        <div class="card mb-3 mb-md-4 shadow-sm">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('professeur.stagiaires') }}">
                    <div class="row g-2 g-md-3">
                        <div class="col-12 col-lg-6">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Rechercher..." 
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-12 col-sm-7 col-lg-4">
                            <select name="filiere_id" class="form-control">
                                <option value="">Toutes les filières</option>
                                @foreach ($filieres as $filiere)
                                    <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                        {{ $filiere->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-5 col-lg-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> 
                                <span class="d-none d-sm-inline">Filtrer</span>
                                <span class="d-inline d-sm-none">OK</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tableau des stagiaires -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <!-- Vue Desktop/Tablette -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3 py-3">Matricule</th>
                                <th class="px-3 py-3">Nom</th>
                                <th class="px-3 py-3">Prénom</th>
                                <th class="px-3 py-3">Filière</th>
                                <th class="px-3 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stagiaires as $stagiaire)
                                <tr>
                                    <td class="px-3 py-3">{{ $stagiaire->matricule }}</td>
                                    <td class="px-3 py-3">{{ $stagiaire->nom }}</td>
                                    <td class="px-3 py-3">{{ $stagiaire->prenom }}</td>
                                    <td class="px-3 py-3">{{ $stagiaire->filiere->nom ?? 'N/A' }}</td>
                                    <td class="px-3 py-3 text-center">
                                        <a href="{{ route('professeur.stagiaires.notes', $stagiaire) }}" 
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-eye me-1"></i> Voir les notes
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="fas fa-exclamation-circle me-2 fs-4"></i>
                                        <div class="mt-2">Aucun stagiaire trouvé.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Vue Mobile (Cards) -->
                <div class="d-md-none">
                    @forelse ($stagiaires as $stagiaire)
                        <div class="border-bottom p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1 fw-bold">{{ $stagiaire->nom }} {{ $stagiaire->prenom }}</h6>
                                    <small class="text-muted">
                                        <i class="fas fa-id-card me-1"></i>{{ $stagiaire->matricule }}
                                    </small>
                                </div>
                            </div>
                            <div class="mb-2">
                                <span class="badge bg-secondary">
                                    <i class="fas fa-graduation-cap me-1"></i>
                                    {{ $stagiaire->filiere->nom ?? 'N/A' }}
                                </span>
                            </div>
                            <a href="{{ route('professeur.stagiaires.notes', $stagiaire) }}" 
                               class="btn btn-sm btn-info w-100">
                                <i class="fas fa-eye me-1"></i> Voir les notes
                            </a>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5 px-3">
                            <i class="fas fa-exclamation-circle fs-1 mb-3 d-block"></i>
                            <p class="mb-0">Aucun stagiaire trouvé.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if ($stagiaires->hasPages())
                    <div class="p-3 border-top">
                        <div class="d-flex justify-content-center">
                            {{ $stagiaires->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        /* Améliorations responsive supplémentaires */
        @media (max-width: 767.98px) {
            .container-fluid {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            .card {
                border-radius: 0.5rem;
            }
            
            .btn-sm {
                font-size: 0.875rem;
                padding: 0.375rem 0.75rem;
            }
        }
        
        @media (min-width: 768px) {
            .table th {
                position: sticky;
                top: 0;
                background-color: #f8f9fa;
                z-index: 10;
            }
        }
        
        /* Animation hover sur les cartes mobiles */
        @media (max-width: 767.98px) {
            .border-bottom:hover {
                background-color: #f8f9fa;
                transition: background-color 0.2s ease;
            }
        }
    </style>
@endsection