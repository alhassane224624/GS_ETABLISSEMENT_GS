@extends('layouts.app-stagiaire')

@section('title', $publication->titre)
@section('page-title', $publication->type_libelle)

@section('content')
<div class="container-fluid">
    <a href="{{ route('stagiaire.publications.index') }}" class="btn btn-outline-secondary btn-sm mb-3"><i class="fas fa-arrow-left me-1"></i>Devoirs et documents</a>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h4 class="fw-bold mb-1">{{ $publication->titre }}</h4>
            <div class="text-muted mb-3">
                {{ $publication->matiere->nom ?? '—' }} · {{ $publication->professeur->name ?? '' }} · publié le {{ $publication->created_at->isoFormat('D MMMM YYYY') }}
            </div>

            @if ($publication->contenu)
                <div class="mb-3" style="white-space: pre-line;">{{ $publication->contenu }}</div>
            @endif

            @if ($publication->fichier_path)
                <a href="{{ route('publications.fichier', $publication) }}" class="btn btn-primary">
                    <i class="fas fa-download me-1"></i>Télécharger {{ $publication->fichier_nom }}
                </a>
            @endif

            @if ($publication->estDevoir())
                <div class="alert {{ $publication->echeanceDepassee() ? 'alert-danger' : 'alert-warning' }} mt-3 mb-0">
                    <i class="fas fa-clock me-1"></i>À rendre pour le <strong>{{ $publication->date_limite->isoFormat('dddd D MMMM [à] HH[h]mm') }}</strong>
                    @if ($publication->echeanceDepassee()) — la date limite est dépassée. @endif
                </div>
            @endif
        </div>
    </div>

    @if ($publication->estDevoir() && $publication->accepte_rendu)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="fas fa-upload me-2 text-primary"></i>Ma copie</div>
            <div class="card-body">
                @if ($rendu)
                    <div class="alert {{ $rendu->rendu_at->gt($publication->date_limite) ? 'alert-warning' : 'alert-success' }}">
                        <i class="fas fa-check-circle me-1"></i>
                        Copie déposée le {{ $rendu->rendu_at->isoFormat('D MMMM [à] HH[h]mm') }}
                        @if ($rendu->rendu_at->gt($publication->date_limite)) <strong>(en retard)</strong> @endif
                        — <a href="{{ route('rendus.fichier', $rendu) }}">{{ $rendu->fichier_nom }}</a>
                    </div>
                @endif

                <form method="POST" action="{{ route('stagiaire.publications.rendu', $publication) }}" enctype="multipart/form-data" id="form_rendu">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ $rendu ? 'Remplacer ma copie' : 'Fichier de ma copie' }}</label>
                        <input type="file" name="fichier" required class="form-control">
                        <div class="form-text">PDF, Word, Excel, PowerPoint, image ou ZIP — 10 Mo maximum.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message au professeur <span class="text-muted small">(facultatif)</span></label>
                        <textarea name="commentaire" rows="2" maxlength="1000" class="form-control">{{ old('commentaire', $rendu?->commentaire) }}</textarea>
                    </div>
                    @if ($publication->echeanceDepassee())
                        <p class="small text-danger"><i class="fas fa-exclamation-triangle me-1"></i>La date limite est dépassée : votre dépôt sera marqué « en retard ».</p>
                    @endif
                    <button class="btn btn-success" id="btn_rendu"><i class="fas fa-paper-plane me-1"></i>{{ $rendu ? 'Remplacer ma copie' : 'Déposer ma copie' }}</button>
                </form>
            </div>
        </div>
    @elseif ($publication->estDevoir())
        <div class="alert alert-light border"><i class="fas fa-info-circle me-1"></i>Ce devoir se rend directement au professeur, pas en ligne.</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('form_rendu')?.addEventListener('submit', () => {
        const btn = document.getElementById('btn_rendu');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Envoi…';
    });
</script>
@endpush
