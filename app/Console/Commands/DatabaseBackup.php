<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--keep-days=30 : Number of days to retain backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup dump';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");

        if ($connection !== 'mysql') {
            $this->warn("Database backup is currently only supported for MySQL (current: {$connection}). Skipping.");

            return Command::FAILURE;
        }

        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = $dbConfig['port'] ?? 3306;
        $database = $dbConfig['database'];
        $username = $dbConfig['username'] ?? 'root';
        $password = $dbConfig['password'] ?? '';
        $keepDays = (int) $this->option('keep-days');

        $filename = 'backup-' . now()->format('Y-m-d-H-i-s') . '.sql';
        $backupDir = 'backups';

        // Ensure backup directory exists
        Storage::disk('local')->makeDirectory($backupDir);

        $backupPath = storage_path("app/{$backupDir}/{$filename}");

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '--password=' . escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($backupPath)
        );

        $this->info("Creating database backup: {$filename}");

        $output = null;
        $returnCode = null;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Database backup failed: ' . implode("\n", $output));
            Log::error('Database backup failed', [
                'database' => $database,
                'output'   => $output,
            ]);

            return Command::FAILURE;
        }

        $fileSize = filesize($backupPath);
        $this->info("Backup created successfully: {$filename} ({$this->formatBytes($fileSize)})");

        // Purge old backups
        $this->purgeOldBackups($backupDir, $keepDays);

        Log::info('Database backup completed', [
            'filename'  => $filename,
            'size'      => $fileSize,
            'database'  => $database,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Remove backups older than the retention period.
     */
    private function purgeOldBackups(string $backupDir, int $keepDays): void
    {
        $cutoff = now()->subDays($keepDays);
        $files = Storage::disk('local')->files($backupDir);
        $deleted = 0;

        foreach ($files as $file) {
            $timestamp = Storage::disk('local')->lastModified($file);
            if ($timestamp && $timestamp < $cutoff->timestamp) {
                Storage::disk('local')->delete($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("Purged {$deleted} backup(s) older than {$keepDays} days.");
        }
    }

    /**
     * Format bytes to human-readable string.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}
