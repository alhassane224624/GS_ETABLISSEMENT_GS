@extends('layouts.app-professeur')

@section('title', 'Dashboard Professeur')
@section('page-title', 'Tableau de Bord Professeur')

@section('content')
<div class="container-fluid">
    <!-- En-tête personnalisé avec animations -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white shadow-lg border-0 animate__animated animate__fadeInDown">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h2 class="mb-1 fw-bold">
                                <i class="fas fa-hand-wave me-2"></i>
                                Bienvenue, {{ auth()->user()->name }}
                            </h2>
                            <p class="mb-0 opacity-90">
                                <i class="fas fa-chalkboard-teacher me-2"></i>
                                Professeur - {{ now()->isoFormat('dddd D MMMM YYYY') }}
                            </p>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('professeur.planning') }}" class="btn btn-light btn-sm me-2 hover-lift">
                                <i class="fas fa-calendar me-1"></i> Mon Planning
                            </a>
                            <a href="{{ route('professeur.planning.create') }}" class="btn btn-success btn-sm hover-lift">
                                <i class="fas fa-plus-circle me-1"></i> Créer un Cours
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques principales avec animations -->
    <div class="row g-4 mb-4">
        <!-- Mes Filières -->
        <div class="col-md-6 col-lg-3">
            <a href="#mes-filieres" class="card stat-card-link border-0 shadow-sm h-100 hover-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon bg-purple-gradient">
                                <i class="fas fa-book-open fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small fw-semibold">Mes Filières</p>
                            <h2 class="mb-0 fw-bold counter">{{ $data['filieres']->count() }}</h2>
                            @if($data['filieres']->count() > 0)
                                <span class="stat-card-cta text-success">
                                    <i class="fas fa-check-circle me-1"></i>Actif
                                </span>
                            @else
                                <span class="stat-card-cta text-muted">Voir le détail <i class="fas fa-arrow-right ms-1"></i></span>
                            @endif
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Mes Stagiaires -->
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('professeur.stagiaires') }}" class="card stat-card-link border-0 shadow-sm h-100 hover-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon bg-success-gradient">
                                <i class="fas fa-user-graduate fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small fw-semibold">Mes Stagiaires</p>
                            <h2 class="mb-0 fw-bold counter">{{ $data['total_stagiaires'] }}</h2>
                            <span class="stat-card-cta text-success">
                                Voir tous <i class="fas fa-arrow-right ms-1"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Notes Saisies -->
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('professeur.notes-par-matiere') }}" class="card stat-card-link border-0 shadow-sm h-100 hover-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon bg-warning-gradient">
                                <i class="fas fa-clipboard-check fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small fw-semibold">Notes Saisies</p>
                            <h2 class="mb-0 fw-bold counter">{{ $data['total_notes'] }}</h2>
                            <span class="stat-card-cta text-warning">
                                Consulter <i class="fas fa-arrow-right ms-1"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Absences ce mois -->
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('professeur.presences') }}" class="card stat-card-link border-0 shadow-sm h-100 hover-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon bg-danger-gradient">
                                <i class="fas fa-user-clock fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small fw-semibold">Absences (30j)</p>
                            <h2 class="mb-0 fw-bold counter">{{ $data['total_absences'] }}</h2>
                            <span class="stat-card-cta text-danger">
                                Gérer <i class="fas fa-arrow-right ms-1"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Planning du jour -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm animate__animated animate__fadeIn">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-calendar-day text-primary me-2"></i>
                            Mon Planning du Jour
                            <span class="badge bg-primary-subtle text-primary ms-2">
                                {{ now()->format('d/m/Y') }}
                            </span>
                        </h5>
                        <a href="{{ route('professeur.planning.create') }}" class="btn btn-sm btn-success hover-lift">
                            <i class="fas fa-plus-circle me-1"></i> Ajouter un Cours
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($data['planning_aujourd_hui']->count() > 0)
                        <div class="timeline-container">
                            @foreach($data['planning_aujourd_hui'] as $index => $cours)
                                <div class="timeline-item mb-3 animate__animated animate__fadeInLeft" style="animation-delay: {{ $index * 0.1 }}s">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="time-badge">
                                                <div class="fw-bold">{{ \Carbon\Carbon::parse($cours->heure_debut)->format('H:i') }}</div>
                                                <small>{{ \Carbon\Carbon::parse($cours->heure_fin)->format('H:i') }}</small>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="cours-card border-start border-4 border-primary">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1 fw-bold text-primary">
                                                            <i class="fas fa-book-reader me-1"></i>
                                                            {{ $cours->matiere->nom ?? 'N/A' }}
                                                        </h6>
                                                        <p class="mb-1 text-muted small">
                                                            <i class="fas fa-users me-1"></i>
                                                            {{ $cours->classe->nom ?? 'N/A' }} 
                                                            <span class="mx-1">•</span>
                                                            {{ $cours->classe->filiere->nom ?? 'N/A' }}
                                                        </p>
                                                        <p class="mb-0 text-muted small">
                                                            <i class="fas fa-door-open me-1"></i>
                                                            Salle: <span class="fw-semibold">{{ $cours->salle->nom ?? 'N/A' }}</span>
                                                        </p>
                                                        @if($cours->description)
                                                            <p class="mb-0 mt-2 small text-muted">
                                                                <i class="fas fa-info-circle me-1"></i>
                                                                {{ Str::limit($cours->description, 100) }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                    <div class="text-end ms-3">
                                                        <span class="badge status-badge status-{{ $cours->statut }}">
                                                            @if($cours->statut == 'valide')
                                                                <i class="fas fa-check-circle me-1"></i>
                                                            @elseif($cours->statut == 'annule')
                                                                <i class="fas fa-times-circle me-1"></i>
                                                            @else
                                                                <i class="fas fa-clock me-1"></i>
                                                            @endif
                                                            {{ ucfirst($cours->statut) }}
                                                        </span>
                                                        <div class="mt-2">
                                                            <span class="badge bg-info-subtle text-info">
                                                                {{ ucfirst($cours->type_cours) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state text-center py-5">
                            <div class="empty-icon mb-3">
                                <i class="fas fa-calendar-times fa-4x"></i>
                            </div>
                            <h5 class="text-muted mb-3">Aucun cours prévu aujourd'hui</h5>
                            <p class="text-muted mb-4">Planifiez vos cours pour organiser votre journée</p>
                            <a href="{{ route('professeur.planning.create') }}" class="btn btn-primary hover-lift">
                                <i class="fas fa-plus-circle me-2"></i> Créer mon Premier Cours
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Grille inférieure -->
    <div class="row g-4 mb-4">
        <!-- Mes Filières détails -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100 animate__animated animate__fadeInUp" id="mes-filieres">
                <div class="card-header bg-gradient-light border-0 py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-graduation-cap text-purple me-2"></i>
                        Mes Filières
                    </h5>
                </div>
                <div class="card-body">
                    @if($data['filieres']->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($data['filieres'] as $filiere)
                                <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3 hover-item">
                                    <div>
                                        <div class="d-flex align-items-center">
                                            <div class="filiere-icon me-3">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-semibold">{{ $filiere->nom }}</h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-layer-group me-1"></i>
                                                    {{ $filiere->niveau ?? 'Niveau non défini' }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="badge bg-primary-subtle text-primary px-3 py-2">
                                            <i class="fas fa-users me-1"></i>
                                            {{ $filiere->stagiaires_count }} 
                                            <small>stagiaire{{ $filiere->stagiaires_count > 1 ? 's' : '' }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">Aucune filière assignée</p>
                            <small class="text-muted">Contactez l'administrateur</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Dernières Notes -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100 animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
                <div class="card-header bg-gradient-light border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-star text-warning me-2"></i>
                            Dernières Notes Saisies
                        </h5>
                        <a href="{{ route('professeur.notes-par-matiere') }}" class="btn btn-sm btn-outline-primary">
                            Voir toutes
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($data['recent_notes']->count() > 0)
                        <div class="notes-list">
                            @foreach($data['recent_notes']->take(5) as $note)
                                <div class="note-item d-flex justify-content-between align-items-center py-3 border-bottom hover-item">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <div class="avatar-sm me-2">
                                                <i class="fas fa-user-circle fa-2x text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold">
                                                    {{ $note->stagiaire->nom }} {{ $note->stagiaire->prenom }}
                                                </h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-book me-1"></i>
                                                    {{ $note->matiere->nom ?? 'N/A' }}
                                                    <span class="mx-1">•</span>
                                                    <span class="badge badge-sm bg-secondary-subtle text-secondary">
                                                        {{ strtoupper($note->type_note) }}
                                                    </span>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="small text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            {{ $note->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    <div class="text-end ms-3">
                                        <div class="note-score 
                                            @if($note->note >= 16) text-success
                                            @elseif($note->note >= 14) text-info
                                            @elseif($note->note >= 12) text-warning
                                            @elseif($note->note >= 10) text-orange
                                            @else text-danger
                                            @endif">
                                            <div class="fs-2 fw-bold">{{ number_format($note->note, 2) }}</div>
                                            <small class="text-muted">/20</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state text-center py-4">
                            <i class="fas fa-clipboard fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">Aucune note saisie récemment</p>
                            <a href="{{ route('professeur.stagiaires') }}" class="btn btn-sm btn-outline-primary mt-3">
                                <i class="fas fa-plus me-1"></i> Ajouter une note
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Accès rapides avec animations -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm animate__animated animate__fadeInUp">
                <div class="card-header bg-gradient-light border-0 py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-bolt text-warning me-2"></i>
                        Accès Rapides
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('professeur.stagiaires') }}" class="quick-link-card">
                                <div class="icon-wrapper bg-primary-subtle">
                                    <i class="fas fa-users fa-2x text-primary"></i>
                                </div>
                                <h6 class="mt-3 mb-1">Mes Stagiaires</h6>
                                <small class="text-muted">Consulter la liste</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('professeur.presences') }}" class="quick-link-card">
                                <div class="icon-wrapper bg-success-subtle">
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                </div>
                                <h6 class="mt-3 mb-1">Présences</h6>
                                <small class="text-muted">Marquer absences</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('professeur.notes-par-matiere') }}" class="quick-link-card">
                                <div class="icon-wrapper bg-warning-subtle">
                                    <i class="fas fa-clipboard-list fa-2x text-warning"></i>
                                </div>
                                <h6 class="mt-3 mb-1">Gérer Notes</h6>
                                <small class="text-muted">Saisir et consulter</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('messages.index') }}" class="quick-link-card">
                                <div class="icon-wrapper bg-info-subtle">
                                    <i class="fas fa-envelope fa-2x text-info"></i>
                                </div>
                                <h6 class="mt-3 mb-1">Messages</h6>
                                <small class="text-muted">
                                    @if(method_exists(auth()->user(), 'getUnreadMessagesCount') && auth()->user()->getUnreadMessagesCount() > 0)
                                        <span class="badge bg-danger">{{ auth()->user()->getUnreadMessagesCount() }}</span> nouveaux
                                    @else
                                        Messagerie
                                    @endif
                                </small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<style>
