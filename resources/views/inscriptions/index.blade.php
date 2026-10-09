@extends('layouts.app')

@section('title', 'Inscriptions')
@section('page-title', 'Inscriptions annuelles')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <form method="GET" class="d-flex gap-2 align-items-center">
        <label class="text-muted text-nowrap">Année scolaire</label>
        <select name="annee_id" class="form-select" onchange="this.form.submit()">
            @foreach ($annees as $a)
                <option value="{{ $a->id }}" @selected(optional($annee)->id === $a->id)>{{ $a->nom }}{{ $a->is_active ? ' (active)' : '' }}</option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('inscriptions.passage.form', ['source_id' => optional($annee)->id]) }}" class="btn btn-primary">
        <i class="fas fa-forward me-1"></i>Passage vers l'année suivante
    </a>
</div>

<div class="alert alert-light border small">
    <strong>Fin d'année, en 3 étapes :</strong>
    1) validez les bulletins de toutes les périodes ;
    2) <strong>délibérez</strong> chaque classe (admis, redouble, diplômé, exclu) ;
    3) faites le <strong>passage</strong> vers l'année suivante, puis activez-la dans <em>Années scolaires</em>.
</div>

@if (!$annee)
    <div class="alert alert-info">Aucune année scolaire n'existe encore.</div>
@else
    <div class="card shadow-sm mb-4">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Classe</th><th>Filière</th><th>Niveau</th><th class="text-center">Inscrits / places</th><th>Délibération</th><th class="text-end"></th></tr>
                </thead>
                <tbody>
                    @forelse ($classes as $classe)
                        <tr>
                            <td class="fw-semibold">{{ $classe->nom }}</td>
                            <td>{{ $classe->filiere->nom ?? '—' }}</td>
                            <td>{{ $classe->niveau->nom ?? '—' }}</td>
                            <td class="text-center">
                                <span class="{{ $classe->inscrits >= $classe->effectif_max ? 'text-danger fw-bold' : '' }}">{{ $classe->inscrits }}</span> / {{ $classe->effectif_max }}
                            </td>
                            <td>
                                @if ($classe->total === 0)
                                    <span class="text-muted">—</span>
                                @elseif ($classe->decides === $classe->total)
                                    <span class="badge bg-success">Terminée</span>
                                @else
                                    <span class="badge bg-warning text-dark">{{ $classe->decides }} / {{ $classe->total }} décidé(s)</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($classe->total > 0)
                                    <a href="{{ route('inscriptions.deliberation', $classe) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-gavel me-1"></i>Délibérer</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucune classe pour {{ $annee->nom }}. Créez les classes de l'année dans <em>Classes</em>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($sansClasse->isNotEmpty())
        <div class="card shadow-sm border-warning">
            <div class="card-header bg-white fw-semibold text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Inscrits sans classe ({{ $sansClasse->count() }})</div>
            <ul class="list-group list-group-flush">
                @foreach ($sansClasse as $i)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $i->stagiaire->nom }} {{ $i->stagiaire->prenom }} <small class="text-muted">{{ $i->filiere->nom ?? '' }}</small></span>
                        <a href="{{ route('stagiaires.edit', $i->stagiaire) }}" class="btn btn-sm btn-outline-secondary">Affecter une classe</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endif
@endsection
