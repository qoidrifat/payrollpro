<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use App\Notifications\AttendanceAlertNotification;
use App\Notifications\PayrollProcessedNotification;
use App\Notifications\PayslipGeneratedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'notifications';
    public int $tries = 3;
    public int $backoff = 10;

    /**
     * Create a new notification job.
     *
     * Supported types:
     * - 'approval'   → ApprovalRequestedNotification
     * - 'payroll'    → PayrollProcessedNotification
     * - 'payslip'    → PayslipGeneratedNotification
     * - 'attendance' → AttendanceAlertNotification
     * - 'raw'        → Send raw subject/body via mail
     */
    public function __construct(
        public readonly string $type,
        public readonly array $recipients,   // User model IDs
        public readonly string $subject,
        public readonly string $body,
        public readonly array $data = [],
    ) {}

    public function handle(): void
    {
        $users = User::whereIn('id', $this->recipients)->get();

        if ($users->isEmpty()) {
            Log::warning('SendNotification: No valid recipients', [
                'type' => $this->type,
                'recipients' => $this->recipients,
            ]);
            return;
        }

        $notification = $this->resolveNotification();

        if ($notification === null) {
            Log::warning("Unknown notification type: {$this->type}");
            return;
        }

        Notification::send($users, $notification);

        Log::info('Notifications sent via Laravel channel', [
            'type'       => $this->type,
            'recipients' => $users->count(),
            'subject'    => $this->subject,
        ]);
    }

    private function resolveNotification(): ?object
    {
        return match ($this->type) {
            'approval' => new ApprovalRequestedNotification(
                approvableType: $this->data['approvable_type'] ?? 'Unknown',
                approvableId: $this->data['approvable_id'] ?? 0,
                approvableName: $this->subject,
                requiredLevel: $this->data['required_level'] ?? 'Manager',
            ),
            'payroll' => new PayrollProcessedNotification(
                payroll: $this->data['payroll'],
            ),
            'payslip' => new PayslipGeneratedNotification(
                payslip: $this->data['payslip'],
            ),
            'attendance' => new AttendanceAlertNotification(
                attendance: $this->data['attendance'],
                alertType: $this->data['alert_type'] ?? 'clock_in',
            ),
            default => null,
        };
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendNotification job failed', [
            'type'       => $this->type,
            'recipients' => $this->recipients,
            'error'      => $e->getMessage(),
            'trace'      => $e->getTraceAsString(),
        ]);
    }
}
