<?php

namespace App\Listeners;

use App\Events\PayrollProcessed;
use App\Models\ActivityLog;
use App\Models\Payroll;
use App\Models\User;
use App\Notifications\PayrollProcessedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogPayrollProcessed implements ShouldQueue
{
    public function handle(PayrollProcessed $event): void
    {
        ActivityLog::create([
            'user_id'      => $event->processedBy->id,
            'action'       => 'payroll_processed',
            'description'  => "Payroll '{$event->payroll->name}' processed for {$event->payroll->total_employees} employees",
            'subject_type' => Payroll::class,
            'subject_id'   => $event->payroll->id,
        ]);

        // Notify Admin and HR users that payroll is ready for approval
        $approvers = User::role(['Admin', 'HR'])->get();
        foreach ($approvers as $approver) {
            $approver->notify(new PayrollProcessedNotification($event->payroll));
        }
    }
}
