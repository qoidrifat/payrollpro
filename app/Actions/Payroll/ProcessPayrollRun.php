<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollStatus;
use App\Jobs\ProcessPayroll as ProcessPayrollJob;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;

class ProcessPayrollRun
{
    /**
     * Execute the payroll processing workflow.
     */
    public function execute(Payroll $payroll, int $processedByUserId): Payroll
    {
        Gate::authorize('update', $payroll);

        if ($payroll->status !== PayrollStatus::Draft) {
            throw new \RuntimeException('Hanya penggajian dengan status draft yang dapat diproses.');
        }

        $activeEmployeeCount = Employee::active()->count();

        if ($activeEmployeeCount === 0) {
            throw new \RuntimeException('Tidak ada karyawan aktif untuk diproses.');
        }

        $payroll->update([
            'status'          => PayrollStatus::Processing,
            'processed_by'    => $processedByUserId,
            'total_employees' => $activeEmployeeCount,
        ]);

        ProcessPayrollJob::dispatch($payroll);

        AuditService::payrollChange('processing', $payroll->id, "Payroll '{$payroll->name}' queued for processing.");

        return $payroll->fresh();
    }

    /**
     * Get the number of active employees.
     */
    public function getActiveEmployeeCount(): int
    {
        return Employee::active()->count();
    }
}
