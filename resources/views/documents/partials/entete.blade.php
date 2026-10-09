{{-- En-tête officiel commun aux documents A4 (variables : $etab, $logo) --}}
<table style="width: 100%; border-collapse: collapse;">
    <tr>
        @if (!empty($logo))
            <td style="width: 80px; vertical-align: middle;"><img src="{{ $logo }}" style="max-width: 70px; max-height: 70px;"></td>
        @endif
        <td style="vertical-align: middle;">
            <div style="font-size: 15pt; font-weight: bold; color: #4f46e5;">{{ $etab['nom'] }}</div>
            @if (!empty($etab['slogan']))<div style="color: #6b7280;">{{ $etab['slogan'] }}</div>@endif
            <div style="font-size: 8pt; color: #6b7280; margin-top: 2px;">
                {{ collect([$etab['adresse'] ?? null, $etab['ville'] ?? null])->filter()->implode(', ') }}<br>
                {{ collect([!empty($etab['telephone']) ? 'Tél. ' . $etab['telephone'] : null, $etab['email'] ?? null, $etab['site'] ?? null])->filter()->implode(' · ') }}
            </div>
        </td>
    </tr>
</table>
<div style="border-bottom: 2px solid #4f46e5; margin: 8px 0 0;"></div>
