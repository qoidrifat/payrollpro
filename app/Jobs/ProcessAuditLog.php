<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAuditLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Low-priority queue for audit processing */
    public string $queue = 'audit';

    public int $tries = 2;
    public int $backoff = 15;

    public function __construct(
        public readonly string $event,
        public readonly ?int $userId = null,
        public readonly array $context = [],
        public readonly ?string $subjectType = null,
        public readonly ?int $subjectId = null,
    ) {}

    public function handle(): void
    {
        \App\Models\ActivityLog::create([
            'user_id'      => $this->userId,
            'event'        => $this->event,
            'ip_address'   => $this->context['ip_address'] ?? request()?->ip(),
            'user_agent'   => $this->context['user_agent'] ?? request()?->userAgent(),
            'details'      => json_encode($this->context, JSON_THROW_ON_ERROR),
            'subject_type' => $this->subjectType,
            'subject_id'   => $this->subjectId,
        ]);

        Log::debug('Audit log processed via queue', [
            'event'  => $this->event,
            'user_id'=> $this->userId,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessAuditLog job failed', [
            'event'  => $this->event,
            'error'  => $e->getMessage(),
            'trace'  => $e->getTraceAsString(),
        ]);
    }
}
