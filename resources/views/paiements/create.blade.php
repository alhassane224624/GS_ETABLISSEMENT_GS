@extends(auth()->user()->role === 'comptable' ? 'layouts.comptable' : 'layouts.app')

@section('title', 'Encaisser un paiement')

@section('content')
@php $dh = fn ($m) => number_format((float) $m, 2, ',', ' ') . ' DH'; @endphp

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fas fa-cash-register text-primary me-2"></i>Encaisser un paiement</h4>
        <a href="{{ route('paiements.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    {{-- 1. Choix du stagiaire (recharge la page avec ses échéances) --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('paiements.create') }}">
                <label class="form-label fw-semibold">1. Stagiaire</label>
                <select name="stagiaire_id" class="form-select form-select-lg" onchange="this.form.submit()" required>
                    <option value="">— Choisir un stagiaire —</option>
                    @foreach ($stagiaires as $s)
                        <option value="{{ $s->id }}" @selected(optional($stagiaire)->id === $s->id)>{{ $s->nom }} {{ $s->prenom }} — {{ $s->matricule }}</option>
                    @endforeach
                </select>
            </form>

            @if ($stagiaire)
                <div class="row g-3 mt-2">
                    <div class="col-md-3"><small class="text-muted d-block">Filière / classe</small><strong>{{ $stagiaire->filiere->nom ?? '—' }}</strong> <span class="text-muted">{{ $stagiaire->classe->nom ?? '' }}</span></div>
                    <div class="col-md-3"><small class="text-muted d-block">Reste à payer</small><strong class="text-danger">{{ $dh($echeances->sum('montant_restant')) }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">Déjà en attente d'encaissement</small><strong class="text-warning">{{ $dh($enAttente->sum('montant')) }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">Montant encaissable</small><strong class="text-success fs-5">{{ $dh($encaissable) }}</strong></div>
                </div>
            @endif
        </div>
    </div>

    @if ($stagiaire)
        @if ($enAttente->isNotEmpty())
            <div class="alert alert-warning">
                <i class="fas fa-hourglass-half me-1"></i>
                Paiement(s) en attente pour ce stagiaire :
                @foreach ($enAttente as $p)
                    <a href="{{ route('paiements.show', $p) }}" class="alert-link">{{ $p->numero_transaction }}</a> ({{ $dh($p->montant) }}, {{ mb_strtolower($p->methode_libelle) }}){{ !$loop->last ? ',' : '.' }}
                @endforeach
                Ils sont déduits du montant encaissable.
            </div>
        @endif

        @if ($echeances->isEmpty())
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span><i class="fas fa-info-circle me-1"></i>Ce stagiaire n'a aucune échéance à régler. Un paiement doit toujours correspondre à une échéance (inscription, mensualité, examen…).</span>
                <a href="{{ route('echeanciers.create', ['stagiaire_id' => $stagiaire->id]) }}" class="btn btn-sm btn-primary">Créer une échéance</a>
            </div>
        @else
            <form method="POST" action="{{ route('paiements.store') }}" enctype="multipart/form-data" id="form-paiement">
                @csrf
                <input type="hidden" name="stagiaire_id" value="{{ $stagiaire->id }}">

                <div class="row g-4">
                    {{-- 2. Échéances --}}
                    <div class="col-lg-7">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white fw-semibold">2. Échéances réglées <small class="text-muted fw-normal">(facultatif)</small></div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="tout-cocher"></th>
                                            <th>Échéance</th><th>Date</th><th class="text-end">Reste dû</th><th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($echeances as $e)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="echeanciers[]" value="{{ $e->id }}" class="form-check-input case-echeance"
                                                           data-reste="{{ $e->montant_restant }}" @checked(in_array($e->id, old('echeanciers', [])))>
                                                </td>
                                                <td>
                                                    <strong>{{ $e->titre }}</strong>
                                                    <div class="small text-muted">{{ $e->type_libelle }}@if ($e->montant_remise > 0) · remise {{ $dh($e->montant_remise) }}@endif</div>
                                                </td>
                                                <td>{{ $e->date_echeance->format('d/m/Y') }}</td>
                                                <td class="text-end fw-semibold">{{ $dh($e->montant_restant) }}</td>
                                                <td><span class="badge bg-{{ $e->statut_couleur }}">{{ $e->statut_libelle }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer bg-white small text-muted">
                                Cochez les échéances payées : le montant se remplit automatiquement.
                                Sans sélection, le paiement règle les échéances <strong>de la plus ancienne à la plus récente</strong>.
                            </div>
                        </div>
                    </div>

                    {{-- 3. Règlement --}}
                    <div class="col-lg-5">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white fw-semibold">3. Règlement</div>
                            <div class="card-body row g-3">
                                <div class="col-12">
                                    <label class="form-label">Montant (DH) *</label>
                                    <input type="number" name="montant" id="montant" step="0.01" min="0.01" max="{{ $encaissable }}"
                                           value="{{ old('montant') }}" class="form-control form-control-lg @error('montant') is-invalid @enderror" required>
                                    <small class="text-muted">Maximum : {{ $dh($encaissable) }}</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Mode de règlement *</label>
                                    <select name="methode_paiement" id="methode_paiement" class="form-select @error('methode_paiement') is-invalid @enderror" required>
                                        @foreach (\App\Models\Paiement::METHODES as $cle => $libelle)
                                            <option value="{{ $cle }}" @selected(old('methode_paiement', 'especes') === $cle)>{{ $libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date du paiement *</label>
                                    <input type="date" name="date_paiement" value="{{ old('date_paiement', now()->toDateString()) }}"
                                           max="{{ now()->toDateString() }}" class="form-control @error('date_paiement') is-invalid @enderror" required>
                                </div>

                                <div class="col-md-6" id="bloc-reference">
                                    <label class="form-label" id="label-reference">Référence</label>
                                    <input type="text" name="reference_externe" value="{{ old('reference_externe') }}" class="form-control @error('reference_externe') is-invalid @enderror">
                                </div>
                                <div class="col-md-6" id="bloc-banque">
                                    <label class="form-label">Banque émettrice</label>
                                    <input type="text" name="banque" value="{{ old('banque') }}" class="form-control" placeholder="Ex. CIH Bank">
                                </div>

                                <div class="col-12">
                                    <div class="alert py-2 mb-0" id="info-validation"></div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Justificatif <small class="text-muted">(photo du chèque, avis de virement…)</small></label>
                                    <input type="file" name="justificatif" accept=".pdf,.jpg,.jpeg,.png" class="form-control @error('justificatif') is-invalid @enderror">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Observation</label>
                                    <textarea name="notes_admin" rows="2" class="form-control">{{ old('notes_admin') }}</textarea>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-success btn-lg w-100"><i class="fas fa-check me-2"></i>Enregistrer le paiement</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-paiement');
    if (!form) return;

    const montant = document.getElementById('montant');
    const cases = form.querySelectorAll('.case-echeance');
    const toutCocher = document.getElementById('tout-cocher');
    const methode = document.getElementById('methode_paiement');
    const blocRef = document.getElementById('bloc-reference');
    const blocBanque = document.getElementById('bloc-banque');
    const labelRef = document.getElementById('label-reference');
    const info = document.getElementById('info-validation');
    const max = parseFloat(montant.max);

    // Montant = total des échéances cochées (plafonné à l'encaissable)
    function majMontant() {
        const total = Array.from(cases).filter(c => c.checked).reduce((s, c) => s + parseFloat(c.dataset.reste), 0);
        if (total > 0) montant.value = Math.min(total, max).toFixed(2);
        toutCocher.checked = cases.length > 0 && Array.from(cases).every(c => c.checked);
    }
    cases.forEach(c => c.addEventListener('change', majMontant));
    toutCocher.addEventListener('change', () => { cases.forEach(c => c.checked = toutCocher.checked); majMontant(); });

    // Champs selon le mode de règlement
    const libelles = { cheque: 'N° de chèque *', virement: 'Référence du virement *', carte: 'N° d\'autorisation', mobile_money: 'Référence de la transaction' };
    function majMethode() {
        const m = methode.value;
        blocRef.style.display = libelles[m] ? '' : 'none';
        blocBanque.style.display = (m === 'cheque' || m === 'virement') ? '' : 'none';
        if (libelles[m]) labelRef.textContent = libelles[m];
        if (m === 'especes') {
            info.className = 'alert alert-success py-2 mb-0';
            info.innerHTML = '<i class="fas fa-bolt me-1"></i>Espèces : le paiement est <strong>validé immédiatement</strong> et le reçu est disponible.';
        } else {
            info.className = 'alert alert-warning py-2 mb-0';
            info.innerHTML = '<i class="fas fa-hourglass-half me-1"></i>Le paiement restera <strong>en attente</strong> et ne réglera les échéances qu\'après validation (encaissement confirmé).';
        }
    }
    methode.addEventListener('change', majMethode);
    majMethode();

    form.addEventListener('submit', function (e) {
        if (parseFloat(montant.value) > max) {
            e.preventDefault();
            alert('Le montant dépasse le maximum encaissable (' + max.toFixed(2) + ' DH).');
        }
    });
});
</script>
@endpush
