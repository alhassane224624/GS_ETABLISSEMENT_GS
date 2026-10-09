<?php

namespace App\Http\Controllers;

use App\Support\Etablissement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ParametreController extends Controller
{
    public function edit()
    {
        return view('parametres.etablissement', [
            'infos' => Etablissement::infos(),
            'logoUrl' => Etablissement::logoUrl(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'etablissement_nom'          => 'required|string|max:150',
            'etablissement_slogan'       => 'nullable|string|max:150',
            'etablissement_adresse'      => 'nullable|string|max:255',
            'etablissement_ville'        => 'nullable|string|max:100',
            'etablissement_telephone'    => 'nullable|string|max:50',
            'etablissement_email'        => 'nullable|email|max:150',
            'etablissement_site'         => 'nullable|string|max:150',
            'etablissement_ice'          => 'nullable|string|max:30',
            'etablissement_rc'           => 'nullable|string|max:30',
            'etablissement_if'           => 'nullable|string|max:30',
            'etablissement_autorisation' => 'nullable|string|max:50',
            'etablissement_directeur'    => 'nullable|string|max:150',
            'etablissement_directeur_titre' => 'nullable|string|max:100',
            'banque_nom'                 => 'nullable|string|max:100',
            'rib_etablissement'          => 'nullable|string|max:40',
            'recu_footer_text'           => 'nullable|string|max:255',
            'recu_conditions'            => 'nullable|string|max:500',
            'seuil_admission'            => 'nullable|numeric|min:0|max:20',
            'seuil_rattrapage'           => 'nullable|numeric|min:0|max:20|lte:seuil_admission',
            'logo'                       => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
            'supprimer_logo'             => 'nullable|boolean',
        ]);

        $ancienLogo = Etablissement::get('logo');

        if ($request->hasFile('logo')) {
            $validated['etablissement_logo'] = $request->file('logo')->store('etablissement', 'public');
        } elseif ($request->boolean('supprimer_logo')) {
            $validated['etablissement_logo'] = null;
        }

        if (array_key_exists('etablissement_logo', $validated) && $ancienLogo) {
            Storage::disk('public')->delete($ancienLogo);
        }

        unset($validated['logo'], $validated['supprimer_logo']);
        Etablissement::enregistrer($validated);

        if (!Etablissement::estConfigure()) {
            return back()->withInput()->with('error', 'L\'enregistrement a échoué : le nom n\'a pas pu être relu en base.');
        }

        return back()->with('success', 'Paramètres enregistrés. Les prochains reçus afficheront « ' . Etablissement::get('nom') . ' ».');
    }
}
