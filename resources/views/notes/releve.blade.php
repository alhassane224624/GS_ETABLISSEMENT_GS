@extends('layouts.app')

@section('title', 'Relevé de notes')
@section('page-title', 'Relevé de notes')

@section('content')
<div class="card shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h5 class="mb-1">{{ $stagiaire->prenom }} {{ $stagiaire->nom }}</h5>
            <small class="text-muted">
                Matricule {{ $stagiaire->matricule }} · {{ $stagiaire->filiere->nom ?? '—' }}
                @if($stagiaire->classe) · {{ $stagiaire->classe->nom }} @endif
            </small>
        </div>
        <form method="GET" class="d-flex gap-2">
            <select name="periode_id" class="form-select" onchange="this.form.submit()">
                <option value="">Toutes les périodes</option>
                @foreach ($periodes as $p)
                    <option value="{{ $p->id }}" @selected($periode_id == $p->id)>
                        {{ $p->nom }} {{ $p->anneeScolaire ? '(' . $p->anneeScolaire->nom . ')' : '' }}
                    </option>
                @endforeach
            </select>
        </form>
        <div class="text-end">
            <div class="text-muted small">Moyenne générale</div>
            <div class="fs-3 fw-bold {{ $moyenneGenerale !== null && $moyenneGenerale < 10 ? 'text-danger' : 'text-success' }}">
                {{ $moyenneGenerale !== null ? number_format($moyenneGenerale, 2, ',', ' ') . ' / 20' : '—' }}
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Matière</th><th class="text-center">Coef.</th><th>Notes</th>
                    <th class="text-end">Moyenne / 20</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($moyennes as $ligne)
                    <tr>
                        <td class="fw-semibold">{{ $ligne['matiere']->nom ?? 'Matière supprimée' }}</td>
                        <td class="text-center">{{ $ligne['matiere']->coefficient ?? 1 }}</td>
                        <td>
                            @foreach ($ligne['notes'] as $note)
                                <span class="badge bg-light text-dark border me-1" title="{{ strtoupper($note->type_note) }} — {{ $note->periode->nom ?? '' }}">
                                    {{ rtrim(rtrim(number_format($note->note, 2, ',', ''), '0'), ',') }}/{{ rtrim(rtrim(number_format($note->note_sur, 1, ',', ''), '0'), ',') }}
                                </span>
                            @endforeach
                        </td>
                        <td class="text-end fw-bold {{ $ligne['moyenne'] < 10 ? 'text-danger' : '' }}">
                            {{ number_format($ligne['moyenne'], 2, ',', ' ') }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Aucune note pour cette sélection.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('stagiaires.show', $stagiaire) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour au dossier</a>
</div>
@endsection
