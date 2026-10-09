@extends('layouts.app')

@section('title', 'Paramètres de l\'établissement')
@section('page-title', 'Établissement')

@section('content')
@php
    $champ = fn ($nom) => old($nom, $infos[str_replace('etablissement_', '', $nom)] ?? '');
@endphp

<form method="POST" action="{{ route('parametres.etablissement.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-school me-2 text-primary"></i>Identité</div>
                <div class="card-body row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Nom de l'établissement *</label>
                        <input name="etablissement_nom" class="form-control" value="{{ $champ('etablissement_nom') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ville</label>
                        <input name="etablissement_ville" class="form-control" value="{{ $champ('etablissement_ville') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Slogan / sous-titre</label>
                        <input name="etablissement_slogan" class="form-control" value="{{ $champ('etablissement_slogan') }}" placeholder="Ex. Centre de formation professionnelle privé">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse</label>
                        <input name="etablissement_adresse" class="form-control" value="{{ $champ('etablissement_adresse') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Téléphone</label>
                        <input name="etablissement_telephone" class="form-control" value="{{ $champ('etablissement_telephone') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="etablissement_email" class="form-control" value="{{ $champ('etablissement_email') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Site web</label>
                        <input name="etablissement_site" class="form-control" value="{{ $champ('etablissement_site') }}">
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-file-contract me-2 text-primary"></i>Mentions légales</div>
                <div class="card-body row g-3">
                    <div class="col-md-6"><label class="form-label">ICE</label><input name="etablissement_ice" class="form-control" value="{{ $champ('etablissement_ice') }}"></div>
                    <div class="col-md-6"><label class="form-label">RC</label><input name="etablissement_rc" class="form-control" value="{{ $champ('etablissement_rc') }}"></div>
                    <div class="col-md-6"><label class="form-label">Identifiant fiscal (IF)</label><input name="etablissement_if" class="form-control" value="{{ $champ('etablissement_if') }}"></div>
                    <div class="col-md-6"><label class="form-label">N° d'autorisation</label><input name="etablissement_autorisation" class="form-control" value="{{ $champ('etablissement_autorisation') }}"></div>
                    <div class="col-md-6"><label class="form-label">Signataire des documents</label><input name="etablissement_directeur" class="form-control" value="{{ $champ('etablissement_directeur') }}" placeholder="Ex. M. Ahmed Benali"></div>
                    <div class="col-md-6"><label class="form-label">Fonction du signataire</label><input name="etablissement_directeur_titre" class="form-control" value="{{ $champ('etablissement_directeur_titre') }}" placeholder="Ex. Directeur pédagogique"></div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-receipt me-2 text-primary"></i>Banque et reçus</div>
                <div class="card-body row g-3">
                    <div class="col-md-5"><label class="form-label">Banque</label><input name="banque_nom" class="form-control" value="{{ old('banque_nom', $infos['banque_nom'] ?? '') }}"></div>
                    <div class="col-md-7"><label class="form-label">RIB</label><input name="rib_etablissement" class="form-control" value="{{ old('rib_etablissement', $infos['rib_etablissement'] ?? '') }}"></div>
                    <div class="col-12"><label class="form-label">Texte en pied de reçu</label><input name="recu_footer_text" class="form-control" value="{{ old('recu_footer_text', $infos['recu_footer_text'] ?? '') }}" placeholder="Ex. Merci pour votre confiance"></div>
                    <div class="col-12"><label class="form-label">Conditions (petits caractères)</label><textarea name="recu_conditions" rows="2" class="form-control" placeholder="Ex. Les frais versés ne sont pas remboursables.">{{ old('recu_conditions', $infos['recu_conditions'] ?? '') }}</textarea></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-image me-2 text-primary"></i>Logo</div>
                <div class="card-body text-center">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo" class="img-fluid mb-3" style="max-height: 120px">
                        <div class="form-check mb-3 text-start">
                            <input class="form-check-input" type="checkbox" name="supprimer_logo" value="1" id="supprimer_logo">
                            <label class="form-check-label" for="supprimer_logo">Supprimer le logo</label>
                        </div>
                    @else
                        <p class="text-muted small">Aucun logo.</p>
                    @endif
                    <input type="file" name="logo" accept="image/png,image/jpeg" class="form-control">
                    <small class="text-muted d-block mt-2">PNG ou JPG, 1 Mo max. Fond transparent conseillé.</small>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-gavel me-2 text-primary"></i>Délibérations</div>
                <div class="card-body">
                    <label class="form-label">Seuil d'admission (/20)</label>
                    <input type="number" step="0.25" min="0" max="20" name="seuil_admission" class="form-control"
                           value="{{ old('seuil_admission', $infos['seuil_admission'] ?? 10) }}">
                    <small class="text-muted">Moyenne annuelle à partir de laquelle « Admis » est proposé.</small>
                </div>
            </div>

            <div class="alert alert-info small mt-3">
                <i class="fas fa-info-circle me-1"></i>
                Ces informations apparaissent en en-tête de tous les documents : reçus, bulletins, relevés.
            </div>

            <button class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Enregistrer</button>
        </div>
    </div>
</form>
@endsection
