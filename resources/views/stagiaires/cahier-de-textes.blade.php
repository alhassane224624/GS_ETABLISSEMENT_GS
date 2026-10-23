@extends('layouts.app-stagiaire')

@section('title', 'Cahier de textes')
@section('page-title', 'Cahier de textes')

@section('content')
<div class="container-fluid">
    {{-- Devoirs à rendre --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-tasks text-warning me-2"></i>Devoirs à faire</h5>
        </div>
        <div class="card-body pt-0">
            @forelse ($aFaire as $seance)
                @php $jours = now()->startOfDay()->diffInDays($seance->devoirs_pour, false); @endphp
                <div class="d-flex gap-3 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="text-center" style="min-width: 80px;">
                        <div class="badge {{ $jours <= 1 ? 'bg-danger' : ($jours <= 3 ? 'bg-warning text-dark' : 'bg-primary') }} px-3 py-2">
                            <div class="fw-bold">{{ $seance->devoirs_pour->isoFormat('D MMM') }}</div>
                            <div class="small">{{ $jours === 0 ? "Aujourd'hui" : ($jours === 1 ? 'Demain' : "Dans {$jours} j") }}</div>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $seance->matiere->nom ?? '—' }}</div>
                        <div class="small text-muted mb-1">
                            Donné le {{ $seance->date->isoFormat('dddd D MMMM') }} par {{ $seance->professeur->name ?? '—' }}
                        </div>
                        <div style="white-space: pre-line;">{{ $seance->devoirs }}</div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>
                    Aucun devoir à rendre pour le moment.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Historique des séances --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0 fw-bold"><i class="fas fa-book-open text-primary me-2"></i>Séances passées</h5>
            <form method="GET" action="{{ route('stagiaire.cahier-de-textes') }}">
                <select name="matiere_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Toutes les matières</option>
                    @foreach ($matieres as $m)
                        <option value="{{ $m->id }}" @selected(request('matiere_id') == $m->id)>{{ $m->nom }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="card-body pt-0">
            @forelse ($seances as $seance)
                <div class="border-start border-4 border-primary ps-3 py-2 mb-3">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div class="fw-semibold">{{ $seance->matiere->nom ?? '—' }}</div>
                        <div class="small text-muted">
                            {{ $seance->date->isoFormat('dddd D MMMM YYYY') }} · {{ substr($seance->heure_debut, 0, 5) }}–{{ substr($seance->heure_fin, 0, 5) }}
                            · {{ $seance->professeur->name ?? '—' }}
                        </div>
                    </div>
                    @if ($seance->contenu_seance)
                        <div class="mt-1" style="white-space: pre-line;">{{ $seance->contenu_seance }}</div>
                    @endif
                    @if ($seance->devoirs)
                        <div class="alert alert-light border mt-2 mb-0 py-2 small">
                            <i class="fas fa-pencil-alt text-warning me-1"></i><strong>Devoirs</strong>
                            @if ($seance->devoirs_pour) pour le {{ $seance->devoirs_pour->isoFormat('D MMMM') }} @endif :
                            <span style="white-space: pre-line;">{{ $seance->devoirs }}</span>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-book fa-2x mb-2 d-block"></i>
                    Aucune séance renseignée{{ request('matiere_id') ? ' pour cette matière' : '' }}.
                </div>
            @endforelse

            {{ $seances->links() }}
        </div>
    </div>
</div>
@endsection
