@extends('layouts.app')

@section('title', 'Délibération ' . $classe->nom)
@section('page-title', 'Délibération de fin d\'année')

@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between gap-2">
        <div>
            <h5 class="mb-1">{{ $classe->nom }}</h5>
            <small class="text-muted">{{ $classe->filiere->nom ?? '' }} · {{ $classe->niveau->nom ?? '' }} · {{ $classe->anneeScolaire->nom ?? '' }}</small>
        </div>
        <div class="small text-muted text-end">
            Seuil d'admission : <strong>{{ number_format($seuil, 2, ',', ' ') }} / 20</strong><br>
            Niveau suivant : <strong>{{ $niveauSuivant->nom ?? 'aucun (dernière année → diplôme)' }}</strong>
        </div>
    </div>
</div>

<div class="alert alert-info small">
    La moyenne annuelle est la moyenne des <strong>bulletins validés</strong> de l'année. La décision proposée est pré-remplie : vérifiez-la, modifiez si besoin, puis enregistrez.
</div>

<form method="POST" action="{{ route('inscriptions.deliberation.store', $classe) }}">
    @csrf
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Stagiaire</th><th class="text-center">Moyenne annuelle</th><th style="width: 230px;">Décision</th><th>Observation</th></tr>
                </thead>
                <tbody>
                    @forelse ($lignes as $l)
                        @php $i = $l['inscription']; $valeur = old("decisions.{$i->id}.decision", $i->decision ?? $l['proposition']); @endphp
                        <tr>
                            <td>
                                <strong>{{ $i->stagiaire->nom }} {{ $i->stagiaire->prenom }}</strong>
                                <div class="small text-muted">{{ $i->stagiaire->matricule }}</div>
                            </td>
                            <td class="text-center">
                                @if ($l['moyenne'] === null)
                                    <span class="text-muted small">aucun bulletin validé</span>
                                @else
                                    <span class="fw-bold {{ $l['moyenne'] >= $seuil ? 'text-success' : 'text-danger' }}">{{ number_format($l['moyenne'], 2, ',', ' ') }}</span>
                                @endif
                            </td>
                            <td>
                                <select name="decisions[{{ $i->id }}][decision]" class="form-select form-select-sm">
                                    <option value="">— En attente —</option>
                                    @foreach (\App\Models\Inscription::DECISIONS as $cle => $libelle)
                                        @continue($cle === 'admis' && !$niveauSuivant)
                                        @continue($cle === 'diplome' && $niveauSuivant)
                                        <option value="{{ $cle }}" @selected($valeur === $cle)>{{ $libelle }}</option>
                                    @endforeach
                                </select>
                                @if (!$i->decision && $l['proposition'])
                                    <small class="text-muted">proposition automatique</small>
                                @endif
                            </td>
                            <td>
                                <input type="text" name="decisions[{{ $i->id }}][observation]" class="form-control form-control-sm"
                                       value="{{ old("decisions.{$i->id}.observation", $i->observation) }}" placeholder="Facultatif">
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Aucun stagiaire inscrit dans cette classe.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between">
            <div class="d-flex gap-2">
                <a href="{{ route('inscriptions.index', ['annee_id' => $classe->annee_scolaire_id]) }}" class="btn btn-outline-secondary">Retour</a>
                <a href="{{ route('inscriptions.pv', $classe) }}" class="btn btn-outline-dark"><i class="fas fa-file-pdf me-1"></i>Procès-verbal (PDF)</a>
            </div>
            <button class="btn btn-primary"><i class="fas fa-save me-1"></i>Enregistrer les décisions</button>
        </div>
    </div>
</form>
@endsection
