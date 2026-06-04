<?php

namespace App\Providers;

use App\Events\ApprovalCompleted;
use App\Events\EmployeeClockedIn;
use App\Events\EmployeeClockedOut;
use App\Events\PayrollProcessed;
use App\Events\PayslipGenerated;
use App\Listeners\LogApprovalCompleted;
use App\Listeners\LogAttendanceEvent;
use App\Listeners\LogPayrollProcessed;
use App\Listeners\LogPayslipGenerated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        EmployeeClockedIn::class => [
            LogAttendanceEvent::class,
        ],
        EmployeeClockedOut::class => [
            LogAttendanceEvent::class,
        ],
        PayrollProcessed::class => [
            LogPayrollProcessed::class,
        ],
        PayslipGenerated::class => [
            LogPayslipGenerated::class,
        ],
        ApprovalCompleted::class => [
            LogApprovalCompleted::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}