@extends('layouts.app')

@section('title', 'Passage d\'année')
@section('page-title', 'Passage vers l\'année suivante')

@section('content')
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
@if (session('erreurs_passage'))
    <div class="alert alert-warning">
        <strong>Non traités :</strong>
        <ul class="mb-0 small">@foreach (session('erreurs_passage') as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="GET" class="card shadow-sm mb-4">
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label">De l'année</label>
            <select name="source_id" class="form-select" onchange="this.form.submit()">
                @foreach ($annees as $a)<option value="{{ $a->id }}" @selected(optional($source)->id === $a->id)>{{ $a->nom }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label">Vers l'année</label>
            <select name="cible_id" class="form-select" onchange="this.form.submit()">
                <option value="">— Choisir —</option>
                @foreach ($annees as $a)<option value="{{ $a->id }}" @selected(optional($cible)->id === $a->id)>{{ $a->nom }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2"><a href="{{ route('annees-scolaires.create') }}" class="btn btn-outline-secondary w-100">Nouvelle année</a></div>
    </div>
</form>

@if (!$source || !$cible || $source->id === $cible->id)
    <div class="alert alert-info">Choisissez l'année qui se termine et la nouvelle année (créez-la d'abord, avec ses classes, si besoin).</div>
@elseif ($classesCible->isEmpty())
    <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>L'année {{ $cible->nom }} n'a encore aucune classe.</span>
        <form method="POST" action="{{ route('inscriptions.copier-classes') }}">
            @csrf
            <input type="hidden" name="source_id" value="{{ $source->id }}">
            <input type="hidden" name="cible_id" value="{{ $cible->id }}">
            <button class="btn btn-sm btn-warning"><i class="fas fa-copy me-1"></i>Copier les classes de {{ $source->nom }}</button>
            <a href="{{ route('classes.create') }}" class="btn btn-sm btn-outline-secondary">ou créer une classe</a>
        </form>
    </div>
@else
    <form method="POST" action="{{ route('inscriptions.passage') }}" onsubmit="return confirm('Créer les inscriptions {{ $cible->nom }} selon les décisions ?')">
        @csrf
        <input type="hidden" name="source_id" value="{{ $source->id }}">
        <input type="hidden" name="cible_id" value="{{ $cible->id }}">

        <div class="card shadow-sm mb-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Classe {{ $source->nom }}</th><th>Décisions</th><th>Admis → classe {{ $cible->nom }}</th><th>Redoublants → classe {{ $cible->nom }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($groupes as $g)
                            <tr>
                                <td><strong>{{ $g->nom }}</strong><div class="small text-muted">{{ $g->filiere->nom ?? '' }} · {{ $g->niveau->nom ?? '' }}</div></td>
                                <td class="small">
                                    <span class="badge bg-success">{{ $g->admis }} admis</span>
                                    <span class="badge bg-warning text-dark">{{ $g->redoublants }} redoublant(s)</span>
                                    <span class="badge bg-secondary">{{ $g->sortants }} sortant(s)</span>
                                    @if ($g->sans_decision)
                                        <div class="text-danger mt-1"><a href="{{ route('inscriptions.deliberation', $g) }}">{{ $g->sans_decision }} sans décision</a> — non traités</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($g->admis)
                                        @if ($g->cibles_admis->isEmpty())
                                            <span class="small text-danger">Aucune classe {{ $g->niveau_suivant->nom ?? '' }} dans {{ $cible->nom }}</span>
                                        @else
                                            <select name="affectations[{{ $g->id }}][admis]" class="form-select form-select-sm">
                                                @foreach ($g->cibles_admis as $c)<option value="{{ $c->id }}">{{ $c->nom }} ({{ $c->inscrits }}/{{ $c->effectif_max }})</option>@endforeach
                                            </select>
                                        @endif
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                <td>
                                    @if ($g->redoublants)
                                        @if ($g->cibles_redouble->isEmpty())
                                            <span class="small text-danger">Aucune classe {{ $g->niveau->nom ?? '' }} dans {{ $cible->nom }}</span>
                                        @else
                                            <select name="affectations[{{ $g->id }}][redouble]" class="form-select form-select-sm">
                                                @foreach ($g->cibles_redouble as $c)<option value="{{ $c->id }}">{{ $c->nom }} ({{ $c->inscrits }}/{{ $c->effectif_max }})</option>@endforeach
                                            </select>
                                        @endif
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Frais de réinscription (DH)</label>
                    <input type="number" step="0.01" min="0" name="frais_reinscription" class="form-control" placeholder="0 = aucune échéance">
                    <small class="text-muted">Crée une échéance « Frais de réinscription » pour chaque réinscrit.</small>
                </div>
                <div class="col-md-8 text-end">
                    <p class="small text-muted mb-2">Diplômés : statut « diplômé ». Exclus : statut « abandonné », compte désactivé. Déjà inscrits en {{ $cible->nom }} : ignorés.</p>
                    <button class="btn btn-primary"><i class="fas fa-forward me-1"></i>Créer les inscriptions {{ $cible->nom }}</button>
                </div>
            </div>
        </div>
    </form>
@endif
@endsection
