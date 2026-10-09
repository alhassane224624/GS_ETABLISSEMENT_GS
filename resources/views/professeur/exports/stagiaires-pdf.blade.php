<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des stagiaires</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; margin: 20px; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 8px; margin-bottom: 15px; }
        h2 { margin: 0; color: #4f46e5; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #4f46e5; color: #fff; padding: 6px; text-align: left; }
        td { padding: 5px 6px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f5f5ff; }
        .footer { margin-top: 15px; font-size: 9px; color: #777; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Liste des stagiaires</h2>
        <div>Professeur : {{ $user->name }} — {{ now()->format('d/m/Y H:i') }} — {{ $stagiaires->count() }} stagiaire(s)</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th><th>Matricule</th><th>Nom</th><th>Prénom</th>
                <th>Filière</th><th>Niveau</th><th>Classe</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stagiaires as $i => $s)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $s->matricule }}</td>
                    <td>{{ $s->nom }}</td>
                    <td>{{ $s->prenom }}</td>
                    <td>{{ $s->filiere->nom ?? '—' }}</td>
                    <td>{{ $s->niveau->nom ?? '—' }}</td>
                    <td>{{ $s->classe->nom ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center">Aucun stagiaire.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Document généré automatiquement</div>
</body>
</html>
