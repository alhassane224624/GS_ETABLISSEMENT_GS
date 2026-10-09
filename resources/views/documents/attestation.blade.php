@php
    $f = ($c['sexe'] ?? null) === 'F';
    $civ = $f ? 'Mme / Mlle' : 'M.';
    $naissance = !empty($c['date_naissance'])
        ? ', ' . ($f ? 'née' : 'né') . ' le ' . $c['date_naissance'] . (!empty($c['lieu_naissance']) ? ' à ' . $c['lieu_naissance'] : '')
        : '';
@endphp
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><title>{{ $document->numero }}</title>@include('documents.partials.styles')</head>
<body>
    @include('documents.partials.entete')

    <div class="titre">ATTESTATION DE SCOLARITÉ</div>
    <div class="numero">N° {{ $document->numero }}</div>

    <div class="corps">
        <p>
            @if (!empty($etab['directeur']))Je soussigné(e), <strong>{{ $etab['directeur'] }}</strong>, {{ $etab['directeur_titre'] ?: 'Directeur' }} de <strong>{{ $etab['nom'] }}</strong>, atteste @else La Direction de <strong>{{ $etab['nom'] }}</strong> atteste @endif que <strong>{{ $civ }} {{ $c['nom'] }} {{ $c['prenom'] }}</strong>{{ $naissance }}, est régulièrement {{ $f ? 'inscrite' : 'inscrit' }} et suit les cours dans notre établissement au titre de l'année scolaire <strong>{{ $c['annee'] }}</strong> :
        </p>

        <table class="fiche">
            <tr><td class="l">Matricule</td><td><strong>{{ $c['matricule'] }}</strong></td></tr>
            <tr><td class="l">Filière</td><td>{{ $c['filiere'] ?? '—' }}</td></tr>
            <tr><td class="l">Niveau</td><td>{{ $c['niveau'] ?? '—' }}</td></tr>
            <tr><td class="l">Classe</td><td>{{ $c['classe'] ?? '—' }}</td></tr>
        </table>

        <p>La présente attestation est délivrée à l'intéressé{{ $f ? 'e' : '' }}, sur sa demande, pour servir et valoir ce que de droit.</p>
    </div>

    @include('documents.partials.pied')
</body></html>
