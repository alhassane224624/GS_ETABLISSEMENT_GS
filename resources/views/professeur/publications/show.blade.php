@extends('layouts.app-professeur')

@section('title', $publication->titre)
@section('page-title', $publication->type_libelle . ' — ' . $publication->classe->nom)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('professeur.publications.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Mes publications</a>
        <form method="POST" action="{{ route('professeur.publications.destroy', $publication) }}"
              onsubmit="return confirm('{{ $publication->type === 'resultats' ? 'Supprimer cette annonce ? Les notes déjà publiées resteront visibles.' : 'Supprimer cette publication' . ($publication->estDevoir() ? ' et les copies déposées' : '') . ' ?' }}')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash me-1"></i>Supprimer</button>
        </form>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                <h4 class="fw-bold mb-0">
                    <span class="badge bg-{{ $publication->couleur }} {{ $publication->couleur === 'warning' ? 'text-dark' : '' }} fs-6 align-middle"><i class="fas {{ $publication->icone }} me-1"></i>{{ $publication->type_libelle }}</span>
                    {{ $publication->titre }}
                </h4>
                <span class="text-muted small">Publié le {{ $publication->created_at->isoFormat('D MMMM YYYY [à] HH[h]mm') }}</span>
            </div>
            <div class="text-muted mb-3">{{ $publication->classe->nom }} · {{ $publication->matiere->nom }}</div>

            @if ($publication->contenu)
                <div class="mb-3" style="white-space: pre-line;">{{ $publication->contenu }}</div>
            @endif

            @if ($publication->fichier_path)
                <a href="{{ route('publications.fichier', $publication) }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-download me-1"></i>{{ $publication->fichier_nom }}
                </a>
            @endif

            @if ($publication->estDevoir())
                <div class="mt-3 {{ $publication->echeanceDepassee() ? 'text-danger' : '' }}">
                    <i class="fas fa-clock me-1"></i>À rendre pour le <strong>{{ $publication->date_limite->isoFormat('dddd D MMMM [à] HH[h]mm') }}</strong>
                    @if ($publication->echeanceDepassee()) (échéance dépassée) @endif
                </div>
            @elseif ($publication->type === 'resultats')
                <div class="mt-3 text-success"><i class="fas fa-check-circle me-1"></i>{{ $publication->notes_publiees }} note(s) rendue(s) visible(s) aux stagiaires.</div>
            @endif
        </div>
    </div>

    @if ($publication->estDevoir() && $publication->accepte_rendu)
        @php $nbRendus = $rendus->count(); @endphp
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="fas fa-inbox me-2 text-primary"></i>Copies déposées</span>
                <span class="badge bg-{{ $nbRendus === $stagiaires->count() ? 'success' : 'secondary' }}">{{ $nbRendus }} / {{ $stagiaires->count() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Stagiaire</th><th>État</th><th>Déposé le</th><th>Commentaire</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($stagiaires as $s)
                            @php $r = $rendus->get($s->id); @endphp
                            <tr>
                                <td>{{ $s->nom }} {{ $s->prenom }} <span class="text-muted small">{{ $s->matricule }}</span></td>
                                <td>
                                    @if (!$r)
                                        <span class="badge {{ $publication->echeanceDepassee() ? 'bg-danger' : 'bg-light text-dark border' }}">{{ $publication->echeanceDepassee() ? 'Non rendu' : 'En attente' }}</span>
                                    @elseif ($r->rendu_at->gt($publication->date_limite))
                                        <span class="badge bg-warning text-dark">Rendu en retard</span>
                                    @else
                                        <span class="badge bg-success">Rendu</span>
                                    @endif
                                </td>
                                <td class="small">{{ $r?->rendu_at->isoFormat('D MMM [à] HH[h]mm') ?? '—' }}</td>
                                <td class="small text-muted">{{ $r?->commentaire ? \Illuminate\Support\Str::limit($r->commentaire, 80) : '' }}</td>
                                <td class="text-end">
                                    @if ($r)
                                        <a href="{{ route('rendus.fichier', $r) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-download me-1"></i>Copie</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
