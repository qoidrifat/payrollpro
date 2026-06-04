<?php

namespace App\Jobs;

use App\Events\PayslipGenerated;
use App\Models\PayrollItem;
use App\Services\PayslipService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GeneratePayslip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        public readonly PayrollItem $payrollItem,
    ) {}

    public function handle(PayslipService $payslipService): void
    {
        $payslip = $payslipService->generate($this->payrollItem);

        PayslipGenerated::dispatch($this->payrollItem, $payslip);

        Log::info('Payslip generated via queue', [
            'payroll_item_id' => $this->payrollItem->id,
            'payslip_id'      => $payslip->id,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GeneratePayslip job failed', [
            'payroll_item_id' => $this->payrollItem->id,
            'error'           => $e->getMessage(),
            'trace'           => $e->getTraceAsString(),
        ]);
    }
}