/* Variables de couleurs */
:root {
    --purple: #9333ea;
    --purple-light: #f3e8ff;
    --orange: #ea580c;
}

/* Gradients */
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-light {
    background: linear-gradient(135deg, #f5f7fa 0%, #e8eaf6 100%);
}

/* Icônes statistiques */
.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.bg-purple-gradient {
    background: linear-gradient(135deg, var(--purple) 0%, #c084fc 100%);
}

.bg-success-gradient {
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
}

.bg-warning-gradient {
    background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
}

.bg-danger-gradient {
    background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
}

/* Cartes de statistiques cliquables */
.stat-card-link {
    display: block;
    text-decoration: none;
    color: inherit;
    cursor: pointer;
}
.stat-card-link:hover,
.stat-card-link:focus {
    color: inherit;
    text-decoration: none;
}
.stat-card-link:focus-visible {
    outline: 2px solid #667eea;
    outline-offset: 3px;
}
.stat-card-cta {
    font-size: 0.85rem;
    font-weight: 600;
}

/* Animations au survol */
.hover-card {
    transition: all 0.3s ease;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.hover-item {
    transition: all 0.2s ease;
    cursor: pointer;
}

.hover-item:hover {
    background-color: #f8f9fa;
    border-radius: 10px;
}

/* Timeline moderne */
.time-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 20px;
    border-radius: 12px;
    text-align: center;
    min-width: 80px;
    box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
}

.cours-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.cours-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transform: translateX(5px);
}

/* Status badges */
.status-badge {
    padding: 8px 16px;
    font-weight: 600;
    border-radius: 8px;
    font-size: 0.85rem;
}

.status-valide {
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    color: white;
}

.status-annule {
    background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
    color: white;
}

.status-brouillon {
    background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
    color: white;
}

/* État vide */
.empty-state {
    padding: 40px 20px;
}

.empty-icon {
    color: #cbd5e1;
}

/* Icônes des filières */
.filiere-icon {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, var(--purple) 0%, #c084fc 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

/* Notes scoring */
.note-score {
    text-align: center;
    min-width: 60px;
}

/* Cartes d'accès rapide */
.quick-link-card {
    display: block;
    text-align: center;
    padding: 25px 15px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
}

.quick-link-card:hover {
    border-color: #667eea;
    background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
}

.quick-link-card .icon-wrapper {
    width: 70px;
    height: 70px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    transition: all 0.3s ease;
}

.quick-link-card:hover .icon-wrapper {
    transform: scale(1.1) rotate(5deg);
}

.quick-link-card h6 {
    font-weight: 700;
    color: #1f2937;
}

/* Compteur animé */
.counter {
    display: inline-block;
}

/* Styles supplémentaires */
.text-purple { color: var(--purple); }
.text-orange { color: var(--orange); }
.bg-purple-100 { background-color: var(--purple-light); }

/* Responsive */
@media (max-width: 768px) {
    .time-badge {
        min-width: 60px;
        padding: 8px 12px;
        font-size: 0.85rem;
    }
    
    .cours-card {
        padding: 15px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
    }
    
    .stat-icon i {
        font-size: 1.5rem !important;
    }
}

/* Animation de pulsation pour les compteurs */
@keyframes pulse-scale {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.counter:hover {
    animation: pulse-scale 0.5s ease-in-out;
}

/* Scrollbar personnalisée */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

/* Badge moderne */
.badge {
    font-weight: 600;
    padding: 6px 12px;
}

/* Avatar */
.avatar-sm {
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Effets de lumière */
.card {
    position: relative;
    overflow: hidden;
}

.card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(
        45deg,
        transparent,
        rgba(255, 255, 255, 0.1),
        transparent
    );
    transform: rotate(45deg);
    transition: all 0.5s ease;
    opacity: 0;
}

.card:hover::before {
    opacity: 1;
    animation: shine 1.5s ease-in-out;
}

@keyframes shine {
    0% { left: -50%; }
    100% { left: 150%; }
}

/* Notes list styles */
.notes-list {
    max-height: 400px;
    overflow-y: auto;
}

.note-item {
    transition: all 0.2s ease;
}

.note-item:hover {
    background-color: #f8fafc;
    padding-left: 10px;
}

.note-item:last-child {
    border-bottom: none !important;
}

/* Timeline container */
.timeline-container {
    position: relative;
}

.timeline-item {
    position: relative;
}

/* Amélioration des badges de statut */
.badge-sm {
    font-size: 0.7rem;
    padding: 4px 8px;
}

/* Styles pour les couleurs de texte des notes */
.text-info { color: #3b82f6 !important; }
.text-orange { color: var(--orange) !important; }

/* Animation pour l'icône de la main */
@keyframes wave {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(20deg); }
    75% { transform: rotate(-20deg); }
}

.fa-hand-wave {
    display: inline-block;
    animation: wave 1s ease-in-out 2;
}

/* Amélioration du header gradient */
.card.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.4);
}

/* Bordures colorées pour les cours */
.border-primary { border-color: #667eea !important; }

/* Hover effect sur les liens */
a {
    transition: all 0.2s ease;
}

/* Amélioration des tooltips */
[data-bs-toggle="tooltip"] {
    cursor: help;
}

/* Style pour le badge de messages non lus */
.badge.bg-danger {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.1);
    }
}

/* Loading state */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Print styles */
@media print {
    .btn, .card-header, .quick-link-card {
        display: none !important;
    }
    
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation des compteurs
    const counters = document.querySelectorAll('.counter');
    
    counters.forEach(counter => {
        const target = parseInt(counter.innerText);
        const duration = 1000;
        const step = target / (duration / 16);
        let current = 0;
        
        const updateCounter = () => {
            current += step;
            if (current < target) {
                counter.innerText = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                counter.innerText = target;
            }
        };
        
        // Observer pour démarrer l'animation quand visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCounter();
                    observer.unobserve(entry.target);
                }
            });
        });
        
        observer.observe(counter);
    });
    
    // Effet de survol sur les cartes de cours
    const coursCards = document.querySelectorAll('.cours-card');
    coursCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
    
    // Tooltip Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Confirmation avant suppression
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-refresh pour les messages non lus
    @if(method_exists(auth()->user(), 'getUnreadMessagesCount'))
    setInterval(function() {
        fetch('{{ route("messages.unread-count") }}')
            .then(response => response.json())
            .then(data => {
                const badges = document.querySelectorAll('.message-count-badge');
                badges.forEach(badge => {
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                });
            })
            .catch(error => console.log('Erreur lors de la récupération des messages:', error));
    }, 30000); // Toutes les 30 secondes
    @endif
});

// Animation de chargement
window.addEventListener('beforeunload', function() {
    document.body.classList.add('loading');
});
</script>
@endpush
@endsection