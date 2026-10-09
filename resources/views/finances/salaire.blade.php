@extends($layout)
@section('title', 'Salaire')
@section('page-title', 'Salaire — ' . ($salaire->professeur->name ?? ''))

@section('content')
@php $dh = fn ($m) => number_format((float) $m, 2, ',', ' ') . ' DH'; @endphp
<div class="container-fluid py-2">
<a href="{{ route('finances.salaires', ['mois' => $salaire->mois->format('Y-m')]) }}" class="btn btn-sm btn-outline-secondary mb-3">← {{ $salaire->mois_libelle }}</a>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Séances réalisées — {{ $salaire->mois_libelle }} ({{ $seances->count() }})</div>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>Date</th><th>Horaire</th><th>Classe</th><th>Matière</th><th class="text-end">Durée</th></tr></thead>
                <tbody>
                @forelse ($seances as $se)
                    <tr><td>{{ $se->date->format('d/m') }}</td><td>{{ substr($se->heure_debut, 0, 5) }}–{{ substr($se->heure_fin, 0, 5) }}</td><td>{{ $se->classe->nom ?? '' }}</td><td>{{ $se->matiere->nom ?? '' }} <span class="small text-muted">{{ $se->type_cours }}</span></td><td class="text-end">{{ \App\Services\FinanceService::duree($se) }} h</td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">Aucune séance réalisée (appel fait ou séance terminée).</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm mb-3"><div class="card-body">
            <table class="table table-sm mb-0">
                <tr><td class="text-muted">Mode</td><td class="text-end">{{ $salaire->mode_remuneration === 'fixe' ? 'Salaire fixe' : 'Taux horaire ' . $dh($salaire->taux_horaire) }}</td></tr>
                <tr><td class="text-muted">Heures</td><td class="text-end">{{ $salaire->heures }} h</td></tr>
                <tr><td class="text-muted">Base</td><td class="text-end">{{ $dh($salaire->montant_base) }}</td></tr>
                <tr><td class="text-muted">Primes</td><td class="text-end text-success">+ {{ $dh($salaire->primes) }}</td></tr>
                <tr><td class="text-muted">Retenues</td><td class="text-end text-danger">− {{ $dh($salaire->retenues) }}</td></tr>
                <tr class="fw-bold"><td>Net à payer</td><td class="text-end">{{ $dh($salaire->montant_net) }}</td></tr>
            </table>
            @if ($salaire->observation)<div class="small text-muted mt-2">{{ $salaire->observation }}</div>@endif
        </div></div>

        @if ($salaire->estPaye())
            <div class="alert alert-success">Payé le {{ $salaire->date_paiement->format('d/m/Y') }} ({{ \App\Models\Depense::MODES[$salaire->mode_paiement] ?? $salaire->mode_paiement }}).</div>
            @if (auth()->user()->isAdmin())
                <form method="POST" action="{{ route('finances.salaires.annuler', $salaire) }}" onsubmit="return confirm('Annuler ce paiement ? La dépense associée sera supprimée.')">@csrf
                    <button class="btn btn-sm btn-outline-danger">Annuler le paiement</button></form>
            @endif
        @else
            <form method="POST" action="{{ route('finances.salaires.ajuster', $salaire) }}" class="card shadow-sm mb-3"><div class="card-body row g-2">
                @csrf
                <div class="col-6"><label class="form-label small">Primes</label><input type="number" step="0.01" min="0" name="primes" value="{{ $salaire->primes }}" class="form-control"></div>
                <div class="col-6"><label class="form-label small">Retenues</label><input type="number" step="0.01" min="0" name="retenues" value="{{ $salaire->retenues }}" class="form-control"></div>
                <div class="col-12"><input name="observation" value="{{ $salaire->observation }}" class="form-control" placeholder="Observation (motif de la prime / retenue)"></div>
                <div class="col-12"><button class="btn btn-outline-primary w-100">Ajuster</button></div>
            </div></form>
            <form method="POST" action="{{ route('finances.salaires.payer', $salaire) }}" class="card shadow-sm border-success" onsubmit="return confirm('Confirmer le paiement de {{ $dh($salaire->montant_net) }} ?')"><div class="card-body row g-2">
                @csrf
                <div class="col-6"><label class="form-label small">Mode</label><select name="mode_paiement" class="form-select">@foreach (\App\Models\Depense::MODES as $k => $v)<option value="{{ $k }}" @selected($k === 'virement')>{{ $v }}</option>@endforeach</select></div>
                <div class="col-6"><label class="form-label small">Date</label><input type="date" name="date_paiement" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" class="form-control"></div>
                <div class="col-12"><input name="reference" class="form-control" placeholder="Référence (n° virement / chèque)"></div>
                <div class="col-12"><button class="btn btn-success w-100"><i class="fas fa-check me-1"></i>Payer {{ $dh($salaire->montant_net) }}</button></div>
            </div></form>
        @endif
    </div>
</div>
</div>
@endsection
