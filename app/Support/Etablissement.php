<?php

namespace App\Support;

use App\Models\ConfigurationPaiement;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Identité de l'établissement (en-têtes des reçus, bulletins, relevés…).
 * Stockée dans la table configuration_paiements (clés "etablissement_*").
 */
class Etablissement
{
    public const CHAMPS = [
        'etablissement_nom'          => 'Nom de l\'établissement',
        'etablissement_slogan'       => 'Slogan / sous-titre',
        'etablissement_adresse'      => 'Adresse',
        'etablissement_ville'        => 'Ville',
        'etablissement_telephone'    => 'Téléphone',
        'etablissement_email'        => 'E-mail',
        'etablissement_site'         => 'Site web',
        'etablissement_ice'          => 'ICE',
        'etablissement_rc'           => 'Registre de commerce (RC)',
        'etablissement_if'           => 'Identifiant fiscal (IF)',
        'etablissement_autorisation' => 'N° d\'autorisation',
        'etablissement_logo'         => 'Logo',
        'etablissement_directeur'    => 'Nom du signataire',
        'etablissement_directeur_titre' => 'Fonction du signataire',
        'banque_nom'                 => 'Banque',
        'rib_etablissement'          => 'RIB',
        'recu_footer_text'           => 'Texte en pied de reçu',
        'recu_conditions'            => 'Conditions (petits caractères)',
        'seuil_admission'            => 'Seuil d\'admission (moyenne annuelle)',
    ];

    /** Mémoire pour la requête en cours (pas de cache persistant : toujours à jour) */
    private static ?array $infos = null;

    /** Toutes les infos sous forme de tableau [cle_courte => valeur] */
    public static function infos(): array
    {
        if (self::$infos !== null) {
            return self::$infos;
        }

        $valeurs = Schema::hasTable('configuration_paiements')
            ? ConfigurationPaiement::whereIn('key', array_keys(self::CHAMPS))->pluck('value', 'key')->all()
            : [];

        $infos = [];
        foreach (array_keys(self::CHAMPS) as $cle) {
            $infos[str_replace('etablissement_', '', $cle)] = filled($valeurs[$cle] ?? null) ? trim($valeurs[$cle]) : null;
        }

        $infos['configure'] = filled($infos['nom']);
        if (!$infos['configure']) {
            // Jamais "Laravel" sur un document officiel
            $appName = config('app.name');
            $infos['nom'] = ($appName && strtolower($appName) !== 'laravel') ? $appName : 'Établissement';
        }

        return self::$infos = $infos;
    }

    public static function estConfigure(): bool
    {
        return self::infos()['configure'];
    }

    public static function get(string $cle, $defaut = null)
    {
        return self::infos()[$cle] ?? $defaut;
    }

    public static function enregistrer(array $donnees): void
    {
        foreach ($donnees as $cle => $valeur) {
            if (array_key_exists($cle, self::CHAMPS)) {
                ConfigurationPaiement::set($cle, $valeur);
            }
        }
        self::$infos = null;
    }

    /** URL publique du logo (pages web) */
    public static function logoUrl(): ?string
    {
        $logo = self::get('logo');
        return $logo && Storage::disk('public')->exists($logo) ? Storage::url($logo) : null;
    }

    /** Logo encodé en base64 pour DomPDF (pas d'accès HTTP dans les PDF) */
    public static function logoBase64(): ?string
    {
        $logo = self::get('logo');
        if (!$logo || !Storage::disk('public')->exists($logo)) {
            return null;
        }
        $mime = Storage::disk('public')->mimeType($logo) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($logo));
    }

    /** Ligne des mentions légales : "ICE … · RC … · IF …" */
    public static function mentionsLegales(): string
    {
        return collect([
            'ICE' => self::get('ice'),
            'RC' => self::get('rc'),
            'IF' => self::get('if'),
            'Autorisation' => self::get('autorisation'),
        ])->filter()->map(fn ($v, $k) => "{$k} : {$v}")->implode(' · ');
    }
}
