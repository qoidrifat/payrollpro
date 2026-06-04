<?php

namespace App\Events;

use App\Models\PayrollItem;
use App\Models\Payslip;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayslipGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PayrollItem $payrollItem,
        public readonly Payslip $payslip
    ) {}
}