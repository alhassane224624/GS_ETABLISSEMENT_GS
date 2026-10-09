@extends('layouts.app-parent')
@section('title', $stagiaire->prenom . ' ' . $stagiaire->nom)

@section('content')
@php $dh = fn ($m) => number_format((float) $m, 2, ',', ' ') . ' DH'; @endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0">{{ $stagiaire->prenom }} {{ $stagiaire->nom }}</h4>
        <div class="text-muted">{{ $stagiaire->matricule }} · {{ $stagiaire->filiere->nom ?? '' }} · {{ $stagiaire->classe->nom ?? 'sans classe' }}</div>
    </div>
    @if ($enfants->count() > 1)
        <div class="btn-group">@foreach ($enfants as $e)<a href="{{ route('parent.enfant', $e->id) }}" class="btn btn-sm {{ $e->id === $stagiaire->id ? 'btn-primary' : 'btn-outline-primary' }}">{{ $e->prenom }}</a>@endforeach</div>
    @endif
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#t-resultats">Résultats</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-absences">Absences</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-devoirs">Travail à faire</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-paiements">Paiements</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="t-resultats">
        <div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Période</th><th class="text-center">Moyenne</th><th class="text-center">Rang</th><th>Appréciation</th><th></th></tr></thead>
            <tbody>
            @forelse ($bulletins as $b)
                <tr>
                    <td>{{ $b->periode->nom ?? '' }}</td>
                    <td class="text-center fw-bold {{ $b->moyenne_generale < 10 ? 'text-danger' : 'text-success' }}">{{ number_format($b->moyenne_generale, 2, ',', '') }}</td>
                    <td class="text-center">{{ $b->rang }} / {{ $b->total_classe }}</td>
                    <td class="small">{{ $b->appreciation_generale }}</td>
                    <td class="text-end"><a href="{{ route('parent.bulletin', [$stagiaire, $b]) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-file-pdf me-1"></i>Bulletin</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Aucun bulletin publié pour le moment.</td></tr>
            @endforelse
            </tbody>
        </table></div></div>
    </div>

    <div class="tab-pane fade" id="t-absences">
        <div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Cours</th><th>Statut</th></tr></thead>
            <tbody>
            @forelse ($absences as $a)
                <tr>
                    <td>{{ $a->date->format('d/m/Y') }}</td>
                    <td>{{ $a->type_libelle }}</td>
                    <td>{{ $a->planning->matiere->nom ?? '—' }}</td>
                    <td>
                        @if ($a->justifiee)<span class="badge bg-success">Justifiée</span>
                        @elseif ($a->justification_statut === 'en_attente')<span class="badge bg-warning text-dark">Justificatif en examen</span>
                        @else<span class="badge bg-danger">Non justifiée</span>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Aucune absence.</td></tr>
            @endforelse
            </tbody>
        </table></div></div>
        <small class="text-muted">Votre enfant peut déposer un justificatif depuis son propre espace.</small>
    </div>

    <div class="tab-pane fade" id="t-devoirs">
        <div class="list-group shadow-sm">
            @forelse ($aFaire as $s)
                <div class="list-group-item"><div class="d-flex justify-content-between"><strong>{{ $s->matiere->nom ?? '' }}</strong><span class="badge bg-warning text-dark">pour le {{ $s->devoirs_pour->format('d/m') }}</span></div><div class="small" style="white-space: pre-line;">{{ $s->devoirs }}</div></div>
            @empty
                <div class="list-group-item text-muted">Aucun travail à rendre.</div>
            @endforelse
        </div>
    </div>

    <div class="tab-pane fade" id="t-paiements">
        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><small class="text-muted">Total dû</small><div class="fs-5 fw-bold">{{ $dh($echeances->sum(fn ($e) => $e->montant_net)) }}</div></div></div></div>
            <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><small class="text-muted">Réglé</small><div class="fs-5 fw-bold text-success">{{ $dh($echeances->sum('montant_paye')) }}</div></div></div></div>
            <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><small class="text-muted">Reste à payer</small><div class="fs-5 fw-bold text-danger">{{ $dh($echeances->sum('montant_restant')) }}</div></div></div></div>
        </div>
        <div class="card shadow-sm mb-3"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>Échéance</th><th>Date limite</th><th class="text-end">Reste</th><th>Statut</th></tr></thead>
            <tbody>
            @forelse ($echeances as $e)
                <tr class="{{ $e->statut === 'en_retard' ? 'table-danger' : '' }}"><td>{{ $e->titre }}</td><td>{{ $e->date_echeance->format('d/m/Y') }}</td><td class="text-end">{{ $dh($e->montant_restant) }}</td><td><span class="badge bg-{{ $e->statut_couleur }}">{{ $e->statut_libelle }}</span></td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-3">Aucune échéance.</td></tr>
            @endforelse
            </tbody>
        </table></div></div>
        <div class="card shadow-sm"><div class="card-header bg-white fw-semibold">Reçus</div><ul class="list-group list-group-flush">
            @forelse ($paiements as $p)
                <li class="list-group-item d-flex justify-content-between align-items-center"><span>{{ $p->date_paiement->format('d/m/Y') }} — {{ $dh($p->montant) }} <span class="small text-muted">{{ $p->methode_libelle }}</span></span>
                    <a href="{{ route('parent.recu', [$stagiaire, $p]) }}" class="btn btn-sm btn-outline-success"><i class="fas fa-file-pdf me-1"></i>Reçu</a></li>
            @empty
                <li class="list-group-item text-muted">Aucun paiement.</li>
            @endforelse
        </ul></div>
    </div>
</div>
@endsection
