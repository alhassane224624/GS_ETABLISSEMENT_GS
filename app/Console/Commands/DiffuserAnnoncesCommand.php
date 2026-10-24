<?php

namespace App\Console\Commands;

use App\Services\DiffusionAnnonces;
use Illuminate\Console\Command;

class DiffuserAnnoncesCommand extends Command
{
    protected $signature = 'annonces:diffuser';
    protected $description = 'Notifie les destinataires des annonces programmées arrivées à leur date de publication';

    public function handle(DiffusionAnnonces $diffusion): int
    {
        $total = $diffusion->diffuserEnAttente();
        $this->info("{$total} notification(s) d'annonce envoyée(s).");

        return self::SUCCESS;
    }
}
