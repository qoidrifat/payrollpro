<?php

namespace App\Actions\Payroll;

use App\Events\PayslipGenerated;
use App\Models\Payroll;
use App\Services\PayslipService;
use Illuminate\Support\Facades\Gate;

class GenerateBulkPayslips
{
    public function __construct(
        private readonly PayslipService $payslipService,
    ) {}

    /**
     * Generate payslips for all items in a payroll run.
     */
    public function execute(Payroll $payroll): array
    {
        Gate::authorize('view', $payroll);

        $payroll->load('items');

        if ($payroll->items->isEmpty()) {
            throw new \RuntimeException('Tidak ada item penggajian untuk membuat slip gaji.');
        }

        $generated = [];

        foreach ($payroll->items as $item) {
            $payslip = $this->payslipService->generate($item);
            PayslipGenerated::dispatch($item, $payslip);
            $generated[] = $payslip;
        }

        return $generated;
    }
}
