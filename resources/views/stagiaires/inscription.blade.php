<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription en ligne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(160deg, #4f46e5, #6366f1); min-height: 100vh; font-family: 'Inter', sans-serif; }
        .card { border: 0; border-radius: 16px; }
        .section-title { font-size: .85rem; text-transform: uppercase; letter-spacing: .05em; color: #4f46e5; font-weight: 600; }
        .hp { position: absolute; left: -9999px; }
    </style>
</head>
<body class="py-5">
<div class="container" style="max-width: 820px">
    <div class="text-center text-white mb-4">
        <h1 class="h3 fw-bold"><i class="fas fa-user-graduate me-2"></i>Demande d'inscription</h1>
        <p class="mb-0 opacity-75">Remplissez le formulaire, l'administration validera votre dossier.</p>
    </div>

    <div class="card shadow-lg">
        <div class="card-body p-4 p-md-5">
            @if (session('success'))
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('stagiaires.inscription.store') }}" enctype="multipart/form-data">
                @csrf
                {{-- Pot de miel anti-robot --}}
                <input type="text" name="site_web" class="hp" tabindex="-1" autocomplete="off">

                <p class="section-title mb-3">Identité</p>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom" value="{{ old('nom') }}" class="form-control @error('nom') is-invalid @enderror" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="prenom" value="{{ old('prenom') }}" class="form-control @error('prenom') is-invalid @enderror" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date de naissance</label>
                        <input type="date" name="date_naissance" value="{{ old('date_naissance') }}" class="form-control @error('date_naissance') is-invalid @enderror">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sexe</label>
                        <select name="sexe" class="form-select">
                            <option value="">—</option>
                            <option value="M" @selected(old('sexe') === 'M')>Masculin</option>
                            <option value="F" @selected(old('sexe') === 'F')>Féminin</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Photo</label>
                        <input type="file" name="photo" accept="image/*" class="form-control @error('photo') is-invalid @enderror">
                    </div>
                </div>

                <p class="section-title mb-3">Contact</p>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Téléphone *</label>
                        <input type="tel" name="telephone" value="{{ old('telephone') }}" class="form-control @error('telephone') is-invalid @enderror" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">E-mail *</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse</label>
                        <textarea name="adresse" rows="2" class="form-control">{{ old('adresse') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nom du tuteur / parent</label>
                        <input type="text" name="nom_tuteur" value="{{ old('nom_tuteur') }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone du tuteur</label>
                        <input type="tel" name="telephone_tuteur" value="{{ old('telephone_tuteur') }}" class="form-control">
                    </div>
                </div>

                <p class="section-title mb-3">Formation souhaitée</p>
                <div class="mb-4">
                    <select name="filiere_id" class="form-select @error('filiere_id') is-invalid @enderror" required>
                        <option value="">Choisir une filière…</option>
                        @foreach ($filieres as $filiere)
                            <option value="{{ $filiere->id }}" @selected(old('filiere_id') == $filiere->id)>
                                {{ $filiere->nom }}{{ $filiere->code ? ' — ' . $filiere->code : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('welcome') }}" class="text-muted text-decoration-none"><i class="fas fa-arrow-left me-1"></i>Accueil</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-paper-plane me-2"></i>Envoyer ma demande</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
