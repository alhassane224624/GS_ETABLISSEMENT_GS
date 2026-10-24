@extends($layout)

@section('title', 'Annonces')
@section('page-title', 'Annonces')

@section('content')
<div class="container-fluid px-0">
    @if (auth()->user()->hasFinancialAccess())
        <div class="text-end mb-3">
            <a href="{{ route('gestion.annonces.index') }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-cog me-1"></i>Gérer les annonces</a>
        </div>
    @endif

    @forelse ($annonces as $a)
        @php $nonLue = !$lues->has($a->id); @endphp
        <a href="{{ route('annonces.show', $a) }}" class="card shadow-sm mb-3 text-decoration-none text-body border-start border-4 border-{{ $a->couleur }}">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <span class="badge bg-{{ $a->couleur }} {{ $a->couleur === 'warning' ? 'text-dark' : '' }}"><i class="fas {{ $a->icone }} me-1"></i>{{ $a->importance_libelle }}</span>
                        @if ($a->epinglee)<i class="fas fa-thumbtack text-muted ms-1" title="Épinglée"></i>@endif
                        @if ($nonLue)<span class="badge bg-primary ms-1">Nouveau</span>@endif
                    </div>
                    <small class="text-muted">{{ $a->publie_le->isoFormat('D MMMM YYYY') }}</small>
                </div>
                <div class="mt-2 {{ $nonLue ? 'fw-bold' : 'fw-semibold' }}">
                    {{ $a->titre }} @if ($a->fichier_path)<i class="fas fa-paperclip text-muted ms-1"></i>@endif
                </div>
                <div class="small text-muted mt-1">{{ \Illuminate\Support\Str::limit($a->contenu, 160) }}</div>
            </div>
        </a>
    @empty
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="fas fa-bullhorn fa-3x mb-3 d-block"></i>
                Aucune annonce pour le moment.
            </div>
        </div>
    @endforelse
</div>
@endsection
