<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurgeActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:purge {--days=90 : Number of days to retain logs} {--dry-run : Show how many records would be deleted without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge activity logs older than the specified number of days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);
        $isDryRun = (bool) $this->option('dry-run');

        $query = ActivityLog::where('created_at', '<', $cutoff);
        $count = $query->count();

        if ($count === 0) {
            $this->info("No activity logs older than {$days} days found.");

            return Command::SUCCESS;
        }

        if ($isDryRun) {
            $this->warn("[DRY-RUN] Would purge {$count} activity logs older than {$days} days (before {$cutoff->toDateString()}).");

            return Command::SUCCESS;
        }

        $this->info("Purging {$count} activity logs older than {$days} days...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        // Chunk delete to avoid long-running transactions
        $totalDeleted = 0;
        ActivityLog::where('created_at', '<', $cutoff)
            ->chunk(200, function ($logs) use ($bar, &$totalDeleted) {
                foreach ($logs as $log) {
                    $log->delete();
                    $totalDeleted++;
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Purged {$totalDeleted} old activity logs.");

        Log::info('Activity logs purged', [
            'deleted_count' => $totalDeleted,
            'cutoff_date'   => $cutoff->toDateString(),
            'days_retained' => $days,
        ]);

        return Command::SUCCESS;
    }
}
