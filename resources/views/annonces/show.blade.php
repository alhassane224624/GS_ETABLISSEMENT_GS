@extends($layout)

@section('title', $annonce->titre)
@section('page-title', 'Annonce')

@section('content')
<div class="container-fluid px-0">
    <a href="{{ route('annonces.index') }}" class="btn btn-sm btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-1"></i>Toutes les annonces</a>

    <div class="card shadow-sm border-start border-4 border-{{ $annonce->couleur }}">
        <div class="card-body p-4">
            <span class="badge bg-{{ $annonce->couleur }} {{ $annonce->couleur === 'warning' ? 'text-dark' : '' }} mb-2"><i class="fas {{ $annonce->icone }} me-1"></i>{{ $annonce->importance_libelle }}</span>
            <h3 class="fw-bold">{{ $annonce->titre }}</h3>
            <div class="small text-muted mb-4">
                {{ $annonce->auteur->name ?? 'L\'administration' }}
                · {{ $annonce->auteur?->role === 'comptable' ? 'Comptabilité' : 'Administration' }}
                · {{ $annonce->publie_le->isoFormat('dddd D MMMM YYYY') }}
            </div>

            <div style="white-space: pre-line; font-size: 1.05rem;">{{ $annonce->contenu }}</div>

            @if ($annonce->fichier_path)
                <a href="{{ route('annonces.fichier', $annonce) }}" class="btn btn-primary mt-4"><i class="fas fa-download me-1"></i>Télécharger {{ $annonce->fichier_nom }}</a>
            @endif
        </div>
    </div>
</div>
@endsection
