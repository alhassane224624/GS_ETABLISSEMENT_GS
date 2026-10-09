@extends('layouts.app')

@section('title', 'Séance')
@section('page-title', 'Détail de la séance')

@section('content')
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            {{ $planning->matiere->nom ?? '—' }}
            <small class="text-muted">— {{ $planning->type_cours_libelle }}</small>
        </h5>
        <span class="badge bg-{{ $planning->statut_color }}">{{ $planning->statut_libelle }}</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><div class="text-muted small">Date</div>{{ \Carbon\Carbon::parse($planning->date)->translatedFormat('l d F Y') }}</div>
            <div class="col-md-4"><div class="text-muted small">Horaire</div>{{ substr($planning->heure_debut, 0, 5) }} – {{ substr($planning->heure_fin, 0, 5) }}</div>
            <div class="col-md-4"><div class="text-muted small">Salle</div>{{ $planning->salle->nom ?? '—' }}</div>
            <div class="col-md-4"><div class="text-muted small">Professeur</div>{{ $planning->professeur->name ?? '—' }}</div>
            <div class="col-md-4"><div class="text-muted small">Classe</div>{{ $planning->classe->nom ?? '—' }}</div>
            <div class="col-md-4"><div class="text-muted small">Créée par</div>{{ $planning->creator->name ?? 'Utilisateur supprimé' }}</div>
            @if ($planning->validated_at)
                <div class="col-md-4"><div class="text-muted small">Validée par</div>{{ $planning->validator->name ?? '—' }} le {{ \Carbon\Carbon::parse($planning->validated_at)->format('d/m/Y H:i') }}</div>
            @endif
            @if ($planning->description)
                <div class="col-12"><div class="text-muted small">Description</div>{{ $planning->description }}</div>
            @endif
            @if ($planning->motif_annulation)
                <div class="col-12"><div class="alert alert-warning mb-0"><strong>Motif d'annulation :</strong> {{ $planning->motif_annulation }}</div></div>
            @endif
        </div>
    </div>
    @if ($planning->appel_fait_at || $planning->contenu_seance || $planning->devoirs)
        <div class="card-body border-top">
            <div class="row g-3">
                <div class="col-md-5">
                    <div class="text-muted small">Appel</div>
                    @if ($planning->appel_fait_at)
                        <div>Fait le {{ $planning->appel_fait_at->format('d/m/Y à H:i') }}</div>
                        @forelse ($planning->absences as $abs)
                            <div class="small">{{ $abs->stagiaire->nom ?? '' }} {{ $abs->stagiaire->prenom ?? '' }} — {{ $abs->type_libelle }}{{ $abs->justifiee ? ' (justifiée)' : '' }}</div>
                        @empty
                            <div class="small text-success">Tous présents</div>
                        @endforelse
                    @else
                        <div class="text-warning">Non fait</div>
                    @endif
                </div>
                <div class="col-md-7">
                    <div class="text-muted small">Cahier de textes</div>
                    <div style="white-space: pre-line;">{{ $planning->contenu_seance ?: '—' }}</div>
                    @if ($planning->devoirs)
                        <div class="mt-2 small"><strong>À faire{{ $planning->devoirs_pour ? ' pour le ' . $planning->devoirs_pour->format('d/m/Y') : '' }} :</strong> {{ $planning->devoirs }}</div>
                    @endif
                </div>
            </div>
        </div>
    @endif
    <div class="card-footer bg-white d-flex flex-wrap gap-2">
        <a href="{{ route('planning.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
        @if ($planning->canBeModified())
            <a href="{{ route('planning.edit', $planning) }}" class="btn btn-outline-primary"><i class="fas fa-edit me-1"></i>Modifier</a>
        @endif
        @if ($planning->canBeValidated())
            <form method="POST" action="{{ route('planning.valider', $planning) }}">@csrf
                <button class="btn btn-success"><i class="fas fa-check me-1"></i>Valider</button>
            </form>
        @endif
        @if ($planning->canBeAnnulated())
            <form method="POST" action="{{ route('planning.annuler', $planning) }}" class="d-flex gap-2">@csrf
                <input type="text" name="motif_annulation" class="form-control" placeholder="Motif d'annulation" required>
                <button class="btn btn-outline-danger"><i class="fas fa-ban me-1"></i>Annuler</button>
            </form>
        @endif
    </div>
</div>
@endsection
