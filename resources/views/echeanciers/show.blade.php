@extends(auth()->user()->role === 'comptable' ? 'layouts.comptable' : 'layouts.app')

@section('title', 'Détails de l\'Échéancier')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-check"></i> Détails de l'Échéancier #{{ $echeancier->id }}
                    </h5>
                    <div>
                        @if($echeancier->statut === 'paye')
                            <span class="badge bg-success">Payé</span>
                        @elseif($echeancier->statut === 'paye_partiel')
                            <span class="badge bg-warning">Payé Partiellement</span>
                        @elseif($echeancier->statut === 'en_retard')
                            <span class="badge bg-danger">En Retard</span>
                        @else
                            <span class="badge bg-secondary">Impayé</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Informations principales -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-info-circle"></i> Informations de l'Échéancier
                            </h6>
                            
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Titre:</th>
                                    <td><strong>{{ $echeancier->titre }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Année Scolaire:</th>
                                    <td>{{ $echeancier->anneeScolaire->nom ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Date d'échéance:</th>
                                    <td>
                                        {{ \Carbon\Carbon::parse($echeancier->date_echeance)->format('d/m/Y') }}
                                        @if(\Carbon\Carbon::parse($echeancier->date_echeance)->isPast() && $echeancier->statut !== 'paye')
                                            <br><span class="badge bg-danger mt-1">En retard de {{ \Carbon\Carbon::parse($echeancier->date_echeance)->diffInDays(now()) }} jours</span>
                                        @elseif(\Carbon\Carbon::parse($echeancier->date_echeance)->isFuture())
                                            <br><span class="badge bg-info mt-1">Dans {{ \Carbon\Carbon::parse($echeancier->date_echeance)->diffInDays(now()) }} jours</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Montant Total:</th>
                                    <td><strong class="text-primary fs-5">{{ number_format($echeancier->montant, 2) }} DH</strong></td>
                                </tr>
                                <tr>
                                    <th>Montant Payé:</th>
                                    <td><strong class="text-success">{{ number_format($echeancier->montant_paye, 2) }} DH</strong></td>
                                </tr>
                                <tr>
                                    <th>Montant Restant:</th>
                                    <td><strong class="text-danger">{{ number_format($echeancier->montant_restant, 2) }} DH</strong></td>
                                </tr>
                                <tr>
                                    <th>Progression:</th>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            @php
                                                $progression = $echeancier->montant > 0 ? ($echeancier->montant_paye / $echeancier->montant) * 100 : 0;
                                            @endphp
                                            <div class="progress-bar {{ $progression == 100 ? 'bg-success' : 'bg-warning' }}" 
                                                role="progressbar" 
                                                style="width: {{ $progression }}%" 
                                                aria-valuenow="{{ $progression }}" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                                {{ number_format($progression, 1) }}%
                                            </div>
                                        </div>
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
                                        <strong>{{ $echeancier->stagiaire->nom }} {{ $echeancier->stagiaire->prenom }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <th>CIN:</th>
                                    <td>{{ $echeancier->stagiaire->cin ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Filière:</th>
                                    <td>{{ $echeancier->stagiaire->filiere->nom ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Classe:</th>
                                    <td>{{ $echeancier->stagiaire->classe->nom ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Téléphone:</th>
                                    <td>{{ $echeancier->stagiaire->telephone ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td>{{ $echeancier->stagiaire->email ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Solde Actuel:</th>
                                    <td>
                                        @if($echeancier->stagiaire->solde_paiement < 0)
                                            <span class="badge bg-danger">{{ number_format(abs($echeancier->stagiaire->solde_paiement), 2) }} DH (Dû)</span>
                                        @elseif($echeancier->stagiaire->solde_paiement > 0)
                                            <span class="badge bg-success">{{ number_format($echeancier->stagiaire->solde_paiement, 2) }} DH (Crédit)</span>
                                        @else
                                            <span class="badge bg-secondary">0.00 DH</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Historique des paiements -->
                    @if($echeancier->paiements && $echeancier->paiements->count() > 0)
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-history"></i> Historique des Paiements ({{ $echeancier->paiements->count() }})
                        </h6>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Montant</th>
                                        <th>Mode de Paiement</th>
                                        <th>Référence</th>
                                        <th>Enregistré par</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($echeancier->paiements as $paiement)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y H:i') }}</td>
                                        <td><strong class="text-success">{{ number_format($paiement->montant, 2) }} DH</strong></td>
                                        <td>
                                            @if($paiement->mode_paiement === 'espece')
                                                <span class="badge bg-success">Espèce</span>
                                            @elseif($paiement->mode_paiement === 'cheque')
                                                <span class="badge bg-primary">Chèque</span>
                                            @elseif($paiement->mode_paiement === 'virement')
                                                <span class="badge bg-info">Virement</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $paiement->mode_paiement }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $paiement->reference ?? '-' }}</td>
                                        <td>
                                            <small>{{ $paiement->user->name ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('paiements.show', $paiement) }}" class="btn btn-sm btn-info" title="Voir le reçu">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>Total:</th>
                                        <th colspan="5"><strong class="text-success">{{ number_format($echeancier->montant_paye, 2) }} DH</strong></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning mb-4">
                        <i class="fas fa-info-circle"></i> Aucun paiement enregistré pour cet échéancier.
                    </div>
                    @endif

                    <!-- Informations système -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-info"></i> Informations Système
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <small class="text-muted d-block">Créé le</small>
                                        <strong>{{ \Carbon\Carbon::parse($echeancier->created_at)->format('d/m/Y à H:i') }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            Il y a {{ \Carbon\Carbon::parse($echeancier->created_at)->diffForHumans() }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <small class="text-muted d-block">Dernière modification</small>
                                        <strong>{{ \Carbon\Carbon::parse($echeancier->updated_at)->format('d/m/Y à H:i') }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            Il y a {{ \Carbon\Carbon::parse($echeancier->updated_at)->diffForHumans() }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="{{ route('echeanciers.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour à la liste
                        </a>
                        
                        <div class="btn-group">
                            @if($echeancier->statut !== 'paye')
                                <a href="{{ route('paiements.create', ['echeancier_id' => $echeancier->id]) }}" class="btn btn-success">
                                    <i class="fas fa-money-bill-wave"></i> Enregistrer un Paiement
                                </a>
                                <a href="{{ route('echeanciers.edit', $echeancier) }}" class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                            @endif
                            
                            @if($echeancier->montant_paye == 0)
                                <form action="{{ route('echeanciers.destroy', $echeancier) }}" method="POST" class="d-inline" 
                                    onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet échéancier ? Cette action est irréversible.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </form>
                            @endif
                            
                            <a href="{{ route('echeanciers.print', $echeancier) }}" class="btn btn-primary" target="_blank">
                                <i class="fas fa-print"></i> Imprimer
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Autres échéanciers du stagiaire -->
            @php
                $autresEcheanciers = \App\Models\Echeancier::where('stagiaire_id', $echeancier->stagiaire_id)
                    ->where('id', '!=', $echeancier->id)
                    ->orderBy('date_echeance', 'desc')
                    ->limit(5)
                    ->get();
            @endphp

            @if($autresEcheanciers->count() > 0)
            <div class="card shadow mt-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-list"></i> Autres Échéanciers de {{ $echeancier->stagiaire->prenom }}
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Titre</th>
                                    <th>Date Échéance</th>
                                    <th>Montant</th>
                                    <th>Restant</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($autresEcheanciers as $autre)
                                <tr>
                                    <td>{{ $autre->titre }}</td>
                                    <td>{{ \Carbon\Carbon::parse($autre->date_echeance)->format('d/m/Y') }}</td>
                                    <td>{{ number_format($autre->montant, 2) }} DH</td>
                                    <td class="text-danger">{{ number_format($autre->montant_restant, 2) }} DH</td>
                                    <td>
                                        @if($autre->statut === 'paye')
                                            <span class="badge bg-success">Payé</span>
                                        @elseif($autre->statut === 'paye_partiel')
                                            <span class="badge bg-warning">Partiel</span>
                                        @elseif($autre->statut === 'en_retard')
                                            <span class="badge bg-danger">En Retard</span>
                                        @else
                                            <span class="badge bg-secondary">Impayé</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('echeanciers.show', $autre) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="{{ route('echeanciers.index', ['stagiaire_id' => $echeancier->stagiaire_id]) }}" class="btn btn-sm btn-outline-primary">
                            Voir tous les échéanciers de ce stagiaire
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection