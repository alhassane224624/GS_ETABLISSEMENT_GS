@extends($layout)
@section('title', 'Salaires')
@section('page-title', 'Salaires des professeurs')

@section('content')
@php $dh = fn ($m) => number_format((float) $m, 2, ',', ' ') . ' DH'; @endphp
<div class="container-fluid py-2">
@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="card shadow-sm mb-3"><div class="card-body d-flex flex-wrap gap-2 align-items-end justify-content-between">
    <form method="GET" class="d-flex gap-2 align-items-end">
        <div><label class="form-label small">Mois</label><input type="month" name="mois" value="{{ $mois->format('Y-m') }}" class="form-control" onchange="this.form.submit()"></div>
    </form>
    <form method="POST" action="{{ route('finances.salaires.calculer') }}" onsubmit="return confirm('Calculer les salaires de {{ $mois->translatedFormat('F Y') }} ? Les salaires déjà payés ne changent pas.')">
        @csrf <input type="hidden" name="mois" value="{{ $mois->format('Y-m') }}">
        <button class="btn btn-primary"><i class="fas fa-calculator me-1"></i>Calculer {{ $mois->translatedFormat('F Y') }}</button>
    </form>
</div></div>

<div class="alert alert-light border small">
    <strong>Calcul :</strong> taux horaire × heures des séances <strong>réellement faites</strong> (séance terminée ou appel fait, hors annulées) ;
    ou salaire fixe mensuel. Ajoutez primes et retenues dans le détail, puis « Payer » : la dépense est enregistrée automatiquement.
</div>

<div class="card shadow-sm mb-4">
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Professeur</th><th class="text-center">Séances</th><th class="text-center">Heures</th><th class="text-end">Base</th><th class="text-end">Primes / retenues</th><th class="text-end">Net</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        @forelse ($salaires as $s)
            <tr>
                <td>{{ $s->professeur->name ?? '' }}<div class="small text-muted">{{ $s->mode_remuneration === 'fixe' ? 'Fixe' : 'Horaire (' . $dh($s->taux_horaire) . '/h)' }}</div></td>
                <td class="text-center">{{ $s->nb_seances }}</td>
                <td class="text-center">{{ rtrim(rtrim(number_format($s->heures, 2, ',', ''), '0'), ',') }} h</td>
                <td class="text-end">{{ $dh($s->montant_base) }}</td>
                <td class="text-end small">+{{ $dh($s->primes) }} / −{{ $dh($s->retenues) }}</td>
                <td class="text-end fw-bold">{{ $dh($s->montant_net) }}</td>
                <td>@if ($s->estPaye())<span class="badge bg-success">Payé le {{ $s->date_paiement->format('d/m') }}</span>@else<span class="badge bg-warning text-dark">À payer</span>@endif</td>
                <td class="text-end"><a href="{{ route('finances.salaires.show', $s) }}" class="btn btn-sm btn-outline-primary">Détail</a></td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-4">Aucun salaire calculé pour ce mois.</td></tr>
        @endforelse
        </tbody>
        @if ($salaires->isNotEmpty())
            <tfoot class="table-light"><tr><th colspan="5" class="text-end">Total</th><th class="text-end">{{ $dh($salaires->sum('montant_net')) }}</th><th colspan="2"></th></tr></tfoot>
        @endif
    </table></div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold"><i class="fas fa-user-tie me-1 text-primary"></i>Rémunération des professeurs</div>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <tbody>
        @foreach ($professeurs as $p)
            <tr>
                <td>{{ $p->name }}</td>
                <td>
                    <form method="POST" action="{{ route('finances.remuneration', $p) }}" class="d-flex flex-wrap gap-2 align-items-center">
                        @csrf
                        <select name="mode_remuneration" class="form-select form-select-sm w-auto">
                            <option value="horaire" @selected($p->mode_remuneration !== 'fixe')>Taux horaire</option>
                            <option value="fixe" @selected($p->mode_remuneration === 'fixe')>Salaire fixe</option>
                        </select>
                        <input type="number" step="0.01" min="0" name="taux_horaire" value="{{ $p->taux_horaire }}" placeholder="DH / heure" class="form-control form-control-sm" style="width: 120px;">
                        <input type="number" step="0.01" min="0" name="salaire_fixe" value="{{ $p->salaire_fixe }}" placeholder="DH / mois" class="form-control form-control-sm" style="width: 120px;">
                        <button class="btn btn-sm btn-outline-primary">Enregistrer</button>
                        @unless ($p->mode_remuneration)<span class="badge bg-danger">Non défini</span>@endunless
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</div>
</div>
@endsection
