@extends(auth()->user()->role === 'comptable' ? 'layouts.comptable' : 'layouts.app')


@section('title', 'Créer un Échéancier')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-plus"></i> Créer un Nouvel Échéancier
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

                    <!-- Tabs pour choisir entre création simple et génération mensuelle -->
                    <ul class="nav nav-tabs mb-4" id="createTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="simple-tab" data-bs-toggle="tab" data-bs-target="#simple" type="button" role="tab">
                                <i class="fas fa-file"></i> Échéancier Simple
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="mensuel-tab" data-bs-toggle="tab" data-bs-target="#mensuel" type="button" role="tab">
                                <i class="fas fa-calendar-alt"></i> Génération Mensuelle
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="createTabContent">
                        <!-- Formulaire Simple -->
                        <div class="tab-pane fade show active" id="simple" role="tabpanel">
                            <form action="{{ route('echeanciers.store') }}" method="POST">
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

                                <!-- Année Scolaire -->
                                <div class="mb-3">
                                    <label for="annee_scolaire_id" class="form-label required">Année Scolaire</label>
                                    <select name="annee_scolaire_id" id="annee_scolaire_id" class="form-select @error('annee_scolaire_id') is-invalid @enderror" required>
                                        <option value="">-- Sélectionner une année --</option>
                                        @foreach($annees as $annee)
                                            <option value="{{ $annee->id }}" {{ old('annee_scolaire_id') == $annee->id ? 'selected' : '' }}>
                                                {{ $annee->nom }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('annee_scolaire_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Titre -->
                                <div class="mb-3">
                                    <label for="titre" class="form-label required">Titre de l'échéancier</label>
                                    <input type="text" name="titre" id="titre" 
                                        class="form-control @error('titre') is-invalid @enderror" 
                                        value="{{ old('titre') }}" 
                                        placeholder="Ex: Mensualité Janvier 2025" 
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
                                        value="{{ old('montant') }}" 
                                        step="0.01" 
                                        min="1" 
                                        placeholder="Ex: 1500.00" 
                                        required>
                                    @error('montant')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Date d'échéance -->
                                <div class="mb-3">
                                    <label for="date_echeance" class="form-label required">Date d'échéance</label>
                                    <input type="date" name="date_echeance" id="date_echeance" 
                                        class="form-control @error('date_echeance') is-invalid @enderror" 
                                        value="{{ old('date_echeance') }}" 
                                        required>
                                    @error('date_echeance')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Boutons -->
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('echeanciers.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Retour
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Formulaire Génération Mensuelle -->
                        <div class="tab-pane fade" id="mensuel" role="tabpanel">
                            <form action="{{ route('echeanciers.generer-mensuels') }}" method="POST">
                                @csrf

                                <!-- Stagiaire -->
                                <div class="mb-3">
                                    <label for="stagiaire_id_mensuel" class="form-label required">Stagiaire</label>
                                    <select name="stagiaire_id" id="stagiaire_id_mensuel" class="form-select" required>
                                        <option value="">-- Sélectionner un stagiaire --</option>
                                        @foreach($stagiaires as $stag)
                                            <option value="{{ $stag->id }}">
                                                {{ $stag->nom }} {{ $stag->prenom }} - {{ $stag->filiere->nom ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Année Scolaire -->
                                <div class="mb-3">
                                    <label for="annee_scolaire_id_mensuel" class="form-label required">Année Scolaire</label>
                                    <select name="annee_scolaire_id" id="annee_scolaire_id_mensuel" class="form-select" required>
                                        <option value="">-- Sélectionner une année --</option>
                                        @foreach($annees as $annee)
                                            <option value="{{ $annee->id }}">
                                                {{ $annee->nom }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Montant Mensuel -->
                                <div class="mb-3">
                                    <label for="montant_mensuel" class="form-label required">Montant Mensuel (DH)</label>
                                    <input type="number" name="montant_mensuel" id="montant_mensuel" 
                                        class="form-control" 
                                        step="0.01" 
                                        min="1" 
                                        placeholder="Ex: 1500.00" 
                                        required>
                                </div>

                                <!-- Date de début -->
                                <div class="mb-3">
                                    <label for="date_debut" class="form-label required">Date de début</label>
                                    <input type="date" name="date_debut" id="date_debut" 
                                        class="form-control" 
                                        required>
                                    <small class="text-muted">La première échéance sera à cette date</small>
                                </div>

                                <!-- Nombre de mois -->
                                <div class="mb-3">
                                    <label for="nombre_mois" class="form-label required">Nombre de mois</label>
                                    <select name="nombre_mois" id="nombre_mois" class="form-select" required>
                                        <option value="">-- Sélectionner --</option>
                                        @for($i = 1; $i <= 12; $i++)
                                            <option value="{{ $i }}">{{ $i }} mois</option>
                                        @endfor
                                    </select>
                                </div>

                                <!-- Aperçu -->
                                <div class="alert alert-info" id="apercu-mensuel" style="display: none;">
                                    <strong><i class="fas fa-info-circle"></i> Aperçu :</strong>
                                    <p class="mb-0" id="apercu-text"></p>
                                </div>

                                <!-- Boutons -->
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('echeanciers.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Retour
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-cogs"></i> Générer les Échéanciers
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Aperçu de la génération mensuelle
    document.getElementById('nombre_mois').addEventListener('change', updateApercu);
    document.getElementById('montant_mensuel').addEventListener('input', updateApercu);

    function updateApercu() {
        const nombreMois = document.getElementById('nombre_mois').value;
        const montantMensuel = document.getElementById('montant_mensuel').value;
        
        if (nombreMois && montantMensuel) {
            const total = nombreMois * montantMensuel;
            document.getElementById('apercu-text').innerHTML = 
                `${nombreMois} échéanciers de ${montantMensuel} DH seront créés.<br>` +
                `<strong>Total : ${total.toFixed(2)} DH</strong>`;
            document.getElementById('apercu-mensuel').style.display = 'block';
        }
    }
</script>
@endpush
@endsection