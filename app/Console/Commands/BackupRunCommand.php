<?php
// Console/Commands/BackupRunCommand.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\BackupController;
use Illuminate\Support\Facades\Storage;

class BackupRunCommand extends Command
{
    protected $signature = 'backup:run {type=full}';
    protected $description = 'Execute automatic backup';

    public function handle()
    {
        $type = $this->argument('type');
        $backupController = new BackupController();
        
        try {
            $filename = $backupController->generateBackup($type);
            $this->info("Sauvegarde créée: {$filename}");
            
            // Nettoyer les anciennes sauvegardes (garder seulement les 10 dernières)
            $this->cleanOldBackups();
            
        } catch (\Exception $e) {
            $this->error("Erreur lors de la sauvegarde: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }

    private function cleanOldBackups()
    {
        $backups = Storage::disk('backups')->files();
        $backupFiles = collect($backups)
            ->filter(fn($file) => pathinfo($file, PATHINFO_EXTENSION) === 'zip')
            ->sortByDesc(fn($file) => Storage::disk('backups')->lastModified($file))
            ->skip(10); // Garder les 10 plus récentes

        foreach ($backupFiles as $file) {
            Storage::disk('backups')->delete($file);
            $this->info("Ancienne sauvegarde supprimée: {$file}");
        }
    }
}