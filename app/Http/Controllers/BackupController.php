<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Carbon\Carbon;

class BackupController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $backups = $this->getAvailableBackups();
        return view('backups.index', compact('backups'));
    }

    public function create(Request $request)
    {
        try {
            $type = $request->get('type', 'full'); // full, database, files
            $filename = $this->generateBackup($type);
            
            return response()->json([
                'success' => true,
                'message' => 'Sauvegarde créée avec succès',
                'filename' => $filename
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ], 500);
        }
    }

    public function download($filename)
    {
        $filename = basename($filename);
        if (!Storage::disk('backups')->exists($filename)) {
            abort(404, 'Fichier de sauvegarde non trouvé');
        }

        return Storage::disk('backups')->download($filename);
    }

    public function delete($filename)
    {
        $filename = basename($filename);
        try {
            Storage::disk('backups')->delete($filename);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function generateBackup($type = 'full')
    {
        $timestamp = now()->format('Y_m_d_H_i_s');
        $filename = "backup_{$type}_{$timestamp}";

        switch ($type) {
            case 'database':
                return $this->createDatabaseBackup($filename);
                
            case 'files':
                return $this->createFilesBackup($filename);
                
            case 'full':
            default:
                return $this->createFullBackup($filename);
        }
    }

    private function createDatabaseBackup($filename)
    {
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');
        
        $sqlFile = storage_path("app/backups/{$filename}.sql");
        
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbHost),
            escapeshellarg($dbName),
            escapeshellarg($sqlFile)
        );

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('Échec de la sauvegarde de la base de données: ' . $process->getErrorOutput());
        }

        // Compresser le fichier SQL
        $zipFile = storage_path("app/backups/{$filename}.zip");
        $zip = new \ZipArchive();
        
        if ($zip->open($zipFile, \ZipArchive::CREATE) === TRUE) {
            $zip->addFile($sqlFile, basename($sqlFile));
            $zip->close();
            unlink($sqlFile); // Supprimer le fichier SQL non compressé
            return "{$filename}.zip";
        }
        
        return "{$filename}.sql";
    }

    private function createFilesBackup($filename)
    {
        $zipFile = storage_path("app/backups/{$filename}.zip");
        $zip = new \ZipArchive();
        
        if ($zip->open($zipFile, \ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Impossible de créer le fichier ZIP');
        }

        // Ajouter les photos des stagiaires
        $photosPath = storage_path('app/public/photos');
        if (is_dir($photosPath)) {
            $this->addDirectoryToZip($zip, $photosPath, 'photos/');
        }

        // Ajouter les exports
        $exportsPath = storage_path('app/exports');
        if (is_dir($exportsPath)) {
            $this->addDirectoryToZip($zip, $exportsPath, 'exports/');
        }

        $zip->close();
        return "{$filename}.zip";
    }

    private function createFullBackup($filename)
    {
        // Créer d'abord la sauvegarde de la base de données
        $dbBackup = $this->createDatabaseBackup($filename . '_db');
        
        $zipFile = storage_path("app/backups/{$filename}.zip");
        $zip = new \ZipArchive();
        
        if ($zip->open($zipFile, \ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Impossible de créer le fichier ZIP complet');
        }

        // Ajouter la sauvegarde de la base de données
        $dbBackupPath = storage_path("app/backups/{$dbBackup}");
        if (file_exists($dbBackupPath)) {
            $zip->addFile($dbBackupPath, "database/{$dbBackup}");
        }

        // Ajouter les fichiers
        $photosPath = storage_path('app/public/photos');
        if (is_dir($photosPath)) {
            $this->addDirectoryToZip($zip, $photosPath, 'files/photos/');
        }

        $exportsPath = storage_path('app/exports');
        if (is_dir($exportsPath)) {
            $this->addDirectoryToZip($zip, $exportsPath, 'files/exports/');
        }

        // Ajouter des informations système
        $systemInfo = [
            'backup_date' => now()->toDateTimeString(),
            'app_version' => config('app.version', '1.0'),
            'backup_type' => 'full',
            'database_name' => config('database.connections.mysql.database'),
        ];
        
        $zip->addFromString('backup_info.json', json_encode($systemInfo, JSON_PRETTY_PRINT));
        $zip->close();

        // Nettoyer le fichier temporaire de la base de données
        if (file_exists($dbBackupPath)) {
            unlink($dbBackupPath);
        }

        return "{$filename}.zip";
    }

    private function addDirectoryToZip(\ZipArchive $zip, $dir, $zipPath = '')
    {
        if (!is_dir($dir)) return;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipPath . substr($filePath, strlen($dir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    private function getAvailableBackups()
    {
        $backupFiles = Storage::disk('backups')->files();
        $backups = collect();

        foreach ($backupFiles as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'zip' || 
                pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                
                $backups->push([
                    'filename' => $file,
                    'name' => pathinfo($file, PATHINFO_FILENAME),
                    'size' => $this->formatBytes(Storage::disk('backups')->size($file)),
                    'date' => Carbon::createFromTimestamp(Storage::disk('backups')->lastModified($file)),
                    'type' => $this->getBackupType($file),
                ]);
            }
        }

        return $backups->sortByDesc('date');
    }

    private function getBackupType($filename)
    {
        if (strpos($filename, '_db_') !== false) return 'Database';
        if (strpos($filename, '_files_') !== false) return 'Files';
        if (strpos($filename, '_full_') !== false) return 'Complete';
        return 'Unknown';
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function scheduleBackup(Request $request)
    {
        $request->validate([
            'frequency' => 'required|in:daily,weekly,monthly',
            'type' => 'required|in:database,files,full',
            'time' => 'required|date_format:H:i',
        ]);

        // Créer ou mettre à jour la tâche cron
        $this->updateCronSchedule($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Sauvegarde automatique programmée'
        ]);
    }

    private function updateCronSchedule($schedule)
    {
        $frequency = $schedule['frequency'];
        $type = $schedule['type'];
        $time = $schedule['time'];
        
        [$hour, $minute] = explode(':', $time);

        $cronExpression = match($frequency) {
            'daily' => "{$minute} {$hour} * * *",
            'weekly' => "{$minute} {$hour} * * 0",
            'monthly' => "{$minute} {$hour} 1 * *",
        };

        // Sauvegarder dans la configuration
        config(['gestion.backup.schedule' => [
            'enabled' => true,
            'frequency' => $frequency,
            'type' => $type,
            'cron' => $cronExpression,
        ]]);

        // Créer la commande artisan si elle n'existe pas
        $this->ensureBackupCommand();
    }

    private function ensureBackupCommand()
    {
        // Cette méthode s'assurerait que la commande artisan backup:run existe
        // et est programmée dans le cron du serveur
    }
}

