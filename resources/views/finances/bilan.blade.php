@extends($layout)
@section('title', 'Bilan financier')
@section('page-title', 'Bilan et caisse')

@section('content')
@php
    $dh = fn ($m) => number_format((float) $m, 2, ',', ' ') . ' DH';
    $max = max(1, collect($bilan['mois'])->flatMap(fn ($m) => [$m['recettes'], $m['depenses']])->max());
    $modes = \App\Models\Paiement::METHODES;
@endphp
<div class="container-fluid py-2">
<form method="GET" class="card shadow-sm mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label small">Du</label><input type="date" name="debut" value="{{ $debut->toDateString() }}" class="form-control"></div>
    <div class="col-md-4"><label class="form-label small">Au</label><input type="date" name="fin" value="{{ $fin->toDateString() }}" class="form-control"></div>
    <div class="col-md-4"><button class="btn btn-primary w-100">Afficher</button></div>
</div></form>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">Recettes encaissées</small><div class="fs-4 fw-bold text-success">{{ $dh($bilan['recettes']) }}</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">Dépenses</small><div class="fs-4 fw-bold text-danger">{{ $dh($bilan['depenses']) }}</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">Résultat</small><div class="fs-4 fw-bold {{ $bilan['solde'] >= 0 ? 'text-primary' : 'text-danger' }}">{{ $dh($bilan['solde']) }}</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">Impayés à échéance</small><div class="fs-4 fw-bold text-warning">{{ $dh($bilan['impayes']) }}</div></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Évolution mensuelle</div>
            <div class="card-body">
                @foreach ($bilan['mois'] as $m)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small"><strong>{{ $m['libelle'] }}</strong><span class="{{ $m['recettes'] - $m['depenses'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $dh($m['recettes'] - $m['depenses']) }}</span></div>
                        <div class="progress mb-1" style="height: 8px;"><div class="progress-bar bg-success" style="width: {{ $m['recettes'] / $max * 100 }}%"></div></div>
                        <div class="progress" style="height: 8px;"><div class="progress-bar bg-danger" style="width: {{ $m['depenses'] / $max * 100 }}%"></div></div>
                    </div>
                @endforeach
                <small class="text-muted"><span class="text-success">■</span> recettes <span class="text-danger ms-2">■</span> dépenses</small>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="fas fa-cash-register me-1"></i>Caisse (espèces) sur la période</div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Entrées</span><strong class="text-success">+ {{ $dh($bilan['caisse_entrees']) }}</strong></div>
                <div class="d-flex justify-content-between"><span>Sorties</span><strong class="text-danger">− {{ $dh($bilan['caisse_sorties']) }}</strong></div>
                <hr class="my-2">
                <div class="d-flex justify-content-between"><span>Variation de caisse</span><strong>{{ $dh($bilan['caisse_entrees'] - $bilan['caisse_sorties']) }}</strong></div>
                <small class="text-muted">À rapprocher du comptage réel de la caisse.</small>
            </div>
        </div>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Recettes par mode</div>
            <ul class="list-group list-group-flush">
                @forelse ($bilan['recettes_par_mode'] as $mode => $t)<li class="list-group-item d-flex justify-content-between"><span>{{ $modes[$mode] ?? $mode }}</span><strong>{{ $dh($t) }}</strong></li>@empty<li class="list-group-item text-muted">—</li>@endforelse
            </ul>
        </div>
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Dépenses par catégorie</div>
            <ul class="list-group list-group-flush">
                @forelse ($bilan['depenses_par_categorie'] as $cat => $t)<li class="list-group-item d-flex justify-content-between"><span>{{ \App\Models\Depense::CATEGORIES[$cat] ?? $cat }}</span><strong>{{ $dh($t) }}</strong></li>@empty<li class="list-group-item text-muted">—</li>@endforelse
            </ul>
        </div>
    </div>
</div>
</div>
@endsection
