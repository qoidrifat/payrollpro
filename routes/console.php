<?php

use App\Console\Commands\DatabaseBackup;
use App\Console\Commands\PurgeActivityLogs;
use Illuminate\Support\Facades\Schedule;

// ─── Schedule Commands ─────────────────────────────────────────────────
// These tasks run automatically when the Laravel scheduler is active
// (add `* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1` to crontab)

// Purge activity logs older than 90 days — runs daily at midnight
Schedule::command(PurgeActivityLogs::class, ['--days' => 90])
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule-purge-logs.log'));

// Database backup — runs daily at 02:00
Schedule::command(DatabaseBackup::class, ['--keep-days' => 30])
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule-backup.log'));

// Queue worker restart — prevents memory leaks
Schedule::command('queue:restart')
    ->hourly()
    ->appendOutputTo(storage_path('logs/schedule-queue-restart.log'));
