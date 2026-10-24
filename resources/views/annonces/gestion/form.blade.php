@extends($layout)

@php $edition = $annonce->exists; @endphp
@section('title', $edition ? 'Modifier l\'annonce' : 'Nouvelle annonce')
@section('page-title', $edition ? 'Modifier l\'annonce' : 'Nouvelle annonce')

@section('content')
@php
    $portee = old('portee', !empty($annonce->classe_ids) ? 'classes' : ($annonce->filiere_id ? 'filiere' : 'tous'));
    $rolesChoisis = old('roles', $annonce->roles ?? []);
    $classesChoisies = array_map('intval', old('classe_ids', $annonce->classe_ids ?? []));
@endphp

<div class="mb-3">
    <a href="{{ $edition ? route('gestion.annonces.show', $annonce) : route('gestion.annonces.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
</div>

{{-- Le layout comptable affiche déjà les erreurs --}}
@if ($errors->any() && $layout !== 'layouts.comptable')
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1"><i class="fas fa-exclamation-triangle me-2"></i>L'annonce n'a pas été enregistrée :</div>
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ $edition ? route('gestion.annonces.update', $annonce) : route('gestion.annonces.store') }}" enctype="multipart/form-data" id="form_annonce">
    @csrf
    @if ($edition) @method('PUT') @endif

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold"><i class="fas fa-pen me-2 text-primary"></i>Contenu</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Titre <span class="text-danger">*</span></label>
                <input type="text" name="titre" value="{{ old('titre', $annonce->titre) }}" required maxlength="255" class="form-control" placeholder="Ex. : Réunion parents-professeurs du 15 novembre">
            </div>
            <div class="mb-3">
                <label class="form-label">Message <span class="text-danger">*</span></label>
                <textarea name="contenu" rows="7" required maxlength="10000" class="form-control">{{ old('contenu', $annonce->contenu) }}</textarea>
            </div>
            <div>
                <label class="form-label">Pièce jointe <span class="text-muted small">(circulaire PDF, document… 10 Mo max)</span></label>
                @if ($annonce->fichier_path)
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="small"><i class="fas fa-paperclip me-1"></i>{{ $annonce->fichier_nom }}</span>
                        <div class="form-check">
                            <input type="checkbox" name="retirer_fichier" value="1" id="retirer_fichier" class="form-check-input">
                            <label for="retirer_fichier" class="form-check-label small">Retirer</label>
                        </div>
                    </div>
                @endif
                <input type="file" name="fichier" class="form-control">
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold"><i class="fas fa-users me-2 text-primary"></i>Destinataires</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label d-block">Qui doit la recevoir ? <span class="text-danger">*</span></label>
                @foreach (\App\Models\Annonce::ROLES as $valeur => $libelle)
                    <div class="form-check form-check-inline">
                        <input type="checkbox" name="roles[]" value="{{ $valeur }}" id="role_{{ $valeur }}" class="form-check-input" @checked(in_array($valeur, $rolesChoisis))>
                        <label for="role_{{ $valeur }}" class="form-check-label">{{ $libelle }}</label>
                    </div>
                @endforeach
            </div>
            <div class="mb-3">
                <label class="form-label d-block">Portée</label>
                @foreach (['tous' => 'Tout l\'établissement', 'filiere' => 'Une filière', 'classes' => 'Des classes'] as $valeur => $libelle)
                    <div class="form-check form-check-inline">
                        <input type="radio" name="portee" value="{{ $valeur }}" id="portee_{{ $valeur }}" class="form-check-input" @checked($portee === $valeur)>
                        <label for="portee_{{ $valeur }}" class="form-check-label">{{ $libelle }}</label>
                    </div>
                @endforeach
                <div class="form-text">Pour une filière ou des classes : les stagiaires et parents concernés, et les professeurs affectés à la filière. L'administration et la comptabilité, si cochées, la reçoivent toujours.</div>
            </div>
            <div id="bloc_filiere" class="mb-3">
                <select name="filiere_id" class="form-select">
                    <option value="">Choisir la filière…</option>
                    @foreach ($filieres as $f)
                        <option value="{{ $f->id }}" @selected(old('filiere_id', $annonce->filiere_id) == $f->id)>{{ $f->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div id="bloc_classes" class="mb-3">
                <select name="classe_ids[]" multiple size="6" class="form-select">
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}" @selected(in_array($c->id, $classesChoisies))>{{ $c->nom }} — {{ $c->filiere->nom ?? '' }}</option>
                    @endforeach
                </select>
                <div class="form-text">Ctrl + clic pour en choisir plusieurs. Classes de l'année en cours.</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold"><i class="fas fa-sliders-h me-2 text-primary"></i>Diffusion</div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                @foreach ([
                    'info' => 'Visible dans la page Annonces.',
                    'important' => 'Notification + bandeau jaune jusqu\'à la lecture.',
                    'urgent' => 'Notification + bandeau rouge sur toutes les pages jusqu\'à la lecture.',
                ] as $valeur => $aide)
                    <div class="col-md-4">
                        <input type="radio" class="btn-check" name="importance" id="imp_{{ $valeur }}" value="{{ $valeur }}" @checked(old('importance', $annonce->importance) === $valeur)>
                        <label class="btn btn-outline-{{ \App\Models\Annonce::COULEURS[$valeur] }} w-100 text-start h-100" for="imp_{{ $valeur }}">
                            <i class="fas {{ \App\Models\Annonce::ICONES[$valeur] }} me-1"></i><strong>{{ \App\Models\Annonce::IMPORTANCES[$valeur] }}</strong>
                            <div class="small">{{ $aide }}</div>
                        </label>
                    </div>
                @endforeach
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Publier le</label>
                    <input type="datetime-local" name="publie_le" value="{{ old('publie_le', optional($annonce->publie_le)->format('Y-m-d\TH:i')) }}" class="form-control">
                    <div class="form-text">Une date future programme la publication.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Retirer le <span class="text-muted small">(facultatif)</span></label>
                    <input type="datetime-local" name="expire_le" value="{{ old('expire_le', optional($annonce->expire_le)->format('Y-m-d\TH:i')) }}" class="form-control">
                </div>
                <div class="col-md-4 d-flex align-items-center">
                    <div class="form-check form-switch">
                        <input type="hidden" name="epinglee" value="0">
                        <input type="checkbox" name="epinglee" value="1" id="epinglee" class="form-check-input" @checked(old('epinglee', $annonce->epinglee))>
                        <label for="epinglee" class="form-check-label">Épingler en tête de liste</label>
                    </div>
                </div>
            </div>
            @if ($edition && $annonce->notifiee_at)
                <div class="alert alert-light border small mt-3 mb-0"><i class="fas fa-info-circle me-1"></i>Les destinataires ont déjà été notifiés : une modification ne renvoie pas de notification.</div>
            @endif
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="{{ route('gestion.annonces.index') }}" class="btn btn-outline-secondary">Annuler</a>
        <button class="btn btn-primary" id="btn_annonce"><i class="fas fa-paper-plane me-1"></i>{{ $edition ? 'Enregistrer' : 'Publier' }}</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('form_annonce');
    const maj = () => {
        const p = form.querySelector('input[name="portee"]:checked')?.value;
        document.getElementById('bloc_filiere').style.display = p === 'filiere' ? '' : 'none';
        document.getElementById('bloc_classes').style.display = p === 'classes' ? '' : 'none';
    };
    form.querySelectorAll('input[name="portee"]').forEach(r => r.addEventListener('change', maj));
    maj();
    form.addEventListener('submit', () => {
        const btn = document.getElementById('btn_annonce');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…';
    });
})();
</script>
@endpush
