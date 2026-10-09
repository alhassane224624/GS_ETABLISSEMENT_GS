@extends($layout)
@section('title', 'Dépenses')
@section('page-title', 'Dépenses')

@section('content')
@php $dh = fn ($m) => number_format((float) $m, 2, ',', ' ') . ' DH'; @endphp
<div class="container-fluid py-2">
@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="row g-4">
    <div class="col-lg-8">
        <form method="GET" class="card shadow-sm mb-3"><div class="card-body row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small">Du</label><input type="date" name="debut" value="{{ $debut->toDateString() }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label small">Au</label><input type="date" name="fin" value="{{ $fin->toDateString() }}" class="form-control"></div>
            <div class="col-md-4"><label class="form-label small">Catégorie</label>
                <select name="categorie" class="form-select"><option value="">Toutes</option>@foreach (\App\Models\Depense::CATEGORIES as $k => $v)<option value="{{ $k }}" @selected(request('categorie') === $k)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Filtrer</button></div>
        </div></form>

        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between"><span class="fw-semibold">Dépenses</span><span class="fw-bold text-danger">Total : {{ $dh($total) }}</span></div>
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Date</th><th>Libellé</th><th>Catégorie</th><th>Mode</th><th class="text-end">Montant</th><th></th></tr></thead>
                <tbody>
                @forelse ($depenses as $d)
                    <tr>
                        <td>{{ $d->date_depense->format('d/m/Y') }}</td>
                        <td>{{ $d->libelle }}@if ($d->fournisseur)<div class="small text-muted">{{ $d->fournisseur }}</div>@endif</td>
                        <td><span class="badge bg-light text-dark border">{{ $d->categorie_libelle }}</span></td>
                        <td class="small">{{ $d->mode_libelle }}{{ $d->reference ? ' · ' . $d->reference : '' }}</td>
                        <td class="text-end fw-semibold">{{ $dh($d->montant) }}</td>
                        <td class="text-end text-nowrap">
                            @if ($d->justificatif)<a href="{{ asset('storage/' . $d->justificatif) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-paperclip"></i></a>@endif
                            @unless ($d->salaire_id)
                                <form method="POST" action="{{ route('finances.depenses.destroy', $d) }}" class="d-inline" onsubmit="return confirm('Supprimer cette dépense ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucune dépense sur la période.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            @if ($depenses->hasPages())<div class="card-footer bg-white">{{ $depenses->links() }}</div>@endif
        </div>
    </div>

    <div class="col-lg-4">
        <form method="POST" action="{{ route('finances.depenses.store') }}" enctype="multipart/form-data" class="card shadow-sm">
            @csrf
            <div class="card-header bg-white fw-semibold"><i class="fas fa-plus me-1 text-danger"></i>Nouvelle dépense</div>
            <div class="card-body row g-2">
                <div class="col-12"><label class="form-label">Catégorie</label><select name="categorie" class="form-select" required>@foreach (\App\Models\Depense::CATEGORIES as $k => $v)<option value="{{ $k }}" @selected(old('categorie') === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">Libellé</label><input name="libelle" value="{{ old('libelle') }}" class="form-control" required placeholder="Ex. Loyer octobre"></div>
                <div class="col-6"><label class="form-label">Montant (DH)</label><input type="number" step="0.01" min="0.01" name="montant" value="{{ old('montant') }}" class="form-control" required></div>
                <div class="col-6"><label class="form-label">Date</label><input type="date" name="date_depense" value="{{ old('date_depense', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="form-control" required></div>
                <div class="col-6"><label class="form-label">Mode</label><select name="mode_paiement" class="form-select">@foreach (\App\Models\Depense::MODES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div class="col-6"><label class="form-label">Référence</label><input name="reference" value="{{ old('reference') }}" class="form-control" placeholder="N° chèque, facture…"></div>
                <div class="col-12"><label class="form-label">Fournisseur</label><input name="fournisseur" value="{{ old('fournisseur') }}" class="form-control"></div>
                <div class="col-12"><label class="form-label">Justificatif</label><input type="file" name="justificatif" accept=".pdf,.jpg,.jpeg,.png" class="form-control"></div>
                <div class="col-12"><button class="btn btn-danger w-100 mt-1">Enregistrer</button></div>
                <small class="text-muted">Les salaires payés s'ajoutent automatiquement.</small>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
