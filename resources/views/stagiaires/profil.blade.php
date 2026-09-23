@extends('layouts.app-stagiaire')

@section('title', 'Mon Profil')
@section('page-title', 'Mon Profil')

@section('content')
<div class="container-fluid profile-page">

    <!-- ===================== BANDEAU DE COUVERTURE ===================== -->
    <div class="profile-cover rounded-4 mb-5 position-relative">
        <div class="profile-cover-pattern rounded-4"></div>
        <div class="profile-cover-glow"></div>

        <div class="position-relative px-4 px-md-5 pb-4 pt-5 d-flex flex-column flex-md-row align-items-center align-items-md-end gap-4">
            <div class="avatar-wrap">
                <img src="{{ $stagiaire->photo_url }}"
                     alt="{{ $stagiaire->nom_complet }}"
                     class="profile-avatar rounded-circle">
                <span class="avatar-status" title="{{ $stagiaire->statut_libelle }}">
                    <i class="fas fa-check"></i>
                </span>
            </div>

            <div class="text-center text-md-start flex-grow-1 pb-2">
                <div class="d-flex flex-column flex-md-row align-items-center align-items-md-center gap-md-3">
                    <h3 class="fw-bold text-white mb-0">{{ $stagiaire->nom }} {{ $stagiaire->prenom }}</h3>
                    <span class="badge badge-glass mt-2 mt-md-0">
                        <i class="fas fa-check-circle me-1"></i> {{ $stagiaire->statut_libelle }}
                    </span>
                </div>
                <p class="text-white-50 mb-0 mt-2">
                    <i class="fas fa-id-badge me-1"></i> {{ $stagiaire->matricule }}
                    <span class="mx-2 opacity-50">•</span>
                    <i class="fas fa-book me-1"></i> {{ $stagiaire->filiere->nom ?? 'N/A' }}
                </p>
            </div>
        </div>
    </div>

    <!-- ===================== CHIPS STATISTIQUES ===================== -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="quick-stat">
                <div class="quick-stat-icon icon-indigo">
                    <i class="fas fa-book"></i>
                </div>
                <div class="min-w-0">
                    <small class="text-muted d-block">Filière</small>
                    <strong class="text-truncate d-block">{{ $stagiaire->filiere->nom ?? 'N/A' }}</strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="quick-stat">
                <div class="quick-stat-icon icon-cyan">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="min-w-0">
                    <small class="text-muted d-block">Niveau</small>
                    <strong class="text-truncate d-block">{{ $stagiaire->niveau->nom ?? 'N/A' }}</strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="quick-stat">
                <div class="quick-stat-icon icon-amber">
                    <i class="fas fa-chalkboard"></i>
                </div>
                <div class="min-w-0">
                    <small class="text-muted d-block">Classe</small>
                    <strong class="text-truncate d-block">{{ $stagiaire->classe->nom ?? 'N/A' }}</strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="quick-stat">
                <div class="quick-stat-icon icon-emerald">
                    <i class="fas fa-birthday-cake"></i>
                </div>
                <div class="min-w-0">
                    <small class="text-muted d-block">Âge</small>
                    <strong class="text-truncate d-block">
                        @if($stagiaire->date_naissance)
                            {{ $stagiaire->age }} ans
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- ===================== COLONNE GAUCHE ===================== -->
        <div class="col-lg-4">
            <div class="card profile-card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">
                        <span class="header-icon icon-indigo"><i class="fas fa-address-card"></i></span>
                        Informations de Contact
                    </h6>
                </div>
                <div class="card-body">
                    <div class="info-line">
                        <div class="info-line-icon icon-emerald">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="min-w-0">
                            <small class="text-muted d-block">Téléphone</small>
                            <strong>{{ $stagiaire->telephone ?: 'Non renseigné' }}</strong>
                        </div>
                    </div>
                    <div class="info-line">
                        <div class="info-line-icon icon-indigo">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="min-w-0">
                            <small class="text-muted d-block">Email</small>
                            <strong class="text-break">{{ $stagiaire->email ?: 'Non renseigné' }}</strong>
                        </div>
                    </div>
                    <div class="info-line mb-0">
                        <div class="info-line-icon icon-rose">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="min-w-0">
                            <small class="text-muted d-block">Adresse</small>
                            <strong>{{ $stagiaire->adresse ?: 'Non renseignée' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations tuteur -->
            @if($stagiaire->nom_tuteur || $stagiaire->telephone_tuteur || $stagiaire->email_tuteur)
            <div class="card profile-card">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">
                        <span class="header-icon icon-amber"><i class="fas fa-users"></i></span>
                        Informations du Tuteur
                    </h6>
                </div>
                <div class="card-body">
                    @if($stagiaire->nom_tuteur)
                    <div class="info-line">
                        <div class="info-line-icon icon-amber">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="min-w-0">
                            <small class="text-muted d-block">Nom</small>
                            <strong>{{ $stagiaire->nom_tuteur }}</strong>
                        </div>
                    </div>
                    @endif
                    @if($stagiaire->telephone_tuteur)
                    <div class="info-line">
                        <div class="info-line-icon icon-emerald">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="min-w-0">
                            <small class="text-muted d-block">Téléphone</small>
                            <strong>{{ $stagiaire->telephone_tuteur }}</strong>
                        </div>
                    </div>
                    @endif
                    @if($stagiaire->email_tuteur)
                    <div class="info-line mb-0">
                        <div class="info-line-icon icon-indigo">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="min-w-0">
                            <small class="text-muted d-block">Email</small>
                            <strong class="text-break">{{ $stagiaire->email_tuteur }}</strong>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- ===================== COLONNE DROITE ===================== -->
        <div class="col-lg-8">
            <!-- Informations personnelles -->
            <div class="card profile-card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">
                        <span class="header-icon icon-indigo"><i class="fas fa-user"></i></span>
                        Informations Personnelles
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Date de Naissance</small>
                            <strong>
                                @if($stagiaire->date_naissance)
                                    {{ $stagiaire->date_naissance->format('d/m/Y') }}
                                    <span class="badge-soft icon-cyan ms-2">{{ $stagiaire->age }} ans</span>
                                @else
                                    <span class="text-muted">Non renseignée</span>
                                @endif
                            </strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Lieu de Naissance</small>
                            <strong>{{ $stagiaire->lieu_naissance ?? 'Non renseigné' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Sexe</small>
                            <strong>
                                @if($stagiaire->sexe == 'M')
                                    <i class="fas fa-mars text-primary me-1"></i> Masculin
                                @elseif($stagiaire->sexe == 'F')
                                    <i class="fas fa-venus text-pink me-1"></i> Féminin
                                @else
                                    <span class="text-muted">Non renseigné</span>
                                @endif
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations scolaires -->
            <div class="card profile-card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">
                        <span class="header-icon icon-emerald"><i class="fas fa-graduation-cap"></i></span>
                        Informations Scolaires
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Filière</small>
                            <strong class="text-primary">
                                <i class="fas fa-book me-1"></i>
                                {{ $stagiaire->filiere->nom ?? 'N/A' }}
                            </strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Niveau</small>
                            <strong>{{ $stagiaire->niveau->nom ?? 'N/A' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Classe</small>
                            <strong>{{ $stagiaire->classe->nom ?? 'N/A' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Date d'Inscription</small>
                            <strong>
                                @if($stagiaire->date_inscription)
                                    {{ $stagiaire->date_inscription->format('d/m/Y') }}
                                @else
                                    Non renseignée
                                @endif
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bandeau d'aide -->
            <div class="help-banner">
                <div class="d-flex align-items-start gap-3">
                    <div class="header-icon bg-white text-indigo flex-shrink-0">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-white">Besoin de modifier vos informations ?</h6>
                        <p class="text-white-50 mb-0">
                            Pour toute modification de vos informations personnelles, veuillez contacter l'administration
                            via la <a href="{{ route('messages.index') }}" class="text-white text-decoration-underline">messagerie</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.profile-page { font-family: 'Figtree', 'Segoe UI', sans-serif; }

.text-pink { color: #ec4899; }
.text-indigo { color: #4f46e5; }
.min-w-0 { min-width: 0; }

/* ===== Bandeau de couverture ===== */
.profile-cover {
    background: linear-gradient(135deg, #4338ca 0%, #6d28d9 55%, #9333ea 100%);
    min-height: 210px;
    overflow: hidden;
    box-shadow: 0 20px 40px -12px rgba(79, 70, 229, 0.35);
}
.profile-cover-pattern {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255,255,255,0.14) 1px, transparent 1px);
    background-size: 20px 20px;
}
.profile-cover-glow {
    position: absolute;
    top: -60px;
    right: -60px;
    width: 260px;
    height: 260px;
    background: radial-gradient(circle, rgba(255,255,255,0.25), transparent 70%);
    border-radius: 50%;
}

.avatar-wrap { position: relative; }
.profile-avatar {
    width: 132px;
    height: 132px;
    object-fit: cover;
    border: 5px solid #fff;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
}
.avatar-status {
    position: absolute;
    bottom: 6px;
    right: 6px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #22c55e;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    border: 3px solid #fff;
}

.badge-glass {
    display: inline-flex;
    align-items: center;
    background: rgba(255,255,255,0.18);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,0.3);
    color: #fff;
    padding: 5px 14px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* ===== Chips statistiques ===== */
.quick-stat {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fff;
    border-radius: 16px;
    padding: 16px;
    height: 100%;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 8px 20px -12px rgba(15, 23, 42, 0.15);
    transition: transform .18s ease, box-shadow .18s ease;
}
.quick-stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 24px -10px rgba(15, 23, 42, 0.2);
}
.quick-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}

/* ===== Cartes ===== */
.profile-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 10px 25px -15px rgba(15, 23, 42, 0.15);
    overflow: hidden;
}
.profile-card .card-header {
    background: #fff;
    border-bottom: 1px solid #f1f5f9;
    padding: 16px 20px;
}
.profile-card .card-body {
    padding: 20px;
}

.header-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 9px;
    margin-right: 8px;
    font-size: 13px;
    vertical-align: middle;
}

/* ===== Lignes d'information ===== */
.info-line {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
}
.info-line-icon {
    width: 40px;
    height: 40px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 14px;
}

.badge-soft {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
}

/* ===== Palette d'icônes ===== */
.icon-indigo  { background: #eef2ff; color: #4f46e5; }
.icon-cyan    { background: #ecfeff; color: #0891b2; }
.icon-amber   { background: #fffbeb; color: #d97706; }
.icon-emerald { background: #ecfdf5; color: #059669; }
.icon-rose    { background: #fff1f2; color: #e11d48; }

/* ===== Bandeau d'aide ===== */
.help-banner {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 16px;
    padding: 22px 24px;
    box-shadow: 0 10px 25px -15px rgba(15, 23, 42, 0.4);
}
</style>
@endpush
@endsection