@php $dec = \App\Models\Inscription::DECISIONS; @endphp
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><title>PV {{ $classe->nom }}</title>
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; }
    html { margin: 12mm 12mm 14mm 12mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 7.5pt; color: #1f2937; }
    .titre { text-align: center; font-size: 13pt; font-weight: bold; letter-spacing: 1px; margin: 10px 0 2px; }
    .sous { text-align: center; color: #4b5563; margin-bottom: 8px; }
    table.pv { width: 100%; border-collapse: collapse; }
    table.pv th { background: #4f46e5; color: #fff; font-size: 6.5pt; padding: 3px 2px; border: 1px solid #4338ca; }
    table.pv td { border: 1px solid #d1d5db; padding: 3px 2px; text-align: center; }
    table.pv td.g { text-align: left; }
    .r { color: #b91c1c; } .v { color: #15803d; font-weight: bold; } .rat { font-size: 6pt; color: #1d4ed8; }
    .bilan td { padding: 2px 8px 2px 0; }
    .sign { width: 100%; margin-top: 18px; } .sign td { width: 33%; text-align: center; vertical-align: top; height: 55px; }
</style></head>
<body>
    @include('documents.partials.entete')

    <div class="titre">PROCÈS-VERBAL DE DÉLIBÉRATION</div>
    <div class="sous">
        Classe <strong>{{ $classe->nom }}</strong> — {{ $classe->filiere->nom ?? '' }}, {{ $classe->niveau->nom ?? '' }} —
        Année scolaire <strong>{{ $classe->anneeScolaire->nom ?? '' }}</strong> — seuil d'admission {{ number_format($seuil, 2, ',', ' ') }}/20
    </div>

    <table class="pv">
        <thead>
            <tr>
                <th>N°</th><th>Matricule</th><th>Nom et prénom</th>
                @foreach ($colonnes as $c)<th>{{ \Illuminate\Support\Str::limit($c['nom'], 14) }}<br>coef {{ rtrim(rtrim(number_format($c['coef'], 1, ',', ''), '0'), ',') }}</th>@endforeach
                <th>Moyenne<br>annuelle</th><th>Décision</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lignes as $n => $l)
                @php $i = $l['inscription']; @endphp
                <tr>
                    <td>{{ $n + 1 }}</td>
                    <td>{{ $i->stagiaire->matricule }}</td>
                    <td class="g">{{ $i->stagiaire->nom }} {{ $i->stagiaire->prenom }}</td>
                    @foreach ($colonnes as $c)
                        @php $m = $l['matieres'][$c['code']]['moyenne'] ?? null; $rt = $l['rattrapage'][$c['code']] ?? null; @endphp
                        <td class="{{ $m !== null && $m < 10 ? 'r' : '' }}">
                            {{ $m !== null ? number_format($m, 2, ',', '') : '—' }}
                            @if ($rt !== null)<div class="rat">R : {{ number_format($rt, 2, ',', '') }}</div>@endif
                        </td>
                    @endforeach
                    <td class="{{ $l['moyenne'] !== null && $l['moyenne'] >= $seuil ? 'v' : 'r' }}">{{ $l['moyenne'] !== null ? number_format($l['moyenne'], 2, ',', '') : '—' }}</td>
                    <td class="g">{{ $i->decision ? $dec[$i->decision] : 'En attente' }}@if ($i->observation)<div style="font-size: 6pt; color: #6b7280;">{{ $i->observation }}</div>@endif</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="bilan" style="margin-top: 8px;">
        <tr>
            <td><strong>Effectif :</strong> {{ $lignes->count() }}</td>
            @foreach ($dec as $k => $v)<td><strong>{{ $v }} :</strong> {{ $stats[$k] ?? 0 }}</td>@endforeach
            <td><strong>En attente :</strong> {{ $stats['en_attente'] ?? 0 }}</td>
        </tr>
    </table>
    <div style="font-size: 6.5pt; color: #6b7280; margin-top: 3px;">Moyennes de matière = moyenne des bulletins validés de l'année ; « R » = note de rattrapage (sur 20), retenue si meilleure.</div>

    <table class="sign">
        <tr>
            <td>Fait à {{ $etab['ville'] ?: '……………' }}, le {{ now()->format('d/m/Y') }}<br><strong>Le président du jury</strong></td>
            <td><strong>Les membres du jury</strong></td>
            <td><strong>{{ $etab['directeur_titre'] ?: 'La Direction' }}</strong><br>{{ $etab['directeur'] ?? '' }}</td>
        </tr>
    </table>
</body></html>
