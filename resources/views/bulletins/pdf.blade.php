<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>📘 Bulletin Scolaire - {{ $bulletin->stagiaire->nom }} {{ $bulletin->stagiaire->prenom }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #333;
            background: #fff;
            padding: 25px 35px;
            line-height: 1.5;
        }

        /* ===== HEADER ===== */
        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 20px;
            color: #2563eb;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header h2 {
            font-size: 14px;
            color: #444;
            margin-bottom: 3px;
        }

        .header p {
            font-size: 11px;
            color: #666;
        }

        /* ===== INFOS STAGIAIRE ===== */
        .info-box {
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 5px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }

        .info-row {
            margin: 5px 0;
        }

        .info-label {
            font-weight: bold;
            color: #374151;
            display: inline-block;
            width: 130px;
        }

        /* ===== TABLEAU DES NOTES ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
            font-size: 11px;
        }

        th {
            background-color: #2563eb;
            color: white;
            text-align: center;
            padding: 8px;
        }

        td {
            border: 1px solid #d1d5db;
            padding: 6px;
            text-align: center;
        }

        td:first-child {
            text-align: left;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .total-row {
            background-color: #eff6ff !important;
            font-weight: bold;
            color: #1e3a8a;
        }

        /* ===== TABLEAU RÉCAPITULATIF ===== */
        .summary-table td {
            border: 1px solid #2563eb;
            padding: 8px;
        }

        .summary-table strong {
            color: #1e3a8a;
        }

        /* ===== APPRÉCIATION ===== */
        .appreciation {
            background-color: #f8fafc;
            border: 1px solid #dbeafe;
            border-left: 5px solid #2563eb;
            padding: 12px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 11px;
        }

        .appreciation strong {
            color: #1e3a8a;
        }

        /* ===== SIGNATURES ===== */
        .signatures {
            margin-top: 70px;
            width: 100%;
            display: table;
        }

        .signatures .col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: center;
        }

        .signatures .col.left {
            padding-left: 60px;
        }

        .signatures .col.right {
            padding-right: 60px;
            position: relative;
            top: -20px; /* 🔹 remonte Chef de Pôle */
        }

        .signatures .col span {
            display: inline-block;
            margin-top: 10px;
            font-weight: bold;
            text-decoration: underline;
            color: #1f2937;
        }

        /* ===== FOOTER ===== */
        .footer {
            margin-top: 120px;
            text-align: center;
            font-size: 9px;
            color: #555;
            line-height: 1.6;
        }
    </style>
</head>
<body>

    <!-- ===== HEADER ===== -->
    <div class="header">
        <h1> BULLETIN SCOLAIRE</h1>
        <h2>{{ $bulletin->periode->nom ?? 'Période non définie' }}</h2>
        <p>Année scolaire {{ $bulletin->periode->anneeScolaire->nom ?? 'N/A' }}</p>
    </div>

    <!-- ===== INFOS STAGIAIRE ===== -->
    <div class="info-box">
        <div class="info-row"><span class="info-label">Nom complet :</span> {{ strtoupper($bulletin->stagiaire->nom) }} {{ ucfirst($bulletin->stagiaire->prenom) }}</div>
        <div class="info-row"><span class="info-label">Matricule :</span> {{ $bulletin->stagiaire->matricule }}</div>
        <div class="info-row"><span class="info-label">Filière :</span> {{ $bulletin->classe->filiere->nom ?? 'N/A' }}</div>
        <div class="info-row"><span class="info-label">Classe :</span> {{ $bulletin->classe->nom ?? 'N/A' }}</div>
    </div>

    <!-- ===== TABLEAU DES NOTES ===== -->
    @php
        $moyennes = is_array($bulletin->moyennes_matieres)
            ? $bulletin->moyennes_matieres
            : json_decode($bulletin->moyennes_matieres, true) ?? [];
        $totalPoints = 0;
        $totalCoef = 0;
    @endphp

    <table>
        <thead>
            <tr>
                <th>Matière</th>
                <th>Type (R/L)</th>
                <th>Note /20</th>
                <th>Coef</th>
                <th>Note GLE (Note × Coef)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($moyennes as $matiere)
                @php
                    if (!is_array($matiere)) continue;
                    $note = (float)($matiere['moyenne'] ?? 0);
                    $coef = (int)($matiere['coefficient'] ?? 1);
                    $points = $note * $coef;
                    $totalPoints += $points;
                    $totalCoef += $coef;
                    $type = str_contains(strtolower($matiere['matiere']), 'projet') ? 'R' : 'L';
                @endphp
                <tr>
                    <td>{{ $matiere['matiere'] ?? 'N/A' }}</td>
                    <td>{{ $type }}</td>
                    <td>{{ number_format($note, 2) }}</td>
                    <td>{{ $coef }}</td>
                    <td>{{ number_format($points, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" style="text-align:right;">Moyenne Générale /20</td>
                <td>{{ number_format($bulletin->moyenne_generale, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- ===== RÉSUMÉ ===== -->
    <table class="summary-table">
        <tr>
            <td><strong>Moyenne générale :</strong> {{ number_format($bulletin->moyenne_generale, 2) }}/20</td>
            <td><strong>Classement :</strong> {{ $bulletin->rang }}{{ $bulletin->rang == 1 ? 'er' : 'ème' }} / {{ $bulletin->total_classe }}</td>
            <td><strong>Décision du jury :</strong>
                @if ($bulletin->moyenne_generale >= 10)
                    <span style="color:#10b981;font-weight:bold;">Admis(e)</span>
                @else
                    <span style="color:#ef4444;font-weight:bold;">redoublant(e)</span>
                @endif
            </td>
        </tr>
    </table>

    <!-- ===== APPRÉCIATION ===== -->
    <div class="appreciation">
        <strong>📝 Appréciation Générale :</strong><br>
        {{ $bulletin->appreciation_generale ?? 'Aucune appréciation disponible.' }}
    </div>

    <!-- ===== SIGNATURES ===== -->
    <div class="signatures">
        <div class="col left">
            <span>Directeur d’Établissement</span>
        </div>
        <div class="col right">
            <span>Chef de Pôle Pédagogique</span>
        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <div class="footer">
        Fait à Conakry, le {{ now()->format('d/m/Y') }} — Document généré automatiquement.<br>
        Ce bulletin est un document officiel. Toute falsification est passible de sanctions disciplinaires.
    </div>

</body>
</html>
