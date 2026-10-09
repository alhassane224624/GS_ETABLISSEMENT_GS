@extends('layouts.app-professeur')

@section('title', 'Mes épreuves')

@section('content')
<div class="container-fluid py-3">
    <h4 class="mb-3"><i class="fas fa-file-alt text-primary me-2"></i>Mes épreuves d'examen</h4>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Date</th><th>Session</th><th>Classe</th><th>Matière</th><th></th></tr></thead>
                <tbody>
                    @forelse ($epreuves as $ep)
                        <tr>
                            <td>{{ $ep->date->format('d/m/Y') }} <span class="small text-muted">{{ $ep->horaire }}</span></td>
                            <td>{{ $ep->examen->nom }} <span class="badge bg-{{ $ep->examen->estRattrapage() ? 'warning text-dark' : 'primary' }}">{{ $ep->examen->estRattrapage() ? 'Rattrapage' : 'Normale' }}</span></td>
                            <td>{{ $ep->classe->nom ?? '' }}</td>
                            <td>{{ $ep->matiere->nom ?? '' }}</td>
                            <td class="text-end">
                                @if (auth()->user()->canTeachMatiere($ep->matiere_id))
                                    <a href="{{ route('professeur.epreuves.saisie', $ep) }}" class="btn btn-sm btn-primary"><i class="fas fa-pen me-1"></i>Saisir les notes</a>
                                @else
                                    <span class="small text-muted">Surveillance</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucune épreuve en cours.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
