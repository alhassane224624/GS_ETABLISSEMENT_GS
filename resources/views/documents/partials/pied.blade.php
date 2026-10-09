{{-- Signature + vérification + mentions légales (variables : $etab, $document, $mentions) --}}
<table class="signature">
    <tr>
        <td style="width: 55%;">
            <div class="verif">
                <strong>Vérification de l'authenticité</strong><br>
                N° {{ $document->numero }} — code <strong>{{ $document->code_verification }}</strong><br>
                {{ $document->url_verification }}
            </div>
        </td>
        <td style="text-align: center;">
            Fait à {{ $etab['ville'] ?: '……………' }}, le {{ $document->created_at->format('d/m/Y') }}<br>
            <strong>{{ $etab['directeur_titre'] ?: 'La Direction' }}</strong><br>
            <span style="color: #6b7280; font-size: 8.5pt;">(cachet et signature)</span>
            <div style="height: 60px;"></div>
            {{ $etab['directeur'] ?? '' }}
        </td>
    </tr>
</table>
<div class="pied">
    {{ $etab['nom'] }}@if ($mentions) — {{ $mentions }}@endif
</div>
