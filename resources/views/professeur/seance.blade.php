@extends('layouts.app-professeur')

@section('title', 'Séance — appel et cahier de textes')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ $planning->matiere->nom ?? '' }} <span class="badge bg-secondary">{{ ucfirst($planning->type_cours) }}</span></h4>
            <div class="text-muted">
                {{ $planning->date->translatedFormat('l d F Y') }} · {{ substr($planning->heure_debut, 0, 5) }}–{{ substr($planning->heure_fin, 0, 5) }}
                · {{ $planning->classe->nom ?? '' }} · Salle {{ $planning->salle->nom ?? '—' }}
            </div>
        </div>
        <div class="text-end">
            @if ($planning->appel_fait_at)
                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Appel fait le {{ $planning->appel_fait_at->format('d/m à H:i') }}</span>
            @else
                <span class="badge bg-warning text-dark">Appel non fait</span>
            @endif
            <div><a href="{{ route('professeur.planning', ['date' => $planning->date->toDateString()]) }}" class="small">← Retour au planning</a></div>
        </div>
    </div>

    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @if (!$commencee)
        <div class="alert alert-info">La séance n'a pas encore commencé : l'appel et le cahier de textes seront disponibles à partir de {{ substr($planning->heure_debut, 0, 5) }}.</div>
    @endif

    <form method="POST" action="{{ route('professeur.seance.enregistrer', $planning) }}">
        @csrf
        <div class="row g-4">
            {{-- Appel --}}
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-semibold"><i class="fas fa-user-check me-2 text-primary"></i>Appel ({{ $stagiaires->count() }} stagiaires)</span>
                        <button type="button" class="btn btn-sm btn-outline-success" id="tous-presents">Tous présents</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <tbody>
                                @forelse ($stagiaires as $s)
                                    @php
                                        $e = $etats[$s->id] ?? null;
                                        $etat = old("presence.{$s->id}", $e ? ($e->retard_minutes !== null ? 'retard' : 'absent') : 'present');
                                        $verrouille = $e && ($e->justifiee || $e->justification_statut);
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $s->nom }} {{ $s->prenom }}</strong>
                                            <div class="small text-muted">{{ $s->matricule }}</div>
                                            @foreach ($autres[$s->id] ?? [] as $a)
                                                <div class="small text-danger"><i class="fas fa-info-circle"></i> {{ $a->type_libelle }} déjà enregistré(e) ce jour{{ $a->justifiee ? ' (justifié)' : '' }}</div>
                                            @endforeach
                                            @if ($verrouille)
                                                <div class="small text-success"><i class="fas fa-lock"></i> {{ $e->justification_libelle ?? 'Absence justifiée' }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap">
                                            @if ($verrouille)
                                                <input type="hidden" name="presence[{{ $s->id }}]" value="{{ $etat }}">
                                                <span class="badge bg-secondary">{{ $etat === 'retard' ? 'Retard' : 'Absent' }}</span>
                                            @else
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <input type="radio" class="btn-check presence" name="presence[{{ $s->id }}]" id="p{{ $s->id }}" value="present" @checked($etat === 'present') @disabled(!$commencee)>
                                                    <label class="btn btn-outline-success" for="p{{ $s->id }}">Présent</label>
                                                    <input type="radio" class="btn-check" name="presence[{{ $s->id }}]" id="a{{ $s->id }}" value="absent" @checked($etat === 'absent') @disabled(!$commencee)>
                                                    <label class="btn btn-outline-danger" for="a{{ $s->id }}">Absent</label>
                                                    <input type="radio" class="btn-check" name="presence[{{ $s->id }}]" id="r{{ $s->id }}" value="retard" @checked($etat === 'retard') @disabled(!$commencee)>
                                                    <label class="btn btn-outline-warning" for="r{{ $s->id }}">Retard</label>
                                                </div>
                                                <input type="number" name="minutes[{{ $s->id }}]" min="1" max="240" placeholder="min"
                                                       value="{{ old("minutes.{$s->id}", $e->retard_minutes ?? '') }}"
                                                       class="form-control form-control-sm d-inline-block ms-1" style="width: 70px;" @disabled(!$commencee)>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="text-center text-muted py-4">Aucun stagiaire actif dans cette classe.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Cahier de textes --}}
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold"><i class="fas fa-book-open me-2 text-primary"></i>Cahier de textes</div>
                    <div class="card-body">
                        <label class="form-label">Contenu de la séance</label>
                        <textarea name="contenu_seance" rows="6" class="form-control mb-3" placeholder="Chapitre, notions vues, exercices faits…">{{ old('contenu_seance', $planning->contenu_seance) }}</textarea>

                        <label class="form-label">Travail à faire</label>
                        <textarea name="devoirs" rows="3" class="form-control mb-2" placeholder="Exercices, lecture, projet…">{{ old('devoirs', $planning->devoirs) }}</textarea>
                        <label class="form-label small text-muted">Pour le</label>
                        <input type="date" name="devoirs_pour" value="{{ old('devoirs_pour', optional($planning->devoirs_pour)->toDateString()) }}" class="form-control">
                        <small class="text-muted">Les stagiaires de la classe voient le contenu et le travail à faire dans leur espace.</small>
                    </div>
                </div>
                <button class="btn btn-primary btn-lg w-100 mt-3" @disabled(!$commencee)>
                    <i class="fas fa-save me-2"></i>{{ $planning->appel_fait_at ? 'Mettre à jour' : 'Enregistrer l\'appel' }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('tous-presents')?.addEventListener('click', () => {
    document.querySelectorAll('input.presence:not(:disabled)').forEach(r => r.checked = true);
});
</script>
@endpush
