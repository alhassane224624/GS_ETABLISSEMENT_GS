@php
    use App\Support\Etablissement;
    use App\Helpers\NumberHelper;

    $etab = Etablissement::infos();
    $logo = Etablissement::logoBase64();
    $mentions = Etablissement::mentionsLegales();
    $situation = $paiement->situationFinanciere();
    $stagiaire = $paiement->stagiaire;
    $dh = fn ($m) => number_format((float) $m, 2, ',', ' ') . ' DH';
    $banque = $paiement->metadata['banque'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu {{ $paiement->numero_transaction }}</title>
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; }
        html { margin: 14mm 14mm 14mm 14mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #1f2937; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        .muted { color: #6b7280; }
        .small { font-size: 7.5pt; }
        .right { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: bold; }

        /* En-tête */
        .entete td { vertical-align: top; }
        .etab-nom { font-size: 14pt; font-weight: bold; color: #4f46e5; }
        .recu-box { border: 2px solid #4f46e5; border-radius: 6px; padding: 6px 10px; text-align: center; }
        .recu-box .titre { font-size: 9.5pt; white-space: nowrap; font-weight: bold; color: #4f46e5; letter-spacing: 1px; }
        .recu-box .numero { font-size: 10pt; font-weight: bold; margin-top: 2px; }
        .badge { display: inline-block; background: #d1fae5; color: #065f46; border-radius: 10px; padding: 1px 8px; font-size: 7.5pt; font-weight: bold; margin-top: 3px; }
        .separateur { border-bottom: 2px solid #4f46e5; margin: 8px 0 10px; }

        /* Blocs */
        .bloc-titre { background: #eef2ff; color: #3730a3; font-weight: bold; font-size: 8pt; padding: 3px 6px; letter-spacing: .5px; }
        .infos td { padding: 2.5px 6px; border-bottom: 1px solid #f1f5f9; }
        .infos td.lbl { color: #6b7280; width: 38%; }

        .detail th { background: #4f46e5; color: #fff; font-size: 8pt; padding: 4px 6px; text-align: left; }
        .detail th.right { text-align: right; }
        .detail td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; }
        .detail tr.total td { background: #f9fafb; font-weight: bold; border-top: 1.5px solid #4f46e5; }

        .montant { border: 2px solid #16a34a; background: #f0fdf4; border-radius: 6px; padding: 8px 12px; margin-top: 10px; }
        .montant .valeur { font-size: 18pt; font-weight: bold; color: #15803d; }
        .montant .lettres { font-style: italic; margin-top: 2px; }

        .situation td { width: 25%; text-align: center; padding: 6px 4px; border: 1px solid #e5e7eb; }
        .situation .chiffre { font-size: 11pt; font-weight: bold; margin-top: 1px; }
        .reste { color: #b91c1c; }
        .solde-ok { color: #15803d; }

        .signatures td { width: 50%; text-align: center; padding-top: 34px; }
        .signatures .ligne { border-top: 1px solid #374151; margin: 0 25px; padding-top: 3px; font-size: 8pt; }

        .pied { position: fixed; bottom: -4mm; left: 0; right: 0; text-align: center; font-size: 7pt; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 3px; }

        .filigrane { position: fixed; top: 38%; left: 18%; font-size: 90pt; font-weight: bold; color: #16a34a; opacity: .06; transform: rotate(-30deg); }
    </style>
</head>
<body>
    <div class="filigrane">PAYÉ</div>

    {{-- ===== EN-TÊTE ===== --}}
    <table class="entete">
        <tr>
            @if ($logo)
                <td style="width: 70px;"><img src="{{ $logo }}" style="max-width: 62px; max-height: 62px;"></td>
            @endif
            <td>
                <div class="etab-nom">{{ $etab['nom'] }}</div>
                @if ($etab['slogan'])<div class="muted">{{ $etab['slogan'] }}</div>@endif
                <div class="small muted" style="margin-top: 2px;">
                    {{ collect([$etab['adresse'], $etab['ville']])->filter()->implode(', ') }}<br>
                    {{ collect([$etab['telephone'] ? 'Tél. ' . $etab['telephone'] : null, $etab['email'], $etab['site']])->filter()->implode(' · ') }}
                </div>
            </td>
            <td style="width: 190px;">
                <div class="recu-box">
                    <div class="titre">REÇU DE PAIEMENT</div>
                    <div class="numero">N° {{ $paiement->numero_transaction }}</div>
                    <div class="small muted">du {{ $paiement->date_paiement->format('d/m/Y') }}</div>
                    <span class="badge">✓ VALIDÉ</span>
                </div>
            </td>
        </tr>
    </table>
    <div class="separateur"></div>

    {{-- ===== STAGIAIRE / PAIEMENT ===== --}}
    <table>
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 6px;">
                <div class="bloc-titre">STAGIAIRE</div>
                <table class="infos">
                    <tr><td class="lbl">Nom complet</td><td class="bold">{{ $stagiaire->nom_complet }}</td></tr>
                    <tr><td class="lbl">Matricule</td><td>{{ $stagiaire->matricule }}</td></tr>
                    <tr><td class="lbl">Filière</td><td>{{ $stagiaire->filiere->nom ?? '—' }}</td></tr>
                    <tr><td class="lbl">Classe</td><td>{{ $stagiaire->classe->nom ?? '—' }}</td></tr>
                    <tr><td class="lbl">Année scolaire</td><td>{{ $situation['annee']->nom ?? '—' }}</td></tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 6px;">
                <div class="bloc-titre">PAIEMENT</div>
                <table class="infos">
                    <tr><td class="lbl">Nature</td><td>{{ $paiement->type_libelle }}</td></tr>
                    <tr><td class="lbl">Mode de règlement</td><td>{{ $paiement->methode_libelle }}</td></tr>
                    @if ($paiement->reference_externe)
                        <tr><td class="lbl">{{ $paiement->methode_paiement === 'cheque' ? 'N° de chèque' : 'Référence' }}</td><td>{{ $paiement->reference_externe }}</td></tr>
                    @endif
                    @if ($banque)
                        <tr><td class="lbl">Banque</td><td>{{ $banque }}</td></tr>
                    @endif
                    <tr><td class="lbl">Validé le</td><td>{{ $paiement->valide_at ? $paiement->valide_at->format('d/m/Y à H:i') : '—' }}</td></tr>
                    <tr><td class="lbl">Encaissé par</td><td>{{ $paiement->validateur->name ?? $paiement->user->name ?? '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== DÉTAIL ===== --}}
    <table class="detail" style="margin-top: 10px;">
        <thead>
            <tr><th>Désignation</th><th style="width: 22%;">Échéance</th><th class="right" style="width: 24%;">Montant réglé</th></tr>
        </thead>
        <tbody>
            @forelse ($paiement->echeanciers as $echeance)
                <tr>
                    <td>{{ ucfirst($echeance->titre) }}</td>
                    <td>{{ \Carbon\Carbon::parse($echeance->date_echeance)->format('d/m/Y') }}</td>
                    <td class="right">{{ $dh($echeance->pivot->montant_affecte) }}</td>
                </tr>
            @empty
                <tr><td colspan="2">{{ $paiement->description ? ucfirst(mb_strtolower($paiement->description)) : $paiement->type_libelle }}</td><td class="right">{{ $dh($paiement->montant) }}</td></tr>
            @endforelse
            <tr class="total"><td colspan="2" class="right">TOTAL</td><td class="right">{{ $dh($paiement->montant) }}</td></tr>
        </tbody>
    </table>

    {{-- ===== MONTANT ===== --}}
    <div class="montant">
        <table>
            <tr>
                <td>
                    <div class="small muted">Arrêté le présent reçu à la somme de :</div>
                    <div class="lettres bold">{{ NumberHelper::montantEnLettres($paiement->montant) }}.</div>
                </td>
                <td class="right" style="width: 40%;"><span class="valeur">{{ $dh($paiement->montant) }}</span></td>
            </tr>
        </table>
    </div>

    {{-- ===== SITUATION FINANCIÈRE ===== --}}
    <div class="bloc-titre" style="margin-top: 10px;">
        SITUATION FINANCIÈRE {{ $situation['annee'] ? '— ' . mb_strtoupper($situation['annee']->nom) : '' }}
        <span style="font-weight: normal;">(au {{ now()->format('d/m/Y') }})</span>
    </div>
    <table class="situation">
        <tr>
            <td><div class="small muted">Total dû</div><div class="chiffre">{{ $dh($situation['total_du']) }}</div></td>
            <td><div class="small muted">Total réglé</div><div class="chiffre solde-ok">{{ $dh($situation['total_paye']) }}</div></td>
            <td><div class="small muted">Reste à payer</div><div class="chiffre {{ $situation['reste'] > 0 ? 'reste' : 'solde-ok' }}">{{ $dh($situation['reste']) }}</div></td>
            <td>
                <div class="small muted">Prochaine échéance</div>
                @if ($situation['prochaine'])
                    <div class="chiffre" style="font-size: 9pt;">{{ \Carbon\Carbon::parse($situation['prochaine']->date_echeance)->format('d/m/Y') }}</div>
                    <div class="small">{{ $dh($situation['prochaine']->montant_restant) }}</div>
                @else
                    <div class="chiffre solde-ok" style="font-size: 9pt;">Soldé ✓</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ===== SIGNATURES ===== --}}
    <table class="signatures">
        <tr>
            <td><div class="ligne">Signature du stagiaire / tuteur</div></td>
            <td><div class="ligne">Cachet et signature de l'établissement</div></td>
        </tr>
    </table>

    @if ($etab['recu_footer_text'] || $etab['recu_conditions'])
        <div class="center small muted" style="margin-top: 10px;">
            @if ($etab['recu_footer_text'])<div class="bold">{{ $etab['recu_footer_text'] }}</div>@endif
            @if ($etab['recu_conditions'])<div>{{ $etab['recu_conditions'] }}</div>@endif
        </div>
    @endif

    {{-- ===== PIED DE PAGE ===== --}}
    <div class="pied">
        {{ $etab['nom'] }}@if ($mentions) — {{ $mentions }}@endif
        @if ($etab['rib_etablissement']) · RIB {{ $etab['banque_nom'] ? $etab['banque_nom'] . ' ' : '' }}{{ $etab['rib_etablissement'] }}@endif
        <br>Reçu édité le {{ now()->format('d/m/Y à H:i') }} — à conserver
    </div>
</body>
</html>
