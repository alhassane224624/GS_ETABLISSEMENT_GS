@php
    use App\Support\Etablissement;
    $etab = Etablissement::infos();
    $logo = Etablissement::logoBase64();
    $dh = fn ($m) => number_format((float) $m, 2, ',', ' ') . ' DH';
    $methodes = \App\Models\Paiement::METHODES;
    $totalMethodes = array_sum($stats['par_methode']) ?: 1;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport financier</title>
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; }
        html { margin: 14mm 12mm 18mm 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; }
        .muted { color: #6b7280; } .right { text-align: right; } .center { text-align: center; } .bold { font-weight: bold; }
        .entete td { vertical-align: middle; }
        .etab { font-size: 13pt; font-weight: bold; color: #4f46e5; }
        .titre { font-size: 12pt; font-weight: bold; color: #4f46e5; text-align: right; }
        .barre { border-bottom: 2px solid #4f46e5; margin: 6px 0 10px; }
        h2 { font-size: 9.5pt; color: #3730a3; background: #eef2ff; padding: 4px 6px; margin: 12px 0 5px; }
        .kpi td { width: 25%; border: 1px solid #e5e7eb; padding: 6px; text-align: center; }
        .kpi .v { font-size: 12pt; font-weight: bold; margin-top: 2px; }
        .vert { color: #15803d; } .rouge { color: #b91c1c; } .bleu { color: #4f46e5; }
        .liste th { background: #4f46e5; color: #fff; font-size: 7.5pt; padding: 4px 5px; text-align: left; }
        .liste th.right { text-align: right; }
        .liste td { padding: 3px 5px; border-bottom: 1px solid #eef0f3; font-size: 7.8pt; }
        .liste tr.total td { font-weight: bold; background: #f9fafb; border-top: 1.5px solid #4f46e5; }
        .barre-prog { background: #e5e7eb; height: 7px; } .barre-prog div { background: #4f46e5; height: 7px; }
        .pied { position: fixed; bottom: -9mm; left: 0; right: 0; font-size: 7pt; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 3px; }
        .page:after { content: counter(page); }
        .vide { color: #6b7280; font-style: italic; padding: 6px; }
    </style>
</head>
<body>
    <div class="pied">
        <table><tr>
            <td>{{ $etab['nom'] }} — Rapport financier du {{ $dateDebut->format('d/m/Y') }} au {{ $dateFin->format('d/m/Y') }}</td>
            <td class="right">Édité le {{ now()->format('d/m/Y à H:i') }} — page <span class="page"></span></td>
        </tr></table>
    </div>

    <table class="entete">
        <tr>
            @if ($logo)<td style="width: 60px;"><img src="{{ $logo }}" style="max-width: 52px; max-height: 52px;"></td>@endif
            <td>
                <div class="etab">{{ $etab['nom'] }}</div>
                <div class="muted">{{ collect([$etab['adresse'], $etab['ville']])->filter()->implode(', ') }}</div>
            </td>
            <td>
                <div class="titre">RAPPORT FINANCIER</div>
                <div class="right muted">Du {{ $dateDebut->format('d/m/Y') }} au {{ $dateFin->format('d/m/Y') }}</div>
                <div class="right muted">Filière : {{ $filiere->nom ?? 'toutes' }}</div>
            </td>
        </tr>
    </table>
    <div class="barre"></div>

    {{-- Indicateurs --}}
    <table class="kpi">
        <tr>
            <td><div class="muted">Encaissé</div><div class="v vert">{{ $dh($stats['total_encaisse']) }}</div>
                <div class="muted" style="font-size: 7pt;">{{ $stats['evolution_encaisse'] >= 0 ? '+' : '' }}{{ $stats['evolution_encaisse'] }} % vs période précédente</div></td>
            <td><div class="muted">Attendu sur la période</div><div class="v">{{ $dh($stats['total_attendu']) }}</div>
                <div class="muted" style="font-size: 7pt;">après {{ $dh($stats['total_remises']) }} de remises</div></td>
            <td><div class="muted">Taux de recouvrement</div><div class="v bleu">{{ $stats['taux_recouvrement'] }} %</div></td>
            <td><div class="muted">Impayés à ce jour</div><div class="v rouge">{{ $dh($stats['total_impayes']) }}</div>
                <div class="muted" style="font-size: 7pt;">{{ $stats['nb_retards'] }} échéance(s) en retard</div></td>
        </tr>
    </table>

    {{-- Répartitions --}}
    <table style="margin-top: 4px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 6px;">
                <h2>Encaissements par mode de règlement</h2>
                <table class="liste">
                    <tr><th>Mode</th><th class="right">Montant</th><th style="width: 35%;">Part</th></tr>
                    @forelse ($stats['par_methode'] as $methode => $montant)
                        <tr>
                            <td>{{ $methodes[$methode] ?? $methode }}</td>
                            <td class="right">{{ $dh($montant) }}</td>
                            <td><div class="barre-prog"><div style="width: {{ round($montant / $totalMethodes * 100) }}%;"></div></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="vide">Aucun encaissement.</td></tr>
                    @endforelse
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 6px;">
                <h2>Encaissements par filière</h2>
                <table class="liste">
                    <tr><th>Filière</th><th class="right">Paiements</th><th class="right">Montant</th></tr>
                    @forelse ($par_filiere as $nom => $ligne)
                        <tr><td>{{ $nom }}</td><td class="right">{{ $ligne['nombre'] }}</td><td class="right">{{ $dh($ligne['montant']) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="vide">Aucun encaissement.</td></tr>
                    @endforelse
                </table>
            </td>
        </tr>
    </table>

    {{-- Détail des encaissements --}}
    <h2>Détail des encaissements ({{ $paiements->count() }})</h2>
    <table class="liste">
        <tr><th>Date</th><th>N°</th><th>Stagiaire</th><th>Filière</th><th>Échéances réglées</th><th>Mode</th><th class="right">Montant</th></tr>
        @forelse ($paiements as $p)
            <tr>
                <td>{{ $p->date_paiement->format('d/m/Y') }}</td>
                <td>{{ $p->numero_transaction }}</td>
                <td>{{ $p->stagiaire->nom ?? '' }} {{ $p->stagiaire->prenom ?? '' }}</td>
                <td>{{ $p->stagiaire->filiere->nom ?? '—' }}</td>
                <td>{{ $p->echeanciers->pluck('titre')->implode(', ') ?: '—' }}</td>
                <td>{{ $p->methode_libelle }}{{ $p->reference_externe ? ' ' . $p->reference_externe : '' }}</td>
                <td class="right">{{ $dh($p->montant) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="vide">Aucun encaissement sur la période.</td></tr>
        @endforelse
        @if ($paiements->isNotEmpty())
            <tr class="total"><td colspan="6" class="right">TOTAL ENCAISSÉ</td><td class="right">{{ $dh($paiements->sum('montant')) }}</td></tr>
        @endif
    </table>

    {{-- Échéances de la période --}}
    <h2>Échéances de la période ({{ $echeanciers->count() }})</h2>
    <table class="liste">
        <tr><th>Date</th><th>Stagiaire</th><th>Échéance</th><th class="right">Dû (net)</th><th class="right">Remise</th><th class="right">Réglé</th><th class="right">Reste</th><th>Statut</th></tr>
        @forelse ($echeanciers as $e)
            <tr>
                <td>{{ $e->date_echeance->format('d/m/Y') }}</td>
                <td>{{ $e->stagiaire->nom ?? '' }} {{ $e->stagiaire->prenom ?? '' }}</td>
                <td>{{ $e->titre }}</td>
                <td class="right">{{ $dh($e->montant_net) }}</td>
                <td class="right">{{ $e->montant_remise > 0 ? $dh($e->montant_remise) : '—' }}</td>
                <td class="right">{{ $dh($e->montant_paye) }}</td>
                <td class="right {{ $e->montant_restant > 0 ? 'rouge' : 'vert' }}">{{ $dh($e->montant_restant) }}</td>
                <td>{{ $e->statut_libelle }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="vide">Aucune échéance sur la période.</td></tr>
        @endforelse
        @if ($echeanciers->isNotEmpty())
            <tr class="total">
                <td colspan="3" class="right">TOTAL</td>
                <td class="right">{{ $dh($echeanciers->sum(fn ($e) => $e->montant_net)) }}</td>
                <td class="right">{{ $dh($echeanciers->sum('montant_remise')) }}</td>
                <td class="right">{{ $dh($echeanciers->sum('montant_paye')) }}</td>
                <td class="right">{{ $dh($echeanciers->sum('montant_restant')) }}</td>
                <td></td>
            </tr>
        @endif
    </table>

    {{-- Retards --}}
    <h2>Impayés en retard à ce jour ({{ $retards->count() }})</h2>
    <table class="liste">
        <tr><th>Échéance du</th><th>Retard</th><th>Stagiaire</th><th>Filière</th><th>Échéance</th><th class="right">Reste dû</th></tr>
        @forelse ($retards as $e)
            <tr>
                <td>{{ $e->date_echeance->format('d/m/Y') }}</td>
                <td>{{ (int) $e->date_echeance->diffInDays(now()) }} j</td>
                <td>{{ $e->stagiaire->nom ?? '' }} {{ $e->stagiaire->prenom ?? '' }} <span class="muted">{{ $e->stagiaire->matricule ?? '' }}</span></td>
                <td>{{ $e->stagiaire->filiere->nom ?? '—' }}</td>
                <td>{{ $e->titre }}</td>
                <td class="right rouge">{{ $dh($e->montant_restant) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="vide">Aucun retard.</td></tr>
        @endforelse
        @if ($retards->isNotEmpty())
            <tr class="total"><td colspan="5" class="right">TOTAL EN RETARD</td><td class="right">{{ $dh($retards->sum('montant_restant')) }}</td></tr>
        @endif
    </table>
</body>
</html>
