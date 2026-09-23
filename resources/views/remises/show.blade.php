@extends(auth()->user()->role === 'comptable' ? 'layouts.comptable' : 'layouts.app')
@section('title', 'Détails de la Remise')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-gift"></i> Détails de la Remise #{{ $remise->id }}
                    </h5>
                    <div>
                        @if($remise->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Informations principales -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-info-circle"></i> Informations de la Remise
                            </h6>
                            
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Titre:</th>
                                    <td><strong>{{ $remise->titre }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Type:</th>
                                    <td>
                                        @if($remise->type === 'pourcentage')
                                            <span class="badge bg-info">Pourcentage</span>
                                        @else
                                            <span class="badge bg-primary">Montant Fixe</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Valeur:</th>
                                    <td>
                                        @if($remise->type === 'pourcentage')
                                            <strong class="text-success fs-5">{{ $remise->valeur }}%</strong>
                                        @else
                                            <strong class="text-success fs-5">{{ number_format($remise->valeur, 2) }} DH</strong>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Date de début:</th>
                                    <td>{{ \Carbon\Carbon::parse($remise->date_debut)->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Date de fin:</th>
                                    <td>
                                        @if($remise->date_fin)
                                            {{ \Carbon\Carbon::parse($remise->date_fin)->format('d/m/Y') }}
                                        @else
                                            <em class="text-muted">Pas de date de fin</em>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Statut:</th>
                                    <td>
                                        @if($remise->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-user-graduate"></i> Informations du Stagiaire
                            </h6>
                            
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Nom complet:</th>
                                    <td>
                                        <strong>{{ $remise->stagiaire->nom }} {{ $remise->stagiaire->prenom }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <th>CIN:</th>
                                    <td>{{ $remise->stagiaire->cin ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Filière:</th>
                                    <td>{{ $remise->stagiaire->filiere->nom ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Classe:</th>
                                    <td>{{ $remise->stagiaire->classe->nom ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Téléphone:</th>
                                    <td>{{ $remise->stagiaire->telephone ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td>{{ $remise->stagiaire->email ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Motif -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-comment-alt"></i> Motif de la Remise
                        </h6>
                        <div class="alert alert-light border">
                            {{ $remise->motif }}
                        </div>
                    </div>

                    <!-- Informations de création -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-info"></i> Informations Système
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <small class="text-muted d-block">Créé par</small>
                                        <strong>{{ $remise->createur->name ?? 'N/A' }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($remise->created_at)->format('d/m/Y à H:i') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <small class="text-muted d-block">Dernière modification</small>
                                        <strong>{{ \Carbon\Carbon::parse($remise->updated_at)->format('d/m/Y à H:i') }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            Il y a {{ \Carbon\Carbon::parse($remise->updated_at)->diffForHumans() }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calcul d'exemple -->
                    @if($remise->is_active)
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-calculator"></i> Exemple de Calcul
                            </h6>
                            <div class="alert alert-info">
                                @if($remise->type === 'pourcentage')
                                    <p class="mb-0">
                                        <strong>Pour un montant de 1000 DH :</strong><br>
                                        Remise = 1000 DH × {{ $remise->valeur }}% = <strong>{{ number_format(1000 * $remise->valeur / 100, 2) }} DH</strong><br>
                                        Montant après remise = <strong>{{ number_format(1000 - (1000 * $remise->valeur / 100), 2) }} DH</strong>
                                    </p>
                                @else
                                    <p class="mb-0">
                                        <strong>Pour un montant de 1000 DH :</strong><br>
                                        Remise = <strong>{{ number_format($remise->valeur, 2) }} DH</strong><br>
                                        Montant après remise = <strong>{{ number_format(1000 - $remise->valeur, 2) }} DH</strong>
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="{{ route('remises.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour à la liste
                        </a>
                        
                        <div class="btn-group">
                            <a href="{{ route('remises.edit', $remise) }}" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            
                            <form action="{{ route('remises.toggle', $remise) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-{{ $remise->is_active ? 'secondary' : 'success' }}">
                                    <i class="fas fa-{{ $remise->is_active ? 'times' : 'check' }}"></i>
                                    {{ $remise->is_active ? 'Désactiver' : 'Activer' }}
                                </button>
                            </form>
                            
                            <form action="{{ route('remises.destroy', $remise) }}" method="POST" class="d-inline" 
                                onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette remise ? Cette action est irréversible.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection