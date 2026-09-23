@extends(auth()->user()->role === 'comptable' ? 'layouts.comptable' : 'layouts.app')
@section('title', 'Modifier une Remise')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> Modifier la Remise #{{ $remise->id }}
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

                    <form action="{{ route('remises.update', $remise) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Info Stagiaire (non modifiable) -->
                        <div class="mb-3">
                            <label class="form-label">Stagiaire</label>
                            <div class="alert alert-info">
                                <strong>{{ $remise->stagiaire->nom }} {{ $remise->stagiaire->prenom }}</strong><br>
                                <small>{{ $remise->stagiaire->filiere->nom ?? 'N/A' }}</small>
                            </div>
                            <small class="text-muted">Le stagiaire ne peut pas être modifié</small>
                        </div>

                        <!-- Titre -->
                        <div class="mb-3">
                            <label for="titre" class="form-label required">Titre de la remise</label>
                            <input type="text" name="titre" id="titre" 
                                class="form-control @error('titre') is-invalid @enderror" 
                                value="{{ old('titre', $remise->titre) }}" 
                                required>
                            @error('titre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Type et Valeur -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label required">Type de remise</label>
                                <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="">-- Sélectionner --</option>
                                    <option value="pourcentage" {{ old('type', $remise->type) == 'pourcentage' ? 'selected' : '' }}>Pourcentage (%)</option>
                                    <option value="montant_fixe" {{ old('type', $remise->type) == 'montant_fixe' ? 'selected' : '' }}>Montant Fixe (DH)</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="valeur" class="form-label required">Valeur</label>
                                <input type="number" name="valeur" id="valeur" 
                                    class="form-control @error('valeur') is-invalid @enderror" 
                                    value="{{ old('valeur', $remise->valeur) }}" 
                                    step="0.01" 
                                    min="0" 
                                    required>
                                <small class="form-text text-muted" id="valeur-hint">
                                    @if($remise->type === 'pourcentage')
                                        Entrez un pourcentage entre 0 et 100
                                    @else
                                        Entrez un montant en DH
                                    @endif
                                </small>
                                @error('valeur')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Motif -->
                        <div class="mb-3">
                            <label for="motif" class="form-label required">Motif de la remise</label>
                            <textarea name="motif" id="motif" 
                                class="form-control @error('motif') is-invalid @enderror" 
                                rows="3" 
                                required>{{ old('motif', $remise->motif) }}</textarea>
                            @error('motif')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Dates -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date_debut" class="form-label required">Date de début</label>
                                <input type="date" name="date_debut" id="date_debut" 
                                    class="form-control @error('date_debut') is-invalid @enderror" 
                                    value="{{ old('date_debut', $remise->date_debut) }}" 
                                    required>
                                @error('date_debut')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="date_fin" class="form-label">Date de fin (optionnel)</label>
                                <input type="date" name="date_fin" id="date_fin" 
                                    class="form-control @error('date_fin') is-invalid @enderror" 
                                    value="{{ old('date_fin', $remise->date_fin) }}">
                                <small class="form-text text-muted">Laissez vide si pas de date de fin</small>
                                @error('date_fin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Statut -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" 
                                    class="form-check-input" 
                                    value="1" 
                                    {{ old('is_active', $remise->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Remise active
                                </label>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('remises.index') }}" class="btn btn-secondary">
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

@push('scripts')
<script>
    // Mise à jour du hint selon le type sélectionné
    document.getElementById('type').addEventListener('change', function() {
        const hint = document.getElementById('valeur-hint');
        const valeurInput = document.getElementById('valeur');
        
        if (this.value === 'pourcentage') {
            hint.textContent = 'Entrez un pourcentage entre 0 et 100';
            valeurInput.max = 100;
        } else if (this.value === 'montant_fixe') {
            hint.textContent = 'Entrez un montant en DH';
            valeurInput.removeAttribute('max');
        } else {
            hint.textContent = 'Entrez la valeur de la remise';
            valeurInput.removeAttribute('max');
        }
    });
</script>
@endpush
@endsection