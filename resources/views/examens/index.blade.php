@extends('layouts.app')

@section('title', 'Examens')
@section('page-title', 'Sessions d\'examen')

@section('content')
<form method="GET" class="d-flex gap-2 align-items-center mb-3">
    <label class="text-muted">Année</label>
    <select name="annee_id" class="form-select w-auto" onchange="this.form.submit()">
        @foreach ($annees as $a)<option value="{{ $a->id }}" @selected(optional($annee)->id === $a->id)>{{ $a->nom }}{{ $a->is_active ? ' (active)' : '' }}</option>@endforeach
    </select>
</form>

@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Session</th><th>Type</th><th>Période</th><th>Dates</th><th class="text-center">Épreuves</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($examens as $e)
                            <tr>
                                <td class="fw-semibold">{{ $e->nom }}</td>
                                <td><span class="badge bg-{{ $e->estRattrapage() ? 'warning text-dark' : 'primary' }}">{{ $e->type_libelle }}</span></td>
                                <td>{{ $e->periode->nom ?? '—' }}</td>
                                <td class="small">{{ $e->date_debut->format('d/m') }} → {{ $e->date_fin->format('d/m/Y') }}</td>
                                <td class="text-center">{{ $e->epreuves_count }}</td>
                                <td><span class="badge bg-{{ $e->estCloturee() ? 'secondary' : 'success' }}">{{ $e->estCloturee() ? 'Clôturée' : 'Ouverte' }}</span></td>
                                <td class="text-end"><a href="{{ route('examens.show', $e) }}" class="btn btn-sm btn-outline-primary">Ouvrir</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Aucune session pour cette année.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="alert alert-light border small mt-3">
            <strong>Déroulement :</strong> session normale (les notes vont dans les bulletins de la période) → validation des bulletins →
            délibération (Inscriptions) : « admis au rattrapage » pour les moyennes entre le seuil de rattrapage et le seuil d'admission →
            session de rattrapage (seuls ces stagiaires sont convoqués ; la meilleure note remplace la moyenne de la matière) →
            délibération finale et procès-verbal.
        </div>
    </div>

    @if ($annee)
    <div class="col-lg-4">
        <form method="POST" action="{{ route('examens.store') }}" class="card shadow-sm">
            @csrf
            <input type="hidden" name="annee_scolaire_id" value="{{ $annee->id }}">
            <div class="card-header bg-white fw-semibold"><i class="fas fa-plus me-1 text-primary"></i>Nouvelle session</div>
            <div class="card-body row g-2">
                <div class="col-12"><label class="form-label">Nom</label><input name="nom" class="form-control" value="{{ old('nom') }}" placeholder="Ex. Examens du 1er semestre" required></div>
                <div class="col-12">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">@foreach (\App\Models\Examen::TYPES as $k => $v)<option value="{{ $k }}" @selected(old('type') === $k)>{{ $v }}</option>@endforeach</select>
                </div>
                <div class="col-12">
                    <label class="form-label">Période <small class="text-muted">(obligatoire en session normale)</small></label>
                    <select name="periode_id" class="form-select"><option value="">—</option>@foreach ($periodes as $p)<option value="{{ $p->id }}" @selected(old('periode_id') == $p->id)>{{ $p->nom }}</option>@endforeach</select>
                </div>
                <div class="col-6"><label class="form-label">Du</label><input type="date" name="date_debut" value="{{ old('date_debut') }}" class="form-control" required></div>
                <div class="col-6"><label class="form-label">Au</label><input type="date" name="date_fin" value="{{ old('date_fin') }}" class="form-control" required></div>
                <div class="col-12"><button class="btn btn-primary w-100 mt-2">Créer</button></div>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
