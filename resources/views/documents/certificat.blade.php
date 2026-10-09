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

    <div class="titre">CERTIFICAT D'INSCRIPTION</div>
    <div class="numero">N° {{ $document->numero }}</div>

    <div class="corps">
        <p>
            @if (!empty($etab['directeur']))Je soussigné(e), <strong>{{ $etab['directeur'] }}</strong>, {{ $etab['directeur_titre'] ?: 'Directeur' }} de <strong>{{ $etab['nom'] }}</strong>, certifie @else La Direction de <strong>{{ $etab['nom'] }}</strong> certifie @endif que <strong>{{ $civ }} {{ $c['nom'] }} {{ $c['prenom'] }}</strong>{{ $naissance }}, est {{ $f ? 'inscrite' : 'inscrit' }} dans notre établissement pour l'année scolaire <strong>{{ $c['annee'] }}</strong>@if (!empty($c['date_inscription'])) depuis le <strong>{{ $c['date_inscription'] }}</strong>@endif.
        </p>

        <table class="fiche">
            <tr><td class="l">Matricule</td><td><strong>{{ $c['matricule'] }}</strong></td></tr>
            <tr><td class="l">Formation</td><td>{{ $c['filiere'] ?? '—' }}</td></tr>
            <tr><td class="l">Niveau / classe</td><td>{{ $c['niveau'] ?? '—' }} — {{ $c['classe'] ?? '—' }}</td></tr>
            @if (!empty($c['annee_fin']))<tr><td class="l">Fin de l'année scolaire</td><td>{{ $c['annee_fin'] }}</td></tr>@endif
        </table>

        <p>Le présent certificat est délivré pour servir et valoir ce que de droit.</p>
    </div>

    @include('documents.partials.pied')
</body></html>
