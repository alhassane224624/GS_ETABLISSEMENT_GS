@extends($layout)

@section('title', 'Annonces')
@section('page-title', 'Annonces et notes circulaires')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="btn-group">
        <a href="{{ route('gestion.annonces.index') }}" class="btn btn-sm {{ request('etat') ? 'btn-outline-secondary' : 'btn-secondary' }}">En cours</a>
        <a href="{{ route('gestion.annonces.index', ['etat' => 'programmees']) }}" class="btn btn-sm {{ request('etat') === 'programmees' ? 'btn-secondary' : 'btn-outline-secondary' }}">Programmées</a>
        <a href="{{ route('gestion.annonces.index', ['etat' => 'expirees']) }}" class="btn btn-sm {{ request('etat') === 'expirees' ? 'btn-secondary' : 'btn-outline-secondary' }}">Expirées</a>
    </div>
    <a href="{{ route('gestion.annonces.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Nouvelle annonce</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>Annonce</th><th>Destinataires</th><th>Publication</th><th class="text-center">Lectures</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($annonces as $a)
                    <tr>
                        <td>
                            <span class="badge bg-{{ $a->couleur }} {{ $a->couleur === 'warning' ? 'text-dark' : '' }}"><i class="fas {{ $a->icone }} me-1"></i>{{ $a->importance_libelle }}</span>
                            @if ($a->epinglee)<i class="fas fa-thumbtack text-muted ms-1" title="Épinglée"></i>@endif
                            @if ($a->fichier_path)<i class="fas fa-paperclip text-muted ms-1" title="Pièce jointe"></i>@endif
                            <div class="fw-semibold">{{ $a->titre }}</div>
                            <div class="small text-muted">par {{ $a->auteur->name ?? '—' }}</div>
                        </td>
                        <td class="small">{{ $a->destinataires_libelle }}</td>
                        <td class="small">
                            {{ $a->publie_le->isoFormat('D MMM YYYY [à] HH[h]mm') }}
                            @if ($a->publie_le->isFuture())<div><span class="badge bg-secondary">programmée</span></div>@endif
                            @if ($a->expire_le)<div class="text-muted">jusqu'au {{ $a->expire_le->isoFormat('D MMM YYYY') }}</div>@endif
                        </td>
                        <td class="text-center"><span class="badge bg-light text-dark border">{{ $a->lectures_count }}</span></td>
                        <td class="text-end"><a href="{{ route('gestion.annonces.show', $a) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">
                        <i class="fas fa-bullhorn fa-2x mb-2 d-block"></i>Aucune annonce {{ request('etat') === 'programmees' ? 'programmée' : (request('etat') === 'expirees' ? 'expirée' : 'en cours') }}.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $annonces->links() }}</div>
@endsection
