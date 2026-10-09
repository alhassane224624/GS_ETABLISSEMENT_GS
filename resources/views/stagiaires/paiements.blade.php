@extends('layouts.app-stagiaire')

@section('title', 'Mes paiements')

@section('content')
@php $dh = fn ($m) => number_format((float) $m, 2, ',', ' ') . ' DH'; @endphp

<div class="container py-4">
    <h4 class="mb-4"><i class="fas fa-wallet text-primary me-2"></i>Mes paiements</h4>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">Total à payer</small><div class="fs-4 fw-bold">{{ $dh($stats['total_a_payer']) }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">Déjà réglé</small><div class="fs-4 fw-bold text-success">{{ $dh($stats['total_paye']) }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">Reste à payer</small><div class="fs-4 fw-bold {{ $stats['solde_restant'] > 0 ? 'text-danger' : 'text-success' }}">{{ $dh($stats['solde_restant']) }}</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">En cours d'encaissement</small><div class="fs-4 fw-bold text-warning">{{ $dh($stats['en_attente']) }}</div></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Historique</span>
            <a href="{{ route('stagiaire.echeanciers') }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-calendar-alt me-1"></i>Mes échéances</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr><th>N°</th><th>Date</th><th>Mode</th><th>Échéances réglées</th><th class="text-end">Montant</th><th>Statut</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($paiements as $paiement)
                        <tr>
                            <td class="small">{{ $paiement->numero_transaction }}</td>
                            <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                            <td>{{ $paiement->methode_libelle }}</td>
                            <td class="small">{{ $paiement->echeanciers->pluck('titre')->implode(', ') ?: '—' }}</td>
                            <td class="text-end fw-semibold">{{ $dh($paiement->montant) }}</td>
                            <td>
                                <span class="badge bg-{{ $paiement->statut_couleur }}">{{ $paiement->statut_libelle }}</span>
                                @if ($paiement->statut === 'refuse' && $paiement->notes_admin)
                                    <div class="small text-danger">{{ $paiement->notes_admin }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($paiement->statut === 'valide')
                                    <a href="{{ route('stagiaire.paiement.recu', $paiement) }}" class="btn btn-sm btn-outline-success" target="_blank">
                                        <i class="fas fa-file-pdf me-1"></i>Reçu
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Aucun paiement pour le moment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($paiements->hasPages())
            <div class="card-footer bg-white">{{ $paiements->links() }}</div>
        @endif
    </div>
</div>
@endsection
