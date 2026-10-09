@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-user-times"></i> Gestion des Absences</h2>
                <a href="{{ route('absences.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Enregistrer une Absence
                </a>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('absences.index') }}">
                <div class="row">
                    <div class="col-md-2">
                        <label>Filière</label>
                        <select name="filiere_id" class="form-control">
                            <option value="">Toutes</option>
                            @foreach($filieres as $filiere)
                                <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                    {{ $filiere->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Date début</label>
                        <input type="date" name="date_debut" class="form-control" value="{{ request('date_debut') }}">
                    </div>
                    <div class="col-md-2">
                        <label>Date fin</label>
                        <input type="date" name="date_fin" class="form-control" value="{{ request('date_fin') }}">
                    </div>
                    <div class="col-md-2">
                        <label>Type</label>
                        <select name="type" class="form-control">
                            <option value="">Tous</option>
                            <option value="matin" {{ request('type') == 'matin' ? 'selected' : '' }}>Matin</option>
                            <option value="apres_midi" {{ request('type') == 'apres_midi' ? 'selected' : '' }}>Après-midi</option>
                            <option value="journee" {{ request('type') == 'journee' ? 'selected' : '' }}>Journée</option>
                            <option value="heure" {{ request('type') == 'heure' ? 'selected' : '' }}>Par heure</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Justifiée</label>
                        <select name="justifiee" class="form-control">
                            <option value="">Toutes</option>
                            <option value="1" {{ request('justifiee') == '1' ? 'selected' : '' }}>Oui</option>
                            <option value="0" {{ request('justifiee') == '0' ? 'selected' : '' }}>Non</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-filter"></i> Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- Liste des absences -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Liste des Absences</h5>
                <a href="{{ route('absences.rapport') }}" class="btn btn-sm btn-info">
                    <i class="fas fa-chart-bar"></i> Rapport
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Stagiaire</th>
                            <th>Filière</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Durée</th>
                            <th>Justifiée</th>
                            <th>Motif</th>
                            <th>Enregistré par</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($absences as $absence)
                            <tr>
                                <td>
                                    <a href="{{ route('stagiaires.show', $absence->stagiaire) }}">
                                        {{ $absence->stagiaire->nom_complet }}
                                    </a>
                                </td>
                                <td>{{ $absence->stagiaire->filiere->nom }}</td>
                                <td>{{ $absence->date->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge badge-secondary">{{ $absence->type_libelle }}</span>
                                </td>
                                <td>
                                    @if($absence->type == 'heure')
                                        {{ $absence->heure_debut }} - {{ $absence->heure_fin }}
                                    @else
                                        {{ $absence->duree }}
                                    @endif
                                </td>
                                <td>
                                    @if($absence->justifiee)
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Oui
                                        </span>
                                    @else
                                        <span class="badge badge-danger">
                                            <i class="fas fa-times"></i> Non
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($absence->motif)
                                        <span class="text-muted" title="{{ $absence->motif }}">
                                            {{ Str::limit($absence->motif, 30) }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $absence->creator->name ?? 'Utilisateur supprimé' }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('absences.show', $absence) }}" class="btn btn-sm btn-info" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('absences.edit', $absence) }}" class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteAbsence({{ $absence->id }})" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">Aucune absence trouvée</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $absences->links() }}
            </div>
        </div>
    </div>
</div>

<script>
function deleteAbsence(absenceId) {
    if(confirm('Êtes-vous sûr de vouloir supprimer cette absence ?')) {
        fetch(`/absences/${absenceId}`, {
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
            }
        });
    }
}
</script>
@endsection