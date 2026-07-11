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
        $driver = $dbConfig['driver'] ?? $connection;

        if (! in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
            $this->warn("Database backup supports MySQL/MariaDB/PostgreSQL only (current: {$driver}). Skipping.");

            return Command::FAILURE;
        }

        $database = $dbConfig['database'];
        $keepDays = (int) $this->option('keep-days');

        $filename = 'backup-' . now()->format('Y-m-d-H-i-s') . '.sql';
        $backupDir = 'backups';

        // Ensure backup directory exists
        Storage::disk('local')->makeDirectory($backupDir);

        $backupPath = storage_path("app/{$backupDir}/{$filename}");

        [$command, $env] = $driver === 'pgsql'
            ? $this->buildPgDumpCommand($dbConfig, $backupPath)
            : $this->buildMysqlDumpCommand($dbConfig, $backupPath);

        $this->info("Creating database backup: {$filename}");

        $output = null;
        $returnCode = null;
        $this->execWithEnv($command, $env, $output, $returnCode);

        // pg_dump/mysqldump return 0 on success. A 0-byte file also means a
        // silent failure (e.g. auth error swallowed) — treat as failure.
        if ($returnCode !== 0 || ! file_exists($backupPath) || filesize($backupPath) === 0) {
            $this->error('Database backup failed: ' . implode("\n", (array) $output));
            Log::error('Database backup failed', [
                'database'    => $database,
                'driver'      => $driver,
                'return_code' => $returnCode,
                'output'      => $output,
            ]);

            // Remove empty/partial file so it is not mistaken for a valid backup
            if (file_exists($backupPath) && filesize($backupPath) === 0) {
                @unlink($backupPath);
            }

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
            'driver'    => $driver,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Build the mysqldump command and its environment.
     *
     * The password is passed via the MYSQL_PWD env var rather than on the
     * command line so it does not leak into the process list.
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function buildMysqlDumpCommand(array $dbConfig, string $backupPath): array
    {
        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = $dbConfig['port'] ?? 3306;
        $database = $dbConfig['database'];
        $username = $dbConfig['username'] ?? 'root';
        $password = (string) ($dbConfig['password'] ?? '');

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --single-transaction --quick %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($backupPath)
        );

        return [$command, $password !== '' ? ['MYSQL_PWD' => $password] : []];
    }

    /**
     * Build the pg_dump command and its environment.
     *
     * The password is passed via the PGPASSWORD env var (pg_dump has no
     * password flag). Errors go to a sibling .err file so a failed dump does
     * not pollute the .sql output with diagnostic text.
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function buildPgDumpCommand(array $dbConfig, string $backupPath): array
    {
        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = $dbConfig['port'] ?? 5432;
        $database = $dbConfig['database'];
        $username = $dbConfig['username'] ?? 'postgres';
        $password = (string) ($dbConfig['password'] ?? '');

        $command = sprintf(
            'pg_dump --host=%s --port=%s --username=%s --no-owner --no-privileges --dbname=%s --file=%s 2>%s',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($backupPath),
            escapeshellarg($backupPath . '.err')
        );

        return [$command, $password !== '' ? ['PGPASSWORD' => $password] : []];
    }

    /**
     * Run a shell command with extra environment variables, inheriting the
     * current environment for everything else.
     *
     * @param  array<string, string>  $env
     */
    private function execWithEnv(string $command, array $env, ?array &$output, ?int &$returnCode): void
    {
        if ($env === []) {
            exec($command, $output, $returnCode);

            return;
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, null, array_merge(getenv() ?: [], $env));

        if (! is_resource($process)) {
            $returnCode = 1;
            $output = ['Failed to start backup process.'];

            return;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);
        $output = array_filter(array_merge(
            explode("\n", trim((string) $stdout)),
            explode("\n", trim((string) $stderr)),
        ), fn ($line) => $line !== '');
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
