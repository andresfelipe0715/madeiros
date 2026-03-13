<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-database';

    /**
     * The console command description.
     *
     * @var string
     */
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Performs a MySQL dump and stores it in the local backups folder.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');

        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'backup-'.now()->format('Y-m-d-His').'.sql';
        $filePath = $backupDir.DIRECTORY_SEPARATOR.$filename;

        $this->info("Creating backup to: $filePath");

        // Build command as an array to avoid shell redirect issues across platforms
        $command = [
            'mysqldump',
            '--user='.$username,
            '--password='.$password,
            '--host='.$host,
            '--skip-ssl',
            $database,
        ];

        // Use proc_open to write output directly to file — avoids shell `>` redirect
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['file', $filePath, 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes);

        if (! is_resource($process)) {
            $this->error('Failed to start mysqldump process.');

            return;
        }

        fclose($pipes[0]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $returnVar = proc_close($process);

        if ($returnVar === 0) {
            $this->info("Backup completed successfully to SQL: $filename");
            $this->compressBackup($filePath);
            $this->cleanOldBackups($backupDir);
        } else {
            $this->error("Backup failed: $stderr");
        }
    }

    private function compressBackup(string $filePath): void
    {
        $zipPath = $filePath . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            $zip->addFile($filePath, basename($filePath));
            $zip->close();

            $this->info("Compression completed: " . basename($zipPath));

            // Delete the original SQL file
            unlink($filePath);
            $this->line("Temporary SQL file removed.");
        } else {
            $this->error("Failed to create ZIP archive.");
        }
    }

    private function cleanOldBackups(string $backupDir): void
    {
        // Target both old .sql files and new .zip files for cleanup
        $files = glob($backupDir.DIRECTORY_SEPARATOR.'backup-*.{sql,zip}', GLOB_BRACE);
        $now = time();
        $retentionDays = 7;

        foreach ($files as $file) {
            if ($now - filemtime($file) >= ($retentionDays * 86400)) {
                unlink($file);
                $this->line('Deleted old backup: '.basename($file));
            }
        }
    }
}
