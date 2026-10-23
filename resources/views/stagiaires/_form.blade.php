{{-- Formulaire commun à l'ajout et à la modification d'un stagiaire ($stagiaire est null à l'ajout) --}}
@php $s = $stagiaire ?? null; @endphp

@if ($errors->any())
    <div class="alert alert-danger shadow-sm">
        <div class="fw-semibold mb-1"><i class="fas fa-exclamation-triangle me-2"></i>L'enregistrement n'a pas été fait. Corrigez {{ $errors->count() > 1 ? 'les points suivants' : 'le point suivant' }} :</div>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Informations personnelles --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold"><i class="fas fa-user text-primary me-2"></i>Informations personnelles</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" name="nom" value="{{ old('nom', $s?->nom) }}" required class="form-control @error('nom') is-invalid @enderror">
                @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Prénom <span class="text-danger">*</span></label>
                <input type="text" name="prenom" value="{{ old('prenom', $s?->prenom) }}" required class="form-control @error('prenom') is-invalid @enderror">
                @error('prenom')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Matricule @if ($s)<span class="text-danger">*</span>@endif</label>
                <input type="text" name="matricule" value="{{ old('matricule', $s?->matricule) }}" @required($s)
                       placeholder="{{ $s ? '' : 'Laisser vide pour le générer automatiquement' }}"
                       class="form-control @error('matricule') is-invalid @enderror">
                @error('matricule')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Date de naissance</label>
                <input type="date" name="date_naissance" value="{{ old('date_naissance', $s?->date_naissance?->format('Y-m-d')) }}"
                       max="{{ now()->subDay()->toDateString() }}" class="form-control @error('date_naissance') is-invalid @enderror">
                @error('date_naissance')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Lieu de naissance</label>
                <input type="text" name="lieu_naissance" value="{{ old('lieu_naissance', $s?->lieu_naissance) }}" class="form-control @error('lieu_naissance') is-invalid @enderror">
                @error('lieu_naissance')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Sexe</label>
                <select name="sexe" class="form-select @error('sexe') is-invalid @enderror">
                    <option value="">Sélectionner</option>
                    <option value="M" @selected(old('sexe', $s?->sexe) === 'M')>Masculin</option>
                    <option value="F" @selected(old('sexe', $s?->sexe) === 'F')>Féminin</option>
                </select>
                @error('sexe')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">Photo <span class="text-muted small">(JPG ou PNG, 2 Mo maximum)</span></label>
                <div class="d-flex align-items-center gap-3">
                    @if ($s?->photo)
                        <img src="{{ asset('storage/' . $s->photo) }}" alt="Photo actuelle" class="rounded border" style="width: 64px; height: 64px; object-fit: cover;">
                    @endif
                    <input type="file" name="photo" accept="image/jpeg,image/png" class="form-control @error('photo') is-invalid @enderror">
                </div>
                @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- Contact --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold"><i class="fas fa-address-book text-success me-2"></i>Contact et compte</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">E-mail <span class="text-danger">*</span></label>
                <input type="email" name="email" value="{{ old('email', $s?->email) }}" required class="form-control @error('email') is-invalid @enderror">
                <div class="form-text">Sert d'identifiant de connexion à l'espace stagiaire.</div>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Téléphone</label>
                <input type="tel" name="telephone" value="{{ old('telephone', $s?->telephone) }}" class="form-control @error('telephone') is-invalid @enderror">
                @error('telephone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">Adresse</label>
                <textarea name="adresse" rows="2" class="form-control @error('adresse') is-invalid @enderror">{{ old('adresse', $s?->adresse) }}</textarea>
                @error('adresse')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- Tuteur --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold"><i class="fas fa-users text-purple me-2" style="color: #7c3aed;"></i>Tuteur</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Nom du tuteur</label>
                <input type="text" name="nom_tuteur" value="{{ old('nom_tuteur', $s?->nom_tuteur) }}" class="form-control @error('nom_tuteur') is-invalid @enderror">
                @error('nom_tuteur')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Téléphone du tuteur</label>
                <input type="tel" name="telephone_tuteur" value="{{ old('telephone_tuteur', $s?->telephone_tuteur) }}" class="form-control @error('telephone_tuteur') is-invalid @enderror">
                @error('telephone_tuteur')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">E-mail du tuteur</label>
                <input type="email" name="email_tuteur" value="{{ old('email_tuteur', $s?->email_tuteur) }}" class="form-control @error('email_tuteur') is-invalid @enderror">
                @error('email_tuteur')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- Scolarité --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold"><i class="fas fa-graduation-cap text-warning me-2"></i>Scolarité</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Filière <span class="text-danger">*</span></label>
                <select name="filiere_id" id="filiere_id" required class="form-select @error('filiere_id') is-invalid @enderror">
                    <option value="">Sélectionner une filière</option>
                    @foreach ($filieres as $filiere)
                        <option value="{{ $filiere->id }}" @selected(old('filiere_id', $s?->filiere_id) == $filiere->id)>{{ $filiere->nom }}</option>
                    @endforeach
                </select>
                @error('filiere_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Niveau</label>
                <select name="niveau_id" id="niveau_id" class="form-select @error('niveau_id') is-invalid @enderror">
                    <option value="">Sélectionner un niveau</option>
                    @foreach ($niveaux as $niveau)
                        <option value="{{ $niveau->id }}" data-filiere="{{ $niveau->filiere_id }}" @selected(old('niveau_id', $s?->niveau_id) == $niveau->id)>{{ $niveau->nom }}</option>
                    @endforeach
                </select>
                @error('niveau_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Classe</label>
                <select name="classe_id" id="classe_id" class="form-select @error('classe_id') is-invalid @enderror">
                    <option value="">Sélectionner une classe</option>
                    @foreach ($classes as $classe)
                        <option value="{{ $classe->id }}" data-filiere="{{ $classe->filiere_id }}" data-niveau="{{ $classe->niveau_id }}"
                                @selected(old('classe_id', $s?->classe_id) == $classe->id)>
                            {{ $classe->nom }} ({{ $classe->effectif_actuel }}/{{ $classe->effectif_max }} places)
                        </option>
                    @endforeach
                </select>
                <div class="form-text" id="classe_aide">Seules les classes de l'année active qui ont encore des places sont proposées.</div>
                @error('classe_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            @if ($s)
                <div class="col-md-4">
                    <label class="form-label">Statut <span class="text-danger">*</span></label>
                    <select name="statut" required class="form-select @error('statut') is-invalid @enderror">
                        @foreach (['actif' => 'Actif', 'suspendu' => 'Suspendu', 'diplome' => 'Diplômé', 'abandonne' => 'Abandonné', 'transfere' => 'Transféré'] as $valeur => $libelle)
                            <option value="{{ $valeur }}" @selected(old('statut', $s->statut) === $valeur)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                    @error('statut')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label">Motif du changement de statut</label>
                    <textarea name="motif_statut" rows="1" class="form-control @error('motif_statut') is-invalid @enderror">{{ old('motif_statut', $s->motif_statut) }}</textarea>
                    @error('motif_statut')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @else
                <div class="col-md-4">
                    <label class="form-label">Date d'inscription</label>
                    <input type="date" name="date_inscription" value="{{ old('date_inscription', now()->toDateString()) }}" class="form-control @error('date_inscription') is-invalid @enderror">
                    @error('date_inscription')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endif

            <div class="col-md-4">
                <label class="form-label">Frais d'inscription</label>
                <div class="input-group">
                    <input type="number" name="frais_inscription" value="{{ old('frais_inscription', $s?->frais_inscription) }}" step="0.01" min="0" class="form-control @error('frais_inscription') is-invalid @enderror">
                    <span class="input-group-text">DH</span>
                    @error('frais_inscription')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    {{-- Champ caché : une case décochée n'est pas envoyée par le navigateur --}}
                    <input type="hidden" name="frais_payes" value="0">
                    <input type="checkbox" name="frais_payes" id="frais_payes" value="1" class="form-check-input" @checked(old('frais_payes', $s?->frais_payes))>
                    <label for="frais_payes" class="form-check-label">Frais d'inscription payés</label>
                </div>
            </div>
            @if ($s)
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input" @checked(old('is_active', $s->is_active))>
                        <label for="is_active" class="form-check-label">Compte actif (peut se connecter)</label>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mb-4">
    <a href="{{ route('stagiaires.index') }}" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i>Annuler</a>
    <button type="submit" class="btn btn-primary" id="btn_enregistrer"><i class="fas fa-save me-1"></i>Enregistrer</button>
</div>

@push('scripts')
<script>
    // Ne proposer que les niveaux et classes de la filière (et du niveau) choisis
    (function () {
        const filiere = document.getElementById('filiere_id');
        const niveau = document.getElementById('niveau_id');
        const classe = document.getElementById('classe_id');

        function filtrer(select, garder) {
            let visibles = 0;
            for (const opt of select.options) {
                if (!opt.value) continue;
                const ok = garder(opt);
                opt.hidden = !ok;
                opt.disabled = !ok;
                if (ok) visibles++;
                if (!ok && opt.selected) select.value = '';
            }
            return visibles;
        }

        function maj() {
            const f = filiere.value, n = niveau.value;
            filtrer(niveau, o => !f || o.dataset.filiere === f);
            const nb = filtrer(classe, o => (!f || o.dataset.filiere === f) && (!niveau.value || o.dataset.niveau === niveau.value));
            document.getElementById('classe_aide').textContent = f && nb === 0
                ? 'Aucune classe disponible pour ce choix : le stagiaire pourra être affecté plus tard.'
                : 'Seules les classes de l\'année active qui ont encore des places sont proposées.';
        }

        filiere.addEventListener('change', maj);
        niveau.addEventListener('change', maj);
        maj();

        // Évite les doubles envois (et donc les doublons) en cas de double clic
        classe.form.addEventListener('submit', () => {
            const btn = document.getElementById('btn_enregistrer');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…';
        });
    })();
</script>
@endpush
