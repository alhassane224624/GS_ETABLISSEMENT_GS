@extends('layouts.app-professeur')

@section('content')
    <div class="container py-5">
        <h1 class="fw-bold text-primary">
            <i class="fas fa-book me-2"></i> Notes par Matière
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

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body">
                {{-- ✅ CORRECTION: Utiliser tirets au lieu de points --}}
                <form method="GET" action="{{ route('professeur.notes-par-matiere') }}">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="matiere_id" class="form-label">Filtrer par matière</label>
                            <select name="matiere_id" id="matiere_id" class="form-control">
                                <option value="">Toutes les matières</option>
                                @foreach ($matieres as $matiere)
                                    <option value="{{ $matiere->id }}" {{ request('matiere_id') == $matiere->id ? 'selected' : '' }}>
                                        {{ $matiere->nom }} ({{ $matiere->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="fas fa-search me-2"></i> Filtrer
                                </button>
                                <a href="{{ route('professeur.notes-par-matiere') }}" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des notes -->
        <div class="card">
            <div class="card-body">
                <h3 class="card-title mb-4">Liste des Notes</h3>
                
                @if($notes->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Stagiaire</th>
                                    <th>Matricule</th>
                                    <th>Filière</th>
                                    <th>Matière</th>
                                    <th class="text-center">Note</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($notes as $note)
                                    <tr>
                                        <td>
                                            <strong>{{ $note->stagiaire->nom }} {{ $note->stagiaire->prenom }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $note->stagiaire->matricule }}</span>
                                        </td>
                                        <td>{{ $note->stagiaire->filiere->nom ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-primary">{{ $note->matiere->nom ?? 'N/A' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge fs-6 {{ $note->note_sur_20 >= 10 ? 'bg-success' : 'bg-danger' }}">
                                                {{ number_format($note->note, 2) }}/{{ $note->note_sur ?? 20 }}
                                            </span>
                                            @unless ($note->estPubliee())
                                                <div><span class="badge bg-light text-muted border mt-1" title="Visible par le stagiaire après publication des résultats"><i class="fas fa-eye-slash me-1"></i>Non publiée</span></div>
                                            @endunless
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ strtoupper($note->type_note) }}</span>
                                        </td>
                                        <td>
                                            <small>{{ $note->created_at->format('d/m/Y H:i') }}</small>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('professeur.stagiaires.notes', $note->stagiaire) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Voir toutes les notes de ce stagiaire">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $notes->appends(request()->query())->links() }}
                    </div>

                    <!-- Statistiques -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Total Notes</h5>
                                    <p class="display-6">{{ $notes->total() }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Moyenne Générale</h5>
                                    <p class="display-6">{{ number_format($notes->avg('note'), 2) }}/20</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Taux de Réussite</h5>
                                    <p class="display-6">
                                        {{ $notes->total() > 0 ? number_format(($notes->where('note', '>=', 10)->count() / $notes->total()) * 100, 1) : 0 }}%
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list text-muted" style="font-size: 4rem;"></i>
                        <p class="text-muted mt-3">
                            @if(request('matiere_id'))
                                Aucune note trouvée pour cette matière.
                            @else
                                Vous n'avez pas encore saisi de notes.
                            @endif
                        </p>
                        <a href="{{ route('professeur.stagiaires') }}" class="btn btn-primary mt-2">
                            <i class="fas fa-plus me-2"></i> Saisir des notes
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Bouton retour -->
        <div class="mt-4">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Retour au dashboard
            </a>
        </div>
    </div>
@endsection