@extends($layout)

@section('title', $annonce->titre)
@section('page-title', 'Annonce')

@section('content')
@php $nbDest = $destinataires->count(); $nbLus = $nbDest - $nonLecteurs->count(); @endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a href="{{ route('gestion.annonces.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Annonces</a>
    @if ($annonce->modifiablePar(auth()->user()))
        <div class="d-flex gap-2">
            <a href="{{ route('gestion.annonces.edit', $annonce) }}" class="btn btn-sm btn-primary"><i class="fas fa-edit me-1"></i>Modifier</a>
            <form method="POST" action="{{ route('gestion.annonces.destroy', $annonce) }}" onsubmit="return confirm('Supprimer définitivement cette annonce ?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i>Supprimer</button>
            </form>
        </div>
    @endif
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <span class="badge bg-{{ $annonce->couleur }} {{ $annonce->couleur === 'warning' ? 'text-dark' : '' }} mb-2"><i class="fas {{ $annonce->icone }} me-1"></i>{{ $annonce->importance_libelle }}</span>
                @if ($annonce->epinglee)<span class="badge bg-light text-dark border mb-2"><i class="fas fa-thumbtack me-1"></i>Épinglée</span>@endif
                <h4 class="fw-bold">{{ $annonce->titre }}</h4>
                <div class="small text-muted mb-3">
                    Par {{ $annonce->auteur->name ?? '—' }} ·
                    @if ($annonce->publie_le->isFuture())
                        <span class="badge bg-secondary">programmée pour le {{ $annonce->publie_le->isoFormat('D MMMM [à] HH[h]mm') }}</span>
                    @else
                        publiée le {{ $annonce->publie_le->isoFormat('D MMMM YYYY [à] HH[h]mm') }}
                    @endif
                    @if ($annonce->expire_le) · retirée le {{ $annonce->expire_le->isoFormat('D MMMM YYYY') }} @endif
                </div>
                <div style="white-space: pre-line;">{{ $annonce->contenu }}</div>
                @if ($annonce->fichier_path)
                    <a href="{{ route('annonces.fichier', $annonce) }}" class="btn btn-outline-primary btn-sm mt-3"><i class="fas fa-download me-1"></i>{{ $annonce->fichier_nom }}</a>
                @endif
                <hr>
                <div class="small"><strong>Destinataires :</strong> {{ $annonce->destinataires_libelle }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="fas fa-check-double me-2 text-primary"></i>Lectures</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-1">
                    <span>{{ $nbLus }} / {{ $nbDest }} personne(s)</span>
                    <strong>{{ $nbDest ? round($nbLus * 100 / $nbDest) : 0 }} %</strong>
                </div>
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar bg-success" style="width: {{ $nbDest ? round($nbLus * 100 / $nbDest) : 0 }}%"></div>
                </div>

                @if ($nonLecteurs->isNotEmpty())
                    <div class="fw-semibold small mb-2">Pas encore lue par ({{ $nonLecteurs->count() }}) :</div>
                    <div style="max-height: 320px; overflow-y: auto;">
                        <ul class="list-group list-group-flush small">
                            @foreach ($nonLecteurs as $u)
                                <li class="list-group-item px-0 py-1 d-flex justify-content-between">
                                    <span>{{ $u->name }}</span>
                                    <span class="text-muted">{{ \App\Models\Annonce::ROLES[$u->role] ?? $u->role }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @elseif ($nbDest)
                    <div class="text-success small"><i class="fas fa-check-circle me-1"></i>Tous les destinataires l'ont lue.</div>
                @else
                    <div class="text-muted small">Aucun destinataire ne correspond à ces critères.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
