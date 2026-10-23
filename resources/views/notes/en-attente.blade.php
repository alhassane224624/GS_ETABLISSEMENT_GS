@extends('layouts.app')

@section('title', 'Notes en attente de publication')
@section('page-title', 'Notes en attente de publication')

@section('content')
<div class="alert alert-light border small">
    Les notes saisies par les professeurs restent masquées (stagiaires, parents, administration, bulletins) jusqu'à ce qu'ils publient les résultats.
    Cette page indique seulement <strong>combien</strong> de notes attendent, pour vérifier avant de générer les bulletins.
</div>

@if ($total === 0)
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
            <h5>Aucune note en attente</h5>
            <p class="text-muted mb-0">Tous les professeurs ont publié leurs résultats : les bulletins peuvent être générés.</p>
        </div>
    </div>
@else
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i><strong>{{ $total }} note(s)</strong> ne sont pas encore publiées et ne comptent donc pas dans les bulletins.</div>

    @foreach ($parProf as $groupes)
        @php $prof = $groupes->first()->creator; @endphp
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <strong>{{ $prof->name ?? 'Professeur supprimé' }}</strong>
                    <span class="text-muted small">{{ $prof->email ?? '' }}</span>
                    <span class="badge bg-warning text-dark ms-1">{{ $groupes->sum('total') }} note(s)</span>
                </div>
                @if ($prof)
                    <form method="POST" action="{{ route('notes.en-attente.relancer', $prof) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-primary"><i class="fas fa-bell me-1"></i>Relancer</button>
                    </form>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Classe</th><th>Matière</th><th class="text-center">Notes en attente</th><th>Saisie la plus ancienne</th></tr></thead>
                    <tbody>
                        @foreach ($groupes as $g)
                            @php $depuis = \Carbon\Carbon::parse($g->plus_ancienne); @endphp
                            <tr>
                                <td>{{ $g->classe->nom ?? '—' }}</td>
                                <td>{{ $g->matiere->nom ?? '—' }}</td>
                                <td class="text-center fw-semibold">{{ $g->total }}</td>
                                <td class="{{ $depuis->lt(now()->subDays(14)) ? 'text-danger fw-semibold' : '' }}">
                                    {{ $depuis->format('d/m/Y') }} <span class="small text-muted">({{ $depuis->diffForHumans() }})</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endif
@endsection
