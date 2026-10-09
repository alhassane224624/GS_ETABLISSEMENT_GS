<?php

// app/Providers/AppServiceProvider.php - Mise à jour
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Utiliser Bootstrap pour la pagination
        Paginator::useBootstrap();

        // Dates en français (jours, mois) dans toute l'application
        \Carbon\Carbon::setLocale(config('app.locale'));

        // Longueur par défaut des chaînes pour les anciennes versions MySQL
        Schema::defaultStringLength(191);

        // Enregistrer les macros personnalisées
        $this->registerCustomMacros();
    }

    private function registerCustomMacros()
    {
        // Macro pour formater les tailles de fichiers
        \Illuminate\Support\Str::macro('formatBytes', function ($bytes, $precision = 2) {
            $units = array('B', 'KB', 'MB', 'GB', 'TB');
            
            for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                $bytes /= 1024;
            }
            
            return round($bytes, $precision) . ' ' . $units[$i];
        });

        // Macro pour les moyennes avec coefficients
        \Illuminate\Database\Eloquent\Collection::macro('averageWeighted', function ($valueKey, $weightKey) {
            $totalValue = $this->sum(function ($item) use ($valueKey, $weightKey) {
                return $item[$valueKey] * $item[$weightKey];
            });
            
            $totalWeight = $this->sum($weightKey);
            
            return $totalWeight > 0 ? $totalValue / $totalWeight : 0;
        });
    }
}
