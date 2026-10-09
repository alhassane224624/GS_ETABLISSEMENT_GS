<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Relevé de notes - {{ $stagiaire->matricule }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 15px;
        }

        .header h1 {
            color: #3b82f6;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header h2 {
            color: #666;
            font-size: 14px;
            font-weight: normal;
            margin-top: 5px;
        }

        .info-section {
            width: 100%;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            background: #f9fafb;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 120px;
        }

        .info-value {
            display: inline-block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        th {
            background-color: #3b82f6;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }

        td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 10px;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .note-cell {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        .total-row {
            background-color: #3b82f6 !important;
            color: white;
            font-weight: bold;
            font-size: 11px;
        }

        .total-row td {
            border-color: #2563eb;
            padding: 10px 8px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .badge-success {
            background-color: #10b981;
            color: white;
        }

        .badge-warning {
            background-color: #f59e0b;
            color: white;
        }

        .badge-danger {
            background-color: #ef4444;
            color: white;
        }

        .badge-info {
            background-color: #3b82f6;
            color: white;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <h1>📊 RELEVÉ DE NOTES</h1>
        <h2>{{ $stagiaire->prenom }} {{ $stagiaire->nom }} ({{ $stagiaire->matricule }})</h2>
        <p style="color: #666; margin-top: 5px; font-size: 10px;">
            Filière: {{ $stagiaire->filiere->nom ?? 'N/A' }}
            @if($stagiaire->classe)
                - Classe: {{ $stagiaire->classe->nom }}
            @endif
        </p>
    </div>

    <!-- Informations stagiaire -->
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Date d'impression :</span>
            <span class="info-value">{{ now()->format('d/m/Y à H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Nombre de notes :</span>
            <span class="info-value">{{ $notes->count() }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Moyenne générale :</span>
            <span class="info-value">
                <strong style="color: {{ $moyenneGenerale >= 10 ? '#10b981' : '#ef4444' }}; font-size: 12px;">
                    {{ number_format($moyenneGenerale, 2) }}/20
                </strong>
            </span>
        </div>
    </div>

    <!-- Tableau des notes -->
    @if($notes->count() > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 30%;">Matière</th>
                    <th style="width: 12%; text-align: center;">Type</th>
                    <th style="width: 12%; text-align: center;">Note</th>
                    <th style="width: 12%; text-align: center;">Sur</th>
                    <th style="width: 22%; text-align: center;">Appréciation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($notes as $note)
                    <tr>
                        <td>{{ $note->created_at->format('d/m/Y') }}</td>
                        <td>
                            <strong>{{ $note->matiere->nom ?? 'N/A' }}</strong>
                            @if($note->periode)
                                <br><small style="color: #666;">{{ $note->periode->nom }}</small>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-info">{{ strtoupper($note->type_note) }}</span>
                        </td>
                        <td class="note-cell" style="color: {{ $note->note >= 10 ? '#10b981' : '#ef4444' }};">
                            {{ number_format($note->note, 2) }}
                        </td>
                        <td class="note-cell">
                            {{ $note->note_sur }}
                        </td>
                        <td style="text-align: center;">
                            @php
                                $noteSur20 = ($note->note / $note->note_sur) * 20;
                            @endphp
                            @if($noteSur20 >= 16)
                                <span class="badge badge-success">Excellent</span>
                            @elseif($noteSur20 >= 14)
                                <span class="badge badge-success">Très bien</span>
                            @elseif($noteSur20 >= 12)
                                <span class="badge badge-success">Bien</span>
                            @elseif($noteSur20 >= 10)
                                <span class="badge badge-warning">Passable</span>
                            @else
                                <span class="badge badge-danger">Insuffisant</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" style="text-align: left;">
                        <strong>MOYENNE GÉNÉRALE</strong>
                    </td>
                    <td colspan="2" style="text-align: center; font-size: 13px;">
                        <strong>{{ number_format($moyenneGenerale, 2) }}/20</strong>
                    </td>
                    <td style="text-align: center;">
                        @if($moyenneGenerale >= 16)
                            <strong>Excellent</strong>
                        @elseif($moyenneGenerale >= 14)
                            <strong>Très bien</strong>
                        @elseif($moyenneGenerale >= 12)
                            <strong>Bien</strong>
                        @elseif($moyenneGenerale >= 10)
                            <strong>Passable</strong>
                        @else
                            <strong>Insuffisant</strong>
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>
    @else
        <div style="text-align: center; padding: 40px; color: #999; border: 1px dashed #ddd; border-radius: 5px;">
            <p style="font-size: 14px; margin: 0;">Aucune note enregistrée pour cette période</p>
        </div>
    @endif

    <!-- Pied de page -->
    <div class="footer">
        <p>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>Ce document est un relevé de notes officiel</p>
        <p style="margin-top: 5px; font-size: 8px;">
            {{ \App\Support\Etablissement::get('nom') }} - {{ $stagiaire->filiere->nom ?? '' }}
        </p>
    </div>
</body>
</html>