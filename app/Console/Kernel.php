<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Commandes Artisan personnalisées.
     */
    protected $commands = [
        \App\Console\Commands\CheckRetardsPaiements::class,
    ];

    /**
     * Définir le planning des tâches planifiées.
     */
    protected function schedule(Schedule $schedule)
    {
        // Passe en "en_retard" les échéances dépassées — chaque jour à 8h00
        $schedule->command('paiements:check-retards')->dailyAt('08:00')->withoutOverlapping();

        // Rappels avant échéance — chaque jour à 9h00
        $schedule->command('paiements:rappels')->dailyAt('09:00')->withoutOverlapping();

        // Signalement des absences non justifiées — chaque soir
        $schedule->command('absences:check-unjustified')->dailyAt('18:00');

        // Nettoyage des notifications de plus de 30 jours — dimanche 2h00
        $schedule->command('notifications:clean --days=30')->weeklyOn(0, '02:00');

        // Sauvegarde complète — chaque nuit à 1h00
        $schedule->command('backup:run full')->dailyAt('01:00')->withoutOverlapping();
    }

    /**
     * Enregistre les commandes pour l'application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
