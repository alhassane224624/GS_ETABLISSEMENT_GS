@php $etab = \App\Support\Etablissement::infos(); @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification d'un document — {{ $etab['nom'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width: 620px;">
    <div class="text-center mb-4">
        <h1 class="h4 fw-bold text-primary mb-1">{{ $etab['nom'] }}</h1>
        <p class="text-muted">Vérification de l'authenticité d'un document</p>
    </div>

    <form method="GET" action="{{ route('documents.verifier') }}" class="card shadow-sm mb-4">
        <div class="card-body d-flex gap-2">
            <input type="text" name="code" value="{{ $code }}" class="form-control text-uppercase" placeholder="Code imprimé sur le document" required>
            <button class="btn btn-primary">Vérifier</button>
        </div>
    </form>

    @if ($code)
        @if (!$document)
            <div class="alert alert-danger"><strong>Document inconnu.</strong> Aucun document n'a été délivré avec ce code.</div>
        @elseif (!$document->estValide())
            <div class="alert alert-danger"><strong>Document annulé</strong> le {{ $document->annule_at->format('d/m/Y') }}. Il n'est plus valable.</div>
        @else
            <div class="card border-success shadow-sm">
                <div class="card-header bg-success text-white fw-semibold">✓ Document authentique</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th class="text-muted fw-normal">Document</th><td>{{ $document->type_libelle }}</td></tr>
                        <tr><th class="text-muted fw-normal">Numéro</th><td>{{ $document->numero }}</td></tr>
                        <tr><th class="text-muted fw-normal">Délivré le</th><td>{{ $document->created_at->format('d/m/Y') }}</td></tr>
                        <tr><th class="text-muted fw-normal">Titulaire</th><td>{{ $document->contenu['nom'] ?? '' }} {{ $document->contenu['prenom'] ?? '' }}</td></tr>
                        <tr><th class="text-muted fw-normal">Formation</th><td>{{ $document->contenu['filiere'] ?? '—' }} — {{ $document->contenu['classe'] ?? '' }}</td></tr>
                        <tr><th class="text-muted fw-normal">Année scolaire</th><td>{{ $document->contenu['annee'] ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>
        @endif
    @endif
</div>
</body>
</html>
