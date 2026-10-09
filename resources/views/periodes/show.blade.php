@extends('layouts.app')

@section('title', 'Période ' . $periode->nom)
@section('page-title', 'Détail de la période')

@section('content')
<div class="card shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h5 class="mb-1">
                {{ $periode->nom }}
                @if ($periode->is_active)<span class="badge bg-success ms-2">Active</span>@endif
            </h5>
            <small class="text-muted">
                {{ $periode->type_libelle ?? ucfirst($periode->type) }}
                · {{ \Carbon\Carbon::parse($periode->debut)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($periode->fin)->format('d/m/Y') }}
                · Année {{ $periode->anneeScolaire->nom ?? '—' }}
            </small>
        </div>
        <div class="d-flex gap-2">
            @unless ($periode->is_active)
                <form method="POST" action="{{ route('periodes.activer', $periode) }}">@csrf
                    <button class="btn btn-success"><i class="fas fa-power-off me-1"></i>Activer</button>
                </form>
            @endunless
            <a href="{{ route('periodes.edit', $periode) }}" class="btn btn-outline-primary"><i class="fas fa-edit me-1"></i>Modifier</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach ([
        ['Notes saisies', $periode->notes_count, 'fa-pen', 'primary'],
        ['Bulletins générés', $periode->bulletins_count, 'fa-file-alt', 'info'],
        ['Bulletins validés', $stats['bulletins_valides'], 'fa-check-circle', 'success'],
        ['Absences', $stats['absences'], 'fa-user-clock', 'warning'],
    ] as [$label, $valeur, $icone, $couleur])
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small"><i class="fas {{ $icone }} text-{{ $couleur }} me-1"></i>{{ $label }}</div>
                    <div class="fs-3 fw-bold">{{ $valeur }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if ($stats['moyenne'] !== null)
    <div class="alert alert-light border">
        Moyenne générale des bulletins de la période :
        <strong>{{ number_format($stats['moyenne'], 2, ',', ' ') }} / 20</strong>
    </div>
@endif

<div class="d-flex gap-2">
    <a href="{{ route('periodes.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
    <a href="{{ route('bulletins.index', ['periode_id' => $periode->id]) }}" class="btn btn-primary"><i class="fas fa-file-alt me-1"></i>Voir les bulletins</a>
</div>
@endsection
