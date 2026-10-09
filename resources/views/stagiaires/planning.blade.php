@extends('layouts.app-professeur')

@section('title', 'Mon Planning')
@section('page-title', 'Mon Planning')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-calendar-alt text-primary me-2"></i>
                            Mon Planning
                        </h5>
                        <form method="GET" action="{{ route('professeur.planning') }}" class="d-flex gap-2">
                            <input type="date" name="date" class="form-control" value="{{ $date }}" onchange="this.form.submit()">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @if($plannings->count() > 0)
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-semibold">
                            <i class="fas fa-calendar-day me-2"></i>
                            {{ \Carbon\Carbon::parse($date)->isoFormat('dddd D MMMM YYYY') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach($plannings as $cours)
                                <div class="row mb-4">
                                    <div class="col-auto">
                                        <div class="badge bg-primary px-3 py-2 text-center">
                                            <div class="fw-bold">{{ $cours->heure_debut }}</div>
                                            <div class="small">{{ $cours->heure_fin }}</div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="card border-start border-4 border-primary">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h5 class="mb-1 fw-bold">{{ $cours->matiere->nom ?? 'N/A' }}</h5>
                                                        <span class="badge bg-secondary">{{ ucfirst($cours->type_cours) }}</span>
                                                    </div>
                                                    <span class="badge bg-{{ $cours->statut_color }}">
                                                        {{ $cours->statut_libelle }}
                                                    </span>
                                                </div>
                                                
                                                <div class="row mt-3">
                                                    <div class="col-md-4">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <i class="fas fa-users text-muted me-2"></i>
                                                            <span>{{ $cours->classe->nom ?? 'N/A' }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <i class="fas fa-book text-muted me-2"></i>
                                                            <span>{{ $cours->classe->filiere->nom ?? 'N/A' }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <i class="fas fa-door-open text-muted me-2"></i>
                                                            <span>Salle {{ $cours->salle->nom ?? 'N/A' }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                @if($cours->description)
                                                    <div class="mt-3">
                                                        <p class="text-muted mb-0 small">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            {{ $cours->description }}
                                                        </p>
                                                    </div>
                                                @endif

                                                <div class="mt-3 d-flex gap-2">
                                                    @if(in_array($cours->statut, ['valide', 'en_cours', 'termine']))
                                                        <a href="{{ route('professeur.seance', $cours) }}"
                                                           class="btn btn-sm {{ $cours->appel_fait_at ? 'btn-outline-success' : 'btn-success' }}">
                                                            <i class="fas fa-{{ $cours->appel_fait_at ? 'check-double' : 'user-check' }} me-1"></i>
                                                            {{ $cours->appel_fait_at ? 'Appel fait — modifier' : 'Faire l\'appel' }} &amp; cahier de textes
                                                        </a>
                                                    @elseif($cours->statut === 'brouillon')
                                                        <span class="small text-muted"><i class="fas fa-hourglass-half me-1"></i>En attente de validation par l'administration</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun cours prévu</h5>
                        <p class="text-muted mb-0">Vous n'avez aucun cours prévu pour cette date</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Navigation de dates -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-center gap-2">
                <a href="{{ route('professeur.planning') }}?date={{ \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d') }}" 
                   class="btn btn-outline-primary">
                    <i class="fas fa-chevron-left"></i> Jour précédent
                </a>
                <a href="{{ route('professeur.planning') }}?date={{ now()->format('Y-m-d') }}" 
                   class="btn btn-primary">
                    Aujourd'hui
                </a>
                <a href="{{ route('professeur.planning') }}?date={{ \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d') }}" 
                   class="btn btn-outline-primary">
                    Jour suivant <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection