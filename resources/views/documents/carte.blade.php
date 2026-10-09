<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><title>{{ $document->numero }}</title>
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 6.5pt; color: #1f2937; }
    .bandeau { background: #4f46e5; color: #fff; padding: 5px 8px; height: 24px; }
    .bandeau td { vertical-align: middle; color: #fff; }
    .nom-etab { font-size: 7.5pt; font-weight: bold; }
    .contenu { padding: 6px 8px 0; }
    .photo { width: 52px; height: 62px; border: 1px solid #d1d5db; background: #f3f4f6; text-align: center; vertical-align: middle; }
    .lbl { color: #6b7280; font-size: 5.5pt; text-transform: uppercase; }
    .val { font-weight: bold; font-size: 7pt; margin-bottom: 2px; }
    .pied { position: absolute; bottom: 4px; left: 8px; right: 8px; font-size: 5pt; color: #6b7280; }
</style></head>
<body>
    <table class="bandeau" style="width: 100%;"><tr>
        @if ($logo)<td style="width: 26px;"><img src="{{ $logo }}" style="max-width: 22px; max-height: 22px;"></td>@endif
        <td><div class="nom-etab">{{ $etab['nom'] }}</div><div style="font-size: 5.5pt;">CARTE DE STAGIAIRE {{ $c['annee'] }}</div></td>
    </tr></table>

    <table class="contenu" style="width: 100%;"><tr>
        <td style="width: 60px; vertical-align: top;">
            <div class="photo">
                @if ($photo)<img src="{{ $photo }}" style="width: 52px; height: 62px;">@else<span style="color: #9ca3af; font-size: 5.5pt;">PHOTO</span>@endif
            </div>
        </td>
        <td style="vertical-align: top; padding-left: 6px;">
            <div class="lbl">Nom et prénom</div><div class="val">{{ $c['nom'] }} {{ $c['prenom'] }}</div>
            <div class="lbl">Matricule</div><div class="val">{{ $c['matricule'] }}</div>
            <div class="lbl">Filière / classe</div><div class="val">{{ $c['filiere'] ?? '—' }} — {{ $c['classe'] ?? '—' }}</div>
            @if (!empty($c['annee_fin']))<div class="lbl">Valable jusqu'au</div><div class="val">{{ $c['annee_fin'] }}</div>@endif
        </td>
    </tr></table>

    <div class="pied">N° {{ $document->numero }} · vérification : code {{ $document->code_verification }}</div>
</body></html>
