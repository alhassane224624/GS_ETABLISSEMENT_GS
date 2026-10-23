@extends('layouts.app-stagiaire')

@section('title', 'Devoirs et documents')
@section('page-title', 'Devoirs et documents')

@section('content')
<div class="container-fluid">
    @if ($aRendre->isNotEmpty())
        <div class="card border-0 shadow-sm mb-4 border-start border-4 border-warning">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="fas fa-hourglass-half text-warning me-2"></i>À rendre en ligne</h6>
                @foreach ($aRendre as $d)
                    <a href="{{ route('stagiaire.publications.show', $d) }}" class="d-flex justify-content-between align-items-center text-decoration-none text-body py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span><strong>{{ $d->matiere->nom ?? '—' }}</strong> · {{ $d->titre }}</span>
                        <span class="badge {{ $d->echeanceDepassee() ? 'bg-danger' : ($d->date_limite->diffInHours(now()) < 48 ? 'bg-warning text-dark' : 'bg-primary') }}">
                            {{ $d->echeanceDepassee() ? 'En retard' : 'Pour le ' . $d->date_limite->isoFormat('D MMM [à] HH[h]mm') }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="btn-group mb-3">
        <a href="{{ route('stagiaire.publications.index') }}" class="btn btn-sm {{ request('type') ? 'btn-outline-primary' : 'btn-primary' }}">Tout</a>
        @foreach (\App\Models\Publication::TYPES as $valeur => $libelle)
            <a href="{{ route('stagiaire.publications.index', ['type' => $valeur]) }}" class="btn btn-sm {{ request('type') === $valeur ? 'btn-primary' : 'btn-outline-primary' }}">{{ $libelle }}</a>
        @endforeach
    </div>

    @forelse ($publications as $p)
        @php $r = $mesRendus->get($p->id); @endphp
        <a href="{{ $p->type === 'resultats' ? route('stagiaire.notes', ['matiere_id' => $p->matiere_id]) : route('stagiaire.publications.show', $p) }}"
           class="card border-0 shadow-sm mb-3 text-decoration-none text-body">
            <div class="card-body d-flex gap-3 align-items-start">
                <div class="rounded-circle bg-{{ $p->couleur }} bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                    <i class="fas {{ $p->icone }} text-{{ $p->couleur }}"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div class="fw-semibold">{{ $p->titre }} @if ($p->fichier_path)<i class="fas fa-paperclip text-muted ms-1"></i>@endif</div>
                        <small class="text-muted">{{ $p->created_at->isoFormat('D MMM') }}</small>
                    </div>
                    <div class="small text-muted">{{ $p->type_libelle }} · {{ $p->matiere->nom ?? '—' }} · {{ $p->professeur->name ?? '' }}</div>
                    @if ($p->estDevoir())
                        <div class="small mt-1">
                            <i class="fas fa-clock me-1"></i>Pour le {{ $p->date_limite?->isoFormat('dddd D MMMM [à] HH[h]mm') }}
                            @if ($p->accepte_rendu)
                                @if ($r)
                                    <span class="badge bg-success ms-1">Rendu</span>
                                @elseif ($p->echeanceDepassee())
                                    <span class="badge bg-danger ms-1">Non rendu</span>
                                @else
                                    <span class="badge bg-warning text-dark ms-1">À rendre</span>
                                @endif
                            @endif
                        </div>
                    @elseif ($p->type === 'resultats')
                        <div class="small mt-1 text-success"><i class="fas fa-arrow-right me-1"></i>Voir mes notes</div>
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="fas fa-bullhorn fa-3x mb-3 d-block"></i>
                Aucune publication de vos professeurs pour le moment.
            </div>
        </div>
    @endforelse

    {{ $publications->links() }}
</div>
@endsection
