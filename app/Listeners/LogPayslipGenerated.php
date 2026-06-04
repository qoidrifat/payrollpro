<?php

namespace App\Listeners;

use App\Events\PayslipGenerated;
use App\Models\ActivityLog;
use App\Models\Payslip;
use App\Notifications\PayslipGeneratedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogPayslipGenerated implements ShouldQueue
{
    public function handle(PayslipGenerated $event): void
    {
        ActivityLog::create([
            'user_id'      => null,
            'action'       => 'payslip_generated',
            'description'  => "Payslip generated for {$event->payrollItem->employee->full_name}",
            'subject_type' => Payslip::class,
            'subject_id'   => $event->payslip->id,
        ]);

        // Notify the employee their payslip is ready
        if ($user = $event->payrollItem->employee->user) {
            $user->notify(new PayslipGeneratedNotification($event->payslip));
        }
    }
}
