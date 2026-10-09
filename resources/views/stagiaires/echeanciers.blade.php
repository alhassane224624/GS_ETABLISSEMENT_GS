@extends('layouts.app-stagiaire')

@section('title', 'Mes échéances')

@section('content')
@php $dh = fn ($m) => number_format((float) $m, 2, ',', ' ') . ' DH'; @endphp

<div class="container py-4">
    <h4 class="mb-4"><i class="fas fa-calendar-alt text-primary me-2"></i>Mes échéances</h4>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">Total dû</small><div class="fs-4 fw-bold">{{ $dh($stats['total_du']) }}</div>@if ($stats['remises'] > 0)<small class="text-success">dont {{ $dh($stats['remises']) }} de remise déduite</small>@endif</div></div></div>
        <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">Réglé</small><div class="fs-4 fw-bold text-success">{{ $dh($stats['total_paye']) }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">Reste à payer</small><div class="fs-4 fw-bold {{ $stats['restant'] > 0 ? 'text-danger' : 'text-success' }}">{{ $dh($stats['restant']) }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">Échéances en retard</small><div class="fs-4 fw-bold {{ $stats['retards'] ? 'text-danger' : '' }}">{{ $stats['retards'] }}</div></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Échéance</th><th>Date limite</th><th class="text-end">Montant</th><th class="text-end">Remise</th><th class="text-end">Réglé</th><th class="text-end">Reste</th><th>Statut</th></tr>
                </thead>
                <tbody>
                    @forelse ($echeanciers as $e)
                        <tr class="{{ $e->statut === 'en_retard' ? 'table-danger' : '' }}">
                            <td><strong>{{ $e->titre }}</strong><div class="small text-muted">{{ $e->type_libelle }} · {{ $e->anneeScolaire->nom ?? '' }}</div></td>
                            <td>{{ $e->date_echeance->format('d/m/Y') }}</td>
                            <td class="text-end">{{ $dh($e->montant) }}</td>
                            <td class="text-end text-success">{{ $e->montant_remise > 0 ? '− ' . $dh($e->montant_remise) : '—' }}</td>
                            <td class="text-end">{{ $dh($e->montant_paye) }}</td>
                            <td class="text-end fw-semibold">{{ $dh($e->montant_restant) }}</td>
                            <td><span class="badge bg-{{ $e->statut_couleur }}">{{ $e->statut_libelle }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Aucune échéance.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="small text-muted mt-3">
        <i class="fas fa-info-circle me-1"></i>Un paiement par chèque ou virement apparaît comme réglé une fois encaissé par l'établissement.
    </p>
</div>
@endsection
