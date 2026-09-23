@extends(auth()->user()->role === 'comptable' ? 'layouts.comptable' : 'layouts.app')

@section('title', 'Gestion des Paiements')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-money-bill-wave me-2"></i> Gestion des Paiements</h2>
        <a href="{{ route('paiements.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Enregistrer un paiement
        </a>
    </div>

    {{-- Statistiques --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Encaissé</h6>
                            <h3 class="mb-0">{{ number_format($stats['total_paiements'], 2) }} DH</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="fas fa-clock fa-2x text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">En Attente</h6>
                            <h3 class="mb-0">{{ $stats['en_attente'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-calendar-check fa-2x text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Ce Mois</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 p-3 rounded">
                                <i class="fas fa-times-circle fa-2x text-danger"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Refusés</h6>
                            <h3 class="mb-0">{{ $stats['refuses'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('paiements.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Recherche</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="N° transaction, nom..." 
                           value="{{ request('search') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-select">
                        <option value="">Tous</option>
                        <option value="en_attente" {{ request('statut') == 'en_attente' ? 'selected' : '' }}>En attente</option>
                        <option value="valide" {{ request('statut') == 'valide' ? 'selected' : '' }}>Validé</option>
                        <option value="refuse" {{ request('statut') == 'refuse' ? 'selected' : '' }}>Refusé</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type_paiement" class="form-select">
                        <option value="">Tous</option>
                        <option value="inscription" {{ request('type_paiement') == 'inscription' ? 'selected' : '' }}>Inscription</option>
                        <option value="mensualite" {{ request('type_paiement') == 'mensualite' ? 'selected' : '' }}>Mensualité</option>
                        <option value="examen" {{ request('type_paiement') == 'examen' ? 'selected' : '' }}>Examen</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Méthode</label>
                    <select name="methode_paiement" class="form-select">
                        <option value="">Toutes</option>
                        <option value="especes" {{ request('methode_paiement') == 'especes' ? 'selected' : '' }}>Espèces</option>
                        <option value="virement" {{ request('methode_paiement') == 'virement' ? 'selected' : '' }}>Virement</option>
                        <option value="cheque" {{ request('methode_paiement') == 'cheque' ? 'selected' : '' }}>Chèque</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-search me-2"></i>Filtrer
                        </button>
                        <a href="{{ route('paiements.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Liste des paiements --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>N° Transaction</th>
                            <th>Date</th>
                            <th>Stagiaire</th>
                            <th>Type</th>
                            <th>Montant</th>
                            <th>Méthode</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paiements as $paiement)
                        <tr>
                            <td>
                                <strong class="text-primary">{{ $paiement->numero_transaction }}</strong>
                            </td>
                            <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                            <td>
                                <div>
                                    <strong>{{ $paiement->stagiaire->nom_complet }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $paiement->stagiaire->matricule }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $paiement->type_libelle }}</span>
                            </td>
                            <td>
                                <strong class="text-success">{{ number_format($paiement->montant, 2) }} DH</strong>
                            </td>
                            <td>{{ $paiement->methode_libelle }}</td>
                            <td>
                                @if($paiement->statut === 'valide')
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>{{ $paiement->statut_libelle }}
                                    </span>
                                @elseif($paiement->statut === 'en_attente')
                                    <span class="badge bg-warning">
                                        <i class="fas fa-clock me-1"></i>{{ $paiement->statut_libelle }}
                                    </span>
                                @else
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times-circle me-1"></i>{{ $paiement->statut_libelle }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('paiements.show', $paiement) }}" 
                                       class="btn btn-outline-primary" 
                                       title="Détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    @if($paiement->statut === 'valide' && $paiement->recu_path)
                                        <a href="{{ route('paiements.recu', $paiement) }}" 
                                           class="btn btn-outline-success" 
                                           title="Télécharger reçu">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    @endif
                                    
                                    @if($paiement->statut === 'en_attente')
                                        <button type="button" 
                                                class="btn btn-outline-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#validerModal{{ $paiement->id }}"
                                                title="Valider">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        
                                        <button type="button" 
                                                class="btn btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#refuserModal{{ $paiement->id }}"
                                                title="Refuser">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </div>

                                {{-- Modal Valider --}}
                                @if($paiement->statut === 'en_attente')
                                <div class="modal fade" id="validerModal{{ $paiement->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('paiements.valider', $paiement) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Valider le paiement</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Confirmez-vous la validation de ce paiement ?</p>
                                                    <ul class="list-unstyled">
                                                        <li><strong>Montant :</strong> {{ number_format($paiement->montant, 2) }} DH</li>
                                                        <li><strong>Stagiaire :</strong> {{ $paiement->stagiaire->nom_complet }}</li>
                                                        <li><strong>Méthode :</strong> {{ $paiement->methode_libelle }}</li>
                                                    </ul>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Notes (optionnel)</label>
                                                        <textarea name="notes_admin" class="form-control" rows="2"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="fas fa-check me-2"></i>Valider
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                {{-- Modal Refuser --}}
                                <div class="modal fade" id="refuserModal{{ $paiement->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('paiements.refuser', $paiement) }}">
                                                @csrf
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title">Refuser le paiement</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="alert alert-warning">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        Cette action notifiera le stagiaire.
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Motif du refus <span class="text-danger">*</span></label>
                                                        <textarea name="motif_refus" class="form-control" rows="3" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="fas fa-times me-2"></i>Refuser
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aucun paiement trouvé</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $paiements->links() }}
            </div>
        </div>
    </div>
</div>
@endsection