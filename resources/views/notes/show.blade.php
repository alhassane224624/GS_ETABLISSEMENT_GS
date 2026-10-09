@extends('layouts.app')

@section('title', 'Détails de la Note')
@section('page-title', 'Détails de la Note')

@section('content')
<div class="container-fluid py-4">
    <!-- En-tête avec breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('notes.index') }}">Notes</a></li>
            <li class="breadcrumb-item active">Détails</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Colonne principale -->
        <div class="col-lg-8">
            <!-- Carte Note Principale -->
            <div class="card shadow-lg border-0 mb-4">
                <!-- En-tête avec gradient -->
                <div class="card-header text-white position-relative overflow-hidden" 
                     style="background: linear-gradient(135deg, {{ $note->note >= 16 ? '#10b981' : ($note->note >= 10 ? '#3b82f6' : '#ef4444') }} 0%, {{ $note->note >= 16 ? '#059669' : ($note->note >= 10 ? '#2563eb' : '#dc2626') }} 100%); min-height: 200px;">
                    <div class="position-absolute top-0 end-0 p-3">
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('notes.index') }}">
                                    <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                                </a></li>
                                @if(Auth::user()->isProfesseur() && $note->created_by === Auth::id())
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('notes.edit', $note) }}">
                                        <i class="fas fa-edit me-2 text-warning"></i>Modifier
                                    </a></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteNote({{ $note->id }})">
                                        <i class="fas fa-trash me-2"></i>Supprimer
                                    </a></li>
                                @endif
                            </ul>
                        </div>
                    </div>
                    
                    <div class="text-center py-4">
                        <p class="text-white-50 small text-uppercase mb-2 fw-bold">Note Obtenue</p>
                        <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                            <h1 class="display-1 fw-bold mb-0">{{ number_format($note->note, 2) }}</h1>
                            <div class="text-start">
                                <h3 class="mb-0 text-white-50">/{{ $note->note_sur }}</h3>
                                <small class="text-white-50">({{ number_format($note->note_sur_20, 2) }}/20)</small>
                            </div>
                        </div>
                        
                        @php
                            $appreciationIcons = [
                                'Excellent' => 'fa-trophy',
                                'Très bien' => 'fa-star',
                                'Bien' => 'fa-thumbs-up',
                                'Assez bien' => 'fa-check-circle',
                                'Passable' => 'fa-minus-circle',
                                'Insuffisant' => 'fa-times-circle'
                            ];
                            $icon = $appreciationIcons[$note->appreciation] ?? 'fa-award';
                        @endphp
                        
                        <span class="badge bg-white text-dark fs-5 px-4 py-2">
                            <i class="fas {{ $icon }} me-2"></i>{{ $note->appreciation }}
                        </span>
                        
                        @if($note->note >= 10)
                            <div class="mt-3">
                                <span class="badge bg-success bg-opacity-25 text-white border border-white">
                                    <i class="fas fa-check-circle me-1"></i>Note Validée
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Informations du stagiaire -->
                <div class="card-body bg-light border-bottom">
                    <h5 class="mb-3 text-primary">
                        <i class="fas fa-user-graduate me-2"></i>Informations du Stagiaire
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                @if($note->stagiaire->photo)
                                    <img src="{{ asset('storage/' . $note->stagiaire->photo) }}" 
                                         class="rounded-circle me-3" width="50" height="50" alt="Photo">
                                @else
                                    <div class="rounded-circle me-3 d-flex align-items-center justify-content-center"
                                         style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                        <i class="fas fa-user text-white"></i>
                                    </div>
                                @endif
                                <div>
                                    <small class="text-muted d-block">Nom complet</small>
                                    <strong>{{ $note->stagiaire->nom }} {{ $note->stagiaire->prenom }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Matricule</small>
                            <strong class="text-primary">
                                <i class="fas fa-id-card me-1"></i>{{ $note->stagiaire->matricule }}
                            </strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Classe</small>
                            <span class="badge bg-secondary fs-6">{{ $note->classe->nom ?? 'Non définie' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Détails de l'évaluation -->
                <div class="card-body">
                    <h5 class="mb-4 text-success">
                        <i class="fas fa-clipboard-list me-2"></i>Détails de l'Évaluation
                    </h5>
                    
                    <div class="row g-4">
                        <!-- Matière -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-start p-3 bg-light rounded-3">
                                <div class="flex-shrink-0">
                                    <div class="icon-box bg-success bg-opacity-10 text-success">
                                        <i class="fas fa-book"></i>
                                    </div>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <small class="text-muted d-block">Matière</small>
                                    <h6 class="mb-1">{{ $note->matiere->nom }}</h6>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-star"></i> Coef: {{ $note->matiere->coefficient }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Type de note -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-start p-3 bg-light rounded-3">
                                <div class="flex-shrink-0">
                                    <div class="icon-box bg-primary bg-opacity-10 text-primary">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <small class="text-muted d-block">Type d'évaluation</small>
                                    <h6 class="mb-1">{{ $note->type_note_libelle }}</h6>
                                    @php
                                        $typeColors = [
                                            'examen' => 'danger',
                                            'ds' => 'warning',
                                            'cc' => 'info',
                                            'tp' => 'success',
                                            'projet' => 'primary'
                                        ];
                                        $color = $typeColors[$note->type_note] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ strtoupper($note->type_note) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Période -->
                        @if($note->periode)
                        <div class="col-md-6">
                            <div class="d-flex align-items-start p-3 bg-light rounded-3">
                                <div class="flex-shrink-0">
                                    <div class="icon-box bg-info bg-opacity-10 text-info">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <small class="text-muted d-block">Période</small>
                                    <h6 class="mb-1">{{ $note->periode->nom }}</h6>
                                    @if($note->periode->date_debut && $note->periode->date_fin)
                                    <small class="text-muted">
                                        {{ $note->periode->date_debut->format('d/m/Y') }} - 
                                        {{ $note->periode->date_fin->format('d/m/Y') }}
                                    </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Professeur -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-start p-3 bg-light rounded-3">
                                <div class="flex-shrink-0">
                                    <div class="icon-box bg-warning bg-opacity-10 text-warning">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                    </div>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <small class="text-muted d-block">Saisi par</small>
                                    <h6 class="mb-1">{{ $note->creator->name ?? 'Utilisateur supprimé' }}</h6>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ $note->created_at->format('d/m/Y à H:i') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Commentaire -->
                    @if($note->commentaire)
                    <div class="mt-4 pt-4 border-top">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-comment-dots me-2"></i>Commentaire du professeur
                        </h6>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-quote-left me-2"></i>
                            <em>{{ $note->commentaire }}</em>
                            <i class="fas fa-quote-right ms-2"></i>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Historique -->
                @if($note->updated_at != $note->created_at)
                <div class="card-footer bg-light text-muted small">
                    <i class="fas fa-history me-2"></i>
                    Dernière modification: {{ $note->updated_at->format('d/m/Y à H:i') }}
                    <span class="badge bg-secondary ms-2">{{ $note->updated_at->diffForHumans() }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Colonne latérale -->
        <div class="col-lg-4">
            <!-- Actions rapides -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Actions Rapides
                    </h6>
                </div>
                <div class="card-body p-0">
                    <a href="{{ route('stagiaires.show', $note->stagiaire) }}" 
                       class="d-flex align-items-center p-3 border-bottom text-decoration-none text-dark hover-bg-light">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary me-3">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="flex-grow-1">
                            <strong class="d-block">Profil du stagiaire</strong>
                            <small class="text-muted">{{ $note->stagiaire->nom }} {{ $note->stagiaire->prenom }}</small>
                        </div>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </a>

                    <a href="{{ route('notes.releve', $note->stagiaire) }}" 
                       class="d-flex align-items-center p-3 border-bottom text-decoration-none text-dark hover-bg-light">
                        <div class="icon-box bg-success bg-opacity-10 text-success me-3">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="flex-grow-1">
                            <strong class="d-block">Relevé de notes</strong>
                            <small class="text-muted">Toutes les notes du stagiaire</small>
                        </div>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </a>

                    @if(Auth::user()->isProfesseur() && $note->created_by === Auth::id())
                    <a href="{{ route('notes.edit', $note) }}" 
                       class="d-flex align-items-center p-3 text-decoration-none text-dark hover-bg-light">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning me-3">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="flex-grow-1">
                            <strong class="d-block">Modifier la note</strong>
                            <small class="text-muted">Corriger ou mettre à jour</small>
                        </div>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </a>
                    @endif
                </div>
            </div>

            <!-- Statistiques de la matière -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Statistiques
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Progression</small>
                            <strong>{{ number_format(($note->note_sur_20 / 20) * 100, 0) }}%</strong>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-{{ $note->note >= 16 ? 'success' : ($note->note >= 10 ? 'primary' : 'danger') }}" 
                                 style="width: {{ ($note->note_sur_20 / 20) * 100 }}%"></div>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <div class="row text-center">
                            <div class="col-6 border-end">
                                <i class="fas fa-book text-primary mb-2 d-block"></i>
                                <h4 class="mb-0">{{ $note->matiere->coefficient }}</h4>
                                <small class="text-muted">Coefficient</small>
                            </div>
                            <div class="col-6">
                                <i class="fas fa-calculator text-success mb-2 d-block"></i>
                                <h4 class="mb-0">{{ number_format($note->note_sur_20 * $note->matiere->coefficient, 2) }}</h4>
                                <small class="text-muted">Points</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guide des appréciations -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Barème d'Appréciation
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-trophy text-success me-2"></i>Excellent</span>
                            <span class="badge bg-success">≥ 16/20</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-star text-info me-2"></i>Très bien</span>
                            <span class="badge bg-info">14-16</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-thumbs-up text-primary me-2"></i>Bien</span>
                            <span class="badge bg-primary">12-14</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-check-circle text-warning me-2"></i>Assez bien</span>
                            <span class="badge bg-warning text-dark">10-12</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-times-circle text-danger me-2"></i>Insuffisant</span>
                            <span class="badge bg-danger">< 10</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmation de suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-trash-alt text-danger" style="font-size: 3rem;"></i>
                </div>
                <h5>Êtes-vous sûr de vouloir supprimer cette note ?</h5>
                <p class="text-muted">
                    Cette action est irréversible. La note sera définitivement supprimée de la base de données.
                </p>
                <div class="alert alert-warning">
                    <strong>Note concernée :</strong> {{ number_format($note->note, 2) }}/{{ $note->note_sur }} en {{ $note->matiere->nom }}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Confirmer la suppression
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .icon-box {
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        font-size: 1.2rem;
    }
    
    .hover-bg-light:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }
    
    .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .card:hover {
        transform: translateY(-2px);
    }
    
    @media print {
        .breadcrumb,
        .dropdown,
        .card-footer,
        .col-lg-4 {
            display: none !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
function deleteNote(noteId) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const form = document.getElementById('deleteForm');
    form.action = `/notes/${noteId}`;
    modal.show();
}
</script>
@endpush