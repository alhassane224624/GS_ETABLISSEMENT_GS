@extends(auth()->user()->role === 'comptable' ? 'layouts.comptable' : 'layouts.app')

@section('title', 'Modifier un Échéancier')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> Modifier l'Échéancier #{{ $echeancier->id }}
                    </h5>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Info Stagiaire (non modifiable) -->
                    <div class="mb-3">
                        <label class="form-label">Stagiaire</label>
                        <div class="alert alert-info">
                            <strong>{{ $echeancier->stagiaire->nom }} {{ $echeancier->stagiaire->prenom }}</strong><br>
                            <small>{{ $echeancier->stagiaire->filiere->nom ?? 'N/A' }}</small>
                        </div>
                        <small class="text-muted">Le stagiaire ne peut pas être modifié</small>
                    </div>

                    <!-- Info Année Scolaire (non modifiable) -->
                    <div class="mb-3">
                        <label class="form-label">Année Scolaire</label>
                        <div class="alert alert-secondary">
                            <strong>{{ $echeancier->anneeScolaire->nom ?? 'N/A' }}</strong>
                        </div>
                        <small class="text-muted">L'année scolaire ne peut pas être modifiée</small>
                    </div>

                    @if($echeancier->montant_paye > 0)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Attention :</strong> Cet échéancier a déjà reçu des paiements ({{ number_format($echeancier->montant_paye, 2) }} DH). 
                            Le montant ne peut pas être inférieur au montant déjà payé.
                        </div>
                    @endif

                    <form action="{{ route('echeanciers.update', $echeancier) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Titre -->
                        <div class="mb-3">
                            <label for="titre" class="form-label required">Titre de l'échéancier</label>
                            <input type="text" name="titre" id="titre" 
                                class="form-control @error('titre') is-invalid @enderror" 
                                value="{{ old('titre', $echeancier->titre) }}" 
                                required>
                            @error('titre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Montant -->
                        <div class="mb-3">
                            <label for="montant" class="form-label required">Montant (DH)</label>
                            <input type="number" name="montant" id="montant" 
                                class="form-control @error('montant') is-invalid @enderror" 
                                value="{{ old('montant', $echeancier->montant) }}" 
                                step="0.01" 
                                min="{{ $echeancier->montant_paye }}" 
                                required>
                            <small class="text-muted">
                                Montant minimum : {{ number_format($echeancier->montant_paye, 2) }} DH (déjà payé)
                            </small>
                            @error('montant')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Date d'échéance -->
                        <div class="mb-3">
                            <label for="date_echeance" class="form-label required">Date d'échéance</label>
                            <input type="date" name="date_echeance" id="date_echeance" 
                                class="form-control @error('date_echeance') is-invalid @enderror" 
                                value="{{ old('date_echeance', $echeancier->date_echeance) }}" 
                                required>
                            @error('date_echeance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Info récapitulative -->
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title">Récapitulatif</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <th width="50%">Montant payé :</th>
                                        <td class="text-success">{{ number_format($echeancier->montant_paye, 2) }} DH</td>
                                    </tr>
                                    <tr>
                                        <th>Montant restant actuel :</th>
                                        <td class="text-danger">{{ number_format($echeancier->montant_restant, 2) }} DH</td>
                                    </tr>
                                    <tr>
                                        <th>Statut :</th>
                                        <td>
                                            @if($echeancier->statut === 'paye')
                                                <span class="badge bg-success">Payé</span>
                                            @elseif($echeancier->statut === 'paye_partiel')
                                                <span class="badge bg-warning">Payé Partiellement</span>
                                            @elseif($echeancier->statut === 'en_retard')
                                                <span class="badge bg-danger">En Retard</span>
                                            @else
                                                <span class="badge bg-secondary">Impayé</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('echeanciers.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Retour
                            </a>
                            <button type="submit" class="btn btn-warning text-white">
                                <i class="fas fa-save"></i> Mettre à jour
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection