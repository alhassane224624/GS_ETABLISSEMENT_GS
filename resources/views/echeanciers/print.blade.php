<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Échéancier #{{ $echeancier->id }} - {{ $echeancier->stagiaire->nom_complet }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #4f46e5;
            font-size: 24pt;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 10pt;
        }
        
        .echeancier-title {
            text-align: center;
            background-color: #4f46e5;
            color: white;
            padding: 15px;
            margin-bottom: 30px;
            font-size: 18pt;
            font-weight: bold;
        }
        
        .info-section {
            margin-bottom: 30px;
        }
        
        .info-section h3 {
            background-color: #f3f4f6;
            padding: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #4f46e5;
            font-size: 13pt;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            padding: 8px;
            font-weight: bold;
            width: 40%;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-value {
            display: table-cell;
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .montant-principal {
            text-align: center;
            margin: 40px 0;
            padding: 20px;
            background-color: #f0fdf4;
            border: 2px solid #22c55e;
            border-radius: 8px;
        }
        
        .montant-principal .label {
            font-size: 12pt;
            color: #666;
            margin-bottom: 10px;
        }
        
        .montant-principal .montant {
            font-size: 32pt;
            font-weight: bold;
            color: #22c55e;
        }
        
        .montant-principal .devise {
            font-size: 16pt;
            color: #666;
        }
        
        .status-box {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
        }
        
        .status-box.paye {
            background-color: #d1fae5;
            border: 2px solid #10b981;
            color: #065f46;
        }
        
        .status-box.impaye {
            background-color: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
        }
        
        .status-box.partiel {
            background-color: #fef3c7;
            border: 2px solid #f59e0b;
            color: #92400e;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        
        table.payment-history {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table.payment-history th,
        table.payment-history td {
            padding: 10px;
            text-align: left;
            border: 1px solid #e5e7eb;
        }
        
        table.payment-history th {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100pt;
            opacity: 0.1;
            font-weight: bold;
            z-index: -1;
        }
        
        .watermark.paye {
            color: #10b981;
        }
        
        .watermark.impaye {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="watermark {{ $echeancier->statut }}">
        {{ strtoupper($echeancier->statut_libelle) }}
    </div>
    
    <div class="container">
        {{-- En-tête --}}
        <div class="header">
            <h1>{{ \App\Support\Etablissement::get('nom') }}</h1>
            <p>Établissement d'Enseignement Supérieur</p>
            <p>Tél: +212 XXX XXX XXX | Email: contact@emsi.ma</p>
        </div>

        {{-- Titre --}}
        <div class="echeancier-title">
            ÉCHÉANCIER DE PAIEMENT
        </div>

        {{-- Statut --}}
        <div class="status-box {{ $echeancier->statut }}">
            <strong style="font-size: 14pt;">STATUT: {{ strtoupper($echeancier->statut_libelle) }}</strong>
        </div>

        {{-- Informations échéancier --}}
        <div class="info-section">
            <h3>INFORMATIONS DE L'ÉCHÉANCIER</h3>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Numéro</div>
                    <div class="info-value"><strong>#{{ $echeancier->id }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Titre</div>
                    <div class="info-value"><strong>{{ $echeancier->titre }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Année scolaire</div>
                    <div class="info-value">{{ $echeancier->anneeScolaire->nom ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date d'échéance</div>
                    <div class="info-value">
                        <strong>{{ $echeancier->date_echeance->format('d/m/Y') }}</strong>
                        @if($echeancier->is_en_retard)
                            <br><span style="color: #ef4444;">⚠ En retard de {{ now()->diffInDays($echeancier->date_echeance) }} jours</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Informations stagiaire --}}
        <div class="info-section">
            <h3>INFORMATIONS DU STAGIAIRE</h3>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Nom complet</div>
                    <div class="info-value"><strong>{{ $echeancier->stagiaire->nom_complet }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Matricule</div>
                    <div class="info-value">{{ $echeancier->stagiaire->matricule }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Filière</div>
                    <div class="info-value">{{ $echeancier->stagiaire->filiere->nom ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Classe</div>
                    <div class="info-value">{{ $echeancier->stagiaire->classe->nom ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- Montants --}}
        <div class="info-section">
            <h3>DÉTAILS FINANCIERS</h3>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Montant total</div>
                    <div class="info-value"><strong>{{ number_format($echeancier->montant, 2) }} DH</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Montant payé</div>
                    <div class="info-value" style="color: #10b981;"><strong>{{ number_format($echeancier->montant_paye, 2) }} DH</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Montant restant</div>
                    <div class="info-value" style="color: #ef4444;"><strong>{{ number_format($echeancier->montant_restant, 2) }} DH</strong></div>
                </div>
            </div>
        </div>

        {{-- Montant restant en grand --}}
        @if($echeancier->montant_restant > 0)
        <div class="montant-principal">
            <div class="label">MONTANT RESTANT À PAYER</div>
            <div>
                <span class="montant">{{ number_format($echeancier->montant_restant, 2) }}</span>
                <span class="devise">DH</span>
            </div>
        </div>
        @endif

        {{-- Historique des paiements --}}
        @if($echeancier->paiements && $echeancier->paiements->count() > 0)
        <div class="info-section">
            <h3>HISTORIQUE DES PAIEMENTS</h3>
            <table class="payment-history">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>N° Transaction</th>
                        <th>Montant</th>
                        <th>Méthode</th>
                        <th>Par</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($echeancier->paiements as $paiement)
                    <tr>
                        <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                        <td>{{ $paiement->numero_transaction }}</td>
                        <td><strong>{{ number_format($paiement->pivot->montant_affecte, 2) }} DH</strong></td>
                        <td>{{ $paiement->methode_libelle }}</td>
                        <td>{{ $paiement->user->name ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background-color: #f3f4f6;">
                        <th colspan="2">TOTAL PAYÉ</th>
                        <th colspan="3">{{ number_format($echeancier->montant_paye, 2) }} DH</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif

        {{-- Pied de page --}}
        <div class="footer">
            <p><strong>Document généré le {{ now()->format('d/m/Y à H:i') }}</strong></p>
            <p style="margin-top: 10px;">Ce document est une copie conforme de l'échéancier enregistré dans notre système.</p>
            <p>Pour toute question, veuillez contacter le service financier.</p>
        </div>
    </div>
</body>
</html>