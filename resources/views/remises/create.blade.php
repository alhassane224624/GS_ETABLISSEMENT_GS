@extends(auth()->user()->role === 'comptable' ? 'layouts.comptable' : 'layouts.app')
@section('title', 'Créer une Remise')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-gift"></i> Créer une Nouvelle Remise
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

                    <form action="{{ route('remises.store') }}" method="POST">
                        @csrf

                        <!-- Stagiaire -->
                        <div class="mb-3">
                            <label for="stagiaire_id" class="form-label required">Stagiaire</label>
                            <select name="stagiaire_id" id="stagiaire_id" class="form-select @error('stagiaire_id') is-invalid @enderror" required>
                                <option value="">-- Sélectionner un stagiaire --</option>
                                @foreach($stagiaires as $stag)
                                    <option value="{{ $stag->id }}" 
                                        {{ (old('stagiaire_id', $stagiaire->id ?? '') == $stag->id) ? 'selected' : '' }}>
                                        {{ $stag->nom }} {{ $stag->prenom }} - {{ $stag->filiere->nom ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('stagiaire_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Titre -->
                        <div class="mb-3">
                            <label for="titre" class="form-label required">Titre de la remise</label>
                            <input type="text" name="titre" id="titre" 
                                class="form-control @error('titre') is-invalid @enderror" 
                                value="{{ old('titre') }}" 
                                placeholder="Ex: Remise pour bon comportement" 
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
                                    <option value="pourcentage" {{ old('type') == 'pourcentage' ? 'selected' : '' }}>Pourcentage (%)</option>
                                    <option value="montant_fixe" {{ old('type') == 'montant_fixe' ? 'selected' : '' }}>Montant Fixe (DH)</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="valeur" class="form-label required">Valeur</label>
                                <input type="number" name="valeur" id="valeur" 
                                    class="form-control @error('valeur') is-invalid @enderror" 
                                    value="{{ old('valeur') }}" 
                                    step="0.01" 
                                    min="0" 
                                    placeholder="Ex: 10" 
                                    required>
                                <small class="form-text text-muted" id="valeur-hint">
                                    Entrez la valeur de la remise
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
                                placeholder="Décrivez le motif de cette remise..." 
                                required>{{ old('motif') }}</textarea>
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
                                    value="{{ old('date_debut') }}" 
                                    required>
                                @error('date_debut')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="date_fin" class="form-label">Date de fin (optionnel)</label>
                                <input type="date" name="date_fin" id="date_fin" 
                                    class="form-control @error('date_fin') is-invalid @enderror" 
                                    value="{{ old('date_fin') }}">
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
                                    {{ old('is_active', true) ? 'checked' : '' }}>
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
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer
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