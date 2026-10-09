<?php

namespace App\Helpers;

class NumberHelper
{
    /**
     * Convertir un nombre en lettres (français)
     */
    public static function convertirEnLettres($nombre)
    {
        $nombre = floatval($nombre);
        $entier = floor($nombre);
        $decimal = round(($nombre - $entier) * 100);

        $result = self::nombreEnLettres($entier);
        
        if ($decimal > 0) {
            $result .= ' et ' . self::nombreEnLettres($decimal) . ' centimes';
        }

        return $result;
    }

    /**
     * Montant en toutes lettres avec majuscule : "Deux cents dirhams et cinquante centimes"
     */
    public static function montantEnLettres($montant, string $devise = 'dirhams'): string
    {
        $montant = round((float) $montant, 2);
        $entier = (int) floor($montant);
        $centimes = (int) round(($montant - $entier) * 100);

        $texte = self::nombreEnLettres($entier) . ' ' . ($entier > 1 ? $devise : rtrim($devise, 's'));
        if ($centimes > 0) {
            $texte .= ' et ' . self::nombreEnLettres($centimes) . ' centime' . ($centimes > 1 ? 's' : '');
        }

        return mb_strtoupper(mb_substr($texte, 0, 1)) . mb_substr($texte, 1);
    }

    private static function nombreEnLettres($nombre)
    {
        if ($nombre == 0) {
            return 'zéro';
        }

        $unites = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'];
        $dizaines = ['', 'dix', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'];
        $exceptions = ['onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize'];

        if ($nombre < 10) {
            return $unites[$nombre];
        }

        if ($nombre >= 11 && $nombre <= 16) {
            return $exceptions[$nombre - 11];
        }

        if ($nombre == 10) {
            return 'dix';
        }

        if ($nombre < 20) {
            return 'dix-' . $unites[$nombre - 10];
        }

        if ($nombre < 70) {
            $diz = floor($nombre / 10);
            $unit = $nombre % 10;
            
            if ($unit == 0) {
                return $dizaines[$diz];
            }
            
            if ($unit == 1 && $diz != 8) {
                return $dizaines[$diz] . ' et un';
            }
            
            return $dizaines[$diz] . '-' . $unites[$unit];
        }

        if ($nombre < 80) {
            $unit = $nombre - 60;
            if ($unit == 11) {
                return 'soixante et onze';
            }
            if ($unit < 17 && $unit > 10) {
                return 'soixante-' . $exceptions[$unit - 11];
            }
            return 'soixante-' . self::nombreEnLettres($unit);
        }

        if ($nombre < 100) {
            $unit = $nombre - 80;
            if ($unit == 0) {
                return 'quatre-vingts';
            }
            return 'quatre-vingt-' . self::nombreEnLettres($unit);
        }

        if ($nombre < 1000) {
            $cent = floor($nombre / 100);
            $reste = $nombre % 100;
            
            $result = '';
            if ($cent == 1) {
                $result = 'cent';
            } else {
                $result = $unites[$cent] . ' cent';
            }
            
            if ($reste == 0 && $cent > 1) {
                $result .= 's';
            }
            
            if ($reste > 0) {
                $result .= ' ' . self::nombreEnLettres($reste);
            }
            
            return $result;
        }

        if ($nombre < 1000000) {
            $mille = floor($nombre / 1000);
            $reste = $nombre % 1000;
            
            $result = '';
            if ($mille == 1) {
                $result = 'mille';
            } else {
                // "deux cent mille", "quatre-vingt mille" : pas de « s » devant mille
                $result = preg_replace('/(cent|vingt)s$/', '$1', self::nombreEnLettres($mille)) . ' mille';
            }
            
            if ($reste > 0) {
                $result .= ' ' . self::nombreEnLettres($reste);
            }
            
            return $result;
        }

        if ($nombre < 1000000000) {
            $million = floor($nombre / 1000000);
            $reste = $nombre % 1000000;
            
            $result = self::nombreEnLettres($million) . ' million';
            if ($million > 1) {
                $result .= 's';
            }
            
            if ($reste > 0) {
                $result .= ' ' . self::nombreEnLettres($reste);
            }
            
            return $result;
        }

        return 'nombre trop grand';
    }
}