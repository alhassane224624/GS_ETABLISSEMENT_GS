@extends($layout)

@section('title', 'Notes — ' . ($epreuve->matiere->nom ?? ''))

@section('content')
@php
    $admin = auth()->user()->isAdmin();
    $action = $admin ? route('epreuves.saisie.store', $epreuve) : route('professeur.epreuves.saisie.store', $epreuve);
    $retour = $admin ? route('examens.show', $epreuve->examen_id) : route('professeur.epreuves');
    $ferme = $epreuve->examen->estCloturee();
    $sur = rtrim(rtrim($epreuve->note_sur, '0'), '.');
@endphp
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ $epreuve->matiere->nom ?? '' }} — {{ $epreuve->classe->nom ?? '' }}</h4>
            <div class="text-muted">{{ $epreuve->examen->nom }} ({{ $epreuve->examen->type_libelle }}) · {{ $epreuve->date->format('d/m/Y') }} {{ $epreuve->horaire }} · barème /{{ $sur }}</div>
        </div>
        <a href="{{ $retour }}" class="btn btn-outline-secondary btn-sm">Retour</a>
    </div>

    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if ($ferme)<div class="alert alert-secondary"><i class="fas fa-lock me-1"></i>Session clôturée : consultation seulement.</div>@endif
    @if ($epreuve->examen->estRattrapage())
        <div class="alert alert-info small">Rattrapage : seuls les stagiaires « admis au rattrapage » sont convoqués. La note remplace la moyenne annuelle de la matière si elle est meilleure.</div>
    @else
        <div class="alert alert-light border small">Chaque note est enregistrée comme note d'<strong>examen</strong> de la période {{ $epreuve->examen->periode->nom ?? '' }} et compte dans le bulletin. Absent = 0.</div>
    @endif

    <form method="POST" action="{{ $action }}">
        @csrf
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Stagiaire</th><th style="width: 160px;">Note /{{ $sur }}</th><th class="text-center">Absent</th></tr></thead>
                    <tbody>
                        @forelse ($convoques as $i => $s)
                            @php $r = $resultats[$s->id] ?? null; @endphp
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td><strong>{{ $s->nom }} {{ $s->prenom }}</strong> <span class="small text-muted">{{ $s->matricule }}</span></td>
                                <td><input type="number" step="0.25" min="0" max="{{ $epreuve->note_sur }}" name="notes[{{ $s->id }}]"
                                           value="{{ old("notes.{$s->id}", $r && !$r->absent ? $r->note : '') }}" class="form-control form-control-sm" @disabled($ferme)></td>
                                <td class="text-center"><input type="checkbox" name="absents[{{ $s->id }}]" value="1" class="form-check-input" @checked(old("absents.{$s->id}", $r->absent ?? false)) @disabled($ferme)></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Aucun convoqué.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @unless ($ferme)
                <div class="card-footer bg-white text-end">
                    <small class="text-muted me-3">Une note vide = pas encore corrigée.</small>
                    <button class="btn btn-primary"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            @endunless
        </div>
    </form>
</div>
@endsection
