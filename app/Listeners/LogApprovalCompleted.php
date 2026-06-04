<?php

namespace App\Listeners;

use App\Events\ApprovalCompleted;
use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogApprovalCompleted implements ShouldQueue
{
    public function handle(ApprovalCompleted $event): void
    {
        ActivityLog::create([
            'user_id'      => $event->approvedBy,
            'action'       => 'approval_completed',
            'description'  => "{$event->approvableType} #{$event->approvableId} was {$event->newStatus}",
            'subject_type' => $event->approvableType,
            'subject_id'   => $event->approvableId,
        ]);
    }
}
