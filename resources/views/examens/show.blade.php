@extends('layouts.app')

@section('title', $examen->nom)
@section('page-title', $examen->nom)

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="badge bg-{{ $examen->estRattrapage() ? 'warning text-dark' : 'primary' }}">{{ $examen->type_libelle }}</span>
            <span class="text-muted ms-2">{{ $examen->anneeScolaire->nom ?? '' }} · {{ $examen->periode->nom ?? 'sans période' }} · {{ $examen->date_debut->format('d/m') }} → {{ $examen->date_fin->format('d/m/Y') }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('examens.index', ['annee_id' => $examen->annee_scolaire_id]) }}" class="btn btn-outline-secondary btn-sm">Retour</a>
            <form method="POST" action="{{ route('examens.cloturer', $examen) }}" onsubmit="return confirm('{{ $examen->estCloturee() ? 'Rouvrir' : 'Clôturer' }} la session ?')">@csrf
                <button class="btn btn-sm {{ $examen->estCloturee() ? 'btn-outline-warning' : 'btn-outline-dark' }}"><i class="fas fa-{{ $examen->estCloturee() ? 'lock-open' : 'lock' }} me-1"></i>{{ $examen->estCloturee() ? 'Rouvrir' : 'Clôturer' }}</button>
            </form>
        </div>
    </div>
</div>

@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Date</th><th>Classe</th><th>Matière</th><th>Salle / surveillant</th><th class="text-center">Barème</th><th class="text-center">Notes saisies</th><th class="text-end"></th></tr></thead>
            <tbody>
                @forelse ($examen->epreuves as $ep)
                    <tr>
                        <td>{{ $ep->date->translatedFormat('D d/m') }}<div class="small text-muted">{{ $ep->horaire }}</div></td>
                        <td>{{ $ep->classe->nom ?? '' }}</td>
                        <td>{{ $ep->matiere->nom ?? '' }}</td>
                        <td class="small">{{ $ep->salle->nom ?? '—' }} · {{ $ep->surveillant->name ?? '—' }}@if ($ep->planning_id) <i class="fas fa-calendar-check text-success" title="Au planning"></i>@endif</td>
                        <td class="text-center">/{{ rtrim(rtrim($ep->note_sur, '0'), '.') }}</td>
                        <td class="text-center">{{ $ep->nb_notes }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('epreuves.saisie', $ep) }}" class="btn btn-sm btn-primary"><i class="fas fa-pen me-1"></i>Notes</a>
                            @unless ($examen->estCloturee())
                                <form method="POST" action="{{ route('epreuves.destroy', $ep) }}" class="d-inline" onsubmit="return confirm('Supprimer cette épreuve ?')">@csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune épreuve.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@unless ($examen->estCloturee())
<form method="POST" action="{{ route('examens.epreuves.store', $examen) }}" class="card shadow-sm">
    @csrf
    <div class="card-header bg-white fw-semibold"><i class="fas fa-plus me-1 text-primary"></i>Ajouter une épreuve</div>
    <div class="card-body row g-3">
        <div class="col-md-3"><label class="form-label">Classe</label>
            <select name="classe_id" class="form-select" required>@foreach ($classes as $c)<option value="{{ $c->id }}" @selected(old('classe_id') == $c->id)>{{ $c->nom }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Matière</label>
            <select name="matiere_id" class="form-select" required>@foreach ($matieres as $m)<option value="{{ $m->id }}" @selected(old('matiere_id') == $m->id)>{{ $m->nom }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Date</label><input type="date" name="date" min="{{ $examen->date_debut->toDateString() }}" max="{{ $examen->date_fin->toDateString() }}" value="{{ old('date', $examen->date_debut->toDateString()) }}" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label">Début</label><input type="time" name="heure_debut" value="{{ old('heure_debut', '09:00') }}" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label">Fin</label><input type="time" name="heure_fin" value="{{ old('heure_fin', '11:00') }}" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">Salle</label>
            <select name="salle_id" class="form-select"><option value="">—</option>@foreach ($salles as $s)<option value="{{ $s->id }}" @selected(old('salle_id') == $s->id)>{{ $s->nom }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Surveillant</label>
            <select name="surveillant_id" class="form-select"><option value="">—</option>@foreach ($surveillants as $p)<option value="{{ $p->id }}" @selected(old('surveillant_id') == $p->id)>{{ $p->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Barème</label><input type="number" step="0.5" name="note_sur" value="{{ old('note_sur', 20) }}" class="form-control" required></div>
        <div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary w-100">Ajouter</button></div>
        <div class="col-12"><small class="text-muted">Avec une salle et un surveillant, l'épreuve est inscrite au planning (séance « examen », conflits vérifiés).</small></div>
    </div>
</form>
@endunless
@endsection
