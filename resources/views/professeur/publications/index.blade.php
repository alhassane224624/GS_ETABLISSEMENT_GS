@extends('layouts.app-professeur')

@section('title', 'Publications')
@section('page-title', 'Mes publications')

@section('content')
<div class="container-fluid">
    @if ($enAttente->isNotEmpty())
        <div class="alert alert-warning shadow-sm">
            <div class="fw-semibold mb-2"><i class="fas fa-eye-slash me-2"></i>Notes saisies mais pas encore visibles par les stagiaires</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($enAttente as $groupe)
                    <a href="{{ route('professeur.publications.create', ['type' => 'resultats', 'classe_id' => $groupe->classe_id, 'matiere_id' => $groupe->matiere_id]) }}"
                       class="btn btn-sm btn-light border">
                        {{ $groupe->classe->nom ?? '—' }} · {{ $groupe->matiere->nom ?? '—' }}
                        <span class="badge bg-warning text-dark ms-1">{{ $groupe->total }}</span>
                        <i class="fas fa-paper-plane ms-1 text-success"></i> Publier
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="btn-group">
            <a href="{{ route('professeur.publications.index') }}" class="btn btn-sm {{ request('type') ? 'btn-outline-secondary' : 'btn-secondary' }}">Tout</a>
            @foreach (\App\Models\Publication::TYPES as $valeur => $libelle)
                <a href="{{ route('professeur.publications.index', ['type' => $valeur]) }}" class="btn btn-sm {{ request('type') === $valeur ? 'btn-secondary' : 'btn-outline-secondary' }}">{{ $libelle }}</a>
            @endforeach
        </div>
        <a href="{{ route('professeur.publications.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Nouvelle publication</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Type</th><th>Titre</th><th>Classe</th><th>Matière</th><th>Publié le</th><th>Suivi</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($publications as $p)
                        <tr>
                            <td><span class="badge bg-{{ $p->couleur }} {{ $p->couleur === 'warning' ? 'text-dark' : '' }}"><i class="fas {{ $p->icone }} me-1"></i>{{ $p->type_libelle }}</span></td>
                            <td class="fw-semibold">{{ $p->titre }} @if ($p->fichier_path)<i class="fas fa-paperclip text-muted ms-1" title="Fichier joint"></i>@endif</td>
                            <td>{{ $p->classe->nom ?? '—' }}</td>
                            <td>{{ $p->matiere->nom ?? '—' }}</td>
                            <td class="small">{{ $p->created_at->isoFormat('D MMM YYYY') }}</td>
                            <td class="small">
                                @if ($p->estDevoir())
                                    @if ($p->accepte_rendu)
                                        <span class="badge bg-light text-dark border">{{ $p->rendus_count }} / {{ $effectifs[$p->classe_id] ?? 0 }} rendus</span>
                                    @endif
                                    <div class="{{ $p->echeanceDepassee() ? 'text-danger' : 'text-muted' }}">
                                        Pour le {{ $p->date_limite?->isoFormat('D MMM [à] HH[h]mm') }}
                                    </div>
                                @elseif ($p->type === 'resultats')
                                    {{ $p->notes_publiees }} note(s) publiée(s)
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('professeur.publications.show', $p) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-bullhorn fa-2x mb-2 d-block"></i>
                            Aucune publication pour le moment. Publiez un devoir, un support de cours ou les résultats d'un contrôle.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $publications->links() }}</div>
</div>
@endsection
