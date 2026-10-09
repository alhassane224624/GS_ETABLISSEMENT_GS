@extends('layouts.app')

@section('title', 'Documents délivrés')
@section('page-title', 'Registre des documents')

@section('content')
<form method="GET" class="card shadow-sm mb-3">
    <div class="card-body row g-2">
        <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="N°, code, nom ou matricule"></div>
        <div class="col-md-4">
            <select name="type" class="form-select">
                <option value="">Tous les documents</option>
                @foreach (\App\Models\DocumentDelivre::TYPES as $k => $v)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Filtrer</button></div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>N°</th><th>Document</th><th>Stagiaire</th><th>Année</th><th>Délivré le</th><th>Par</th><th>État</th><th class="text-end"></th></tr>
            </thead>
            <tbody>
                @forelse ($documents as $d)
                    <tr>
                        <td class="fw-semibold">{{ $d->numero }}<div class="small text-muted">code {{ $d->code_verification }}</div></td>
                        <td>{{ $d->type_libelle }}</td>
                        <td>@if ($d->stagiaire)<a href="{{ route('stagiaires.show', $d->stagiaire) }}">{{ $d->contenu['nom'] ?? '' }} {{ $d->contenu['prenom'] ?? '' }}</a>@endif<div class="small text-muted">{{ $d->contenu['matricule'] ?? '' }}</div></td>
                        <td>{{ $d->contenu['annee'] ?? '—' }}</td>
                        <td>{{ $d->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $d->auteur->name ?? 'Stagiaire (en ligne)' }}</td>
                        <td>@if ($d->estValide())<span class="badge bg-success">Valide</span>@else<span class="badge bg-danger">Annulé</span>@endif</td>
                        <td class="text-end text-nowrap">
                            @if ($d->estValide())
                                <a href="{{ route('documents.telecharger', $d) }}" class="btn btn-sm btn-outline-primary" title="Réimprimer"><i class="fas fa-print"></i></a>
                                <form method="POST" action="{{ route('documents.annuler', $d) }}" class="d-inline" onsubmit="return confirm('Annuler ce document ? Sa vérification indiquera qu\'il n\'est plus valide.')">
                                    @csrf <button class="btn btn-sm btn-outline-danger" title="Annuler"><i class="fas fa-ban"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucun document délivré. Les documents se délivrent depuis la fiche d'un stagiaire.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($documents->hasPages())<div class="card-footer bg-white">{{ $documents->links() }}</div>@endif
</div>
@endsection
