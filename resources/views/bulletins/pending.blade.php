@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-clock text-warning"></i> 
                    Bulletins en Attente de Validation
                </h2>
                <a href="{{ route('bulletins.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Tous les bulletins
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if($bulletins->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Aucun bulletin en attente de validation.
        </div>
    @else
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>{{ $bulletins->count() }}</strong> bulletin(s) nécessite(nt) votre validation.
        </div>

        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> Liste des bulletins à valider
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Stagiaire</th>
                                <th>Classe</th>
                                <th>Période</th>
                                <th>Moyenne</th>
                                <th>Rang</th>
                                <th>Créé le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bulletins as $bulletin)
                                <tr>
                                    <td>
                                        <strong>{{ $bulletin->stagiaire->nom_complet }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $bulletin->stagiaire->matricule }}</small>
                                    </td>
                                    <td>{{ $bulletin->classe->nom }}</td>
                                    <td>{{ $bulletin->periode->nom }}</td>
                                    <td>
                                        <span class="badge badge-{{ $bulletin->moyenne_generale >= 10 ? 'success' : 'danger' }} badge-lg">
                                            {{ number_format($bulletin->moyenne_generale, 2) }}/20
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">
                                            {{ $bulletin->rang }}/{{ $bulletin->total_classe }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $bulletin->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('bulletins.show', $bulletin) }}" 
                                               class="btn btn-sm btn-info" 
                                               title="Consulter">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <button type="button" 
                                                    class="btn btn-sm btn-success" 
                                                    onclick="validerBulletin({{ $bulletin->id }})"
                                                    title="Valider maintenant">
                                                <i class="fas fa-check"></i> Valider
                                            </button>
                                            
                                            <form id="form-valider-{{ $bulletin->id }}" 
                                                  action="{{ route('bulletins.validate', $bulletin) }}" 
                                                  method="POST" 
                                                  style="display:none;">
                                                @csrf
                                                @method('PATCH')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Action groupée -->
                <div class="mt-4">
                    <button type="button" 
                            class="btn btn-success btn-lg" 
                            onclick="validerTous()">
                        <i class="fas fa-check-double"></i> Valider tous les bulletins affichés
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
function validerBulletin(bulletinId) {
    if (confirm('Confirmer la validation de ce bulletin ?')) {
        document.getElementById('form-valider-' + bulletinId).submit();
    }
}

function validerTous() {
    if (confirm('Êtes-vous sûr de vouloir valider TOUS les bulletins affichés sur cette page ? Cette action est irréversible.')) {
        // Soumettre tous les formulaires
        let forms = document.querySelectorAll('form[id^="form-valider-"]');
        let formData = new FormData();
        let bulletinIds = [];
        
        forms.forEach(form => {
            let id = form.id.replace('form-valider-', '');
            bulletinIds.push(id);
        });
        
        // Créer une requête AJAX pour valider tous les bulletins
        fetch('{{ route("bulletins.validate-multiple") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                bulletin_ids: bulletinIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            }
        })
        .catch(error => {
            alert('Une erreur est survenue');
            console.error(error);
        });
    }
}
</script>
@endpush
@endsection