<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollStatus;
use App\Jobs\ProcessPayroll as ProcessPayrollJob;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProcessPayrollRun
{
    /** Statuses that mean a payroll already occupies its period. */
    private const ACTIVE_STATUSES = [
        PayrollStatus::Processing,
        PayrollStatus::Processed,
        PayrollStatus::Approved,
        PayrollStatus::Paid,
    ];

    /**
     * Execute the payroll processing workflow.
     */
    public function execute(Payroll $payroll, int $processedByUserId): Payroll
    {
        Gate::authorize('update', $payroll);

        $activeEmployeeCount = Employee::active()->count();

        if ($activeEmployeeCount === 0) {
            throw new \RuntimeException('Tidak ada karyawan aktif untuk diproses.');
        }

        // Race-safe transition: lock this row inside a transaction so two
        // concurrent requests (e.g. a double-click) cannot both pass the Draft
        // check and dispatch the job twice.
        DB::transaction(function () use ($payroll, $processedByUserId, $activeEmployeeCount) {
            $locked = Payroll::whereKey($payroll->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== PayrollStatus::Draft) {
                throw new \RuntimeException('Hanya penggajian dengan status draft yang dapat diproses.');
            }

            // Guard against a second payroll covering an overlapping period for
            // the same company — otherwise employees could be paid twice.
            $overlapping = Payroll::query()
                ->where('company_id', $locked->company_id)
                ->whereKeyNot($locked->getKey())
                ->whereIn('status', array_map(fn ($s) => $s->value, self::ACTIVE_STATUSES))
                ->whereDate('period_start', '<=', $locked->period_end)
                ->whereDate('period_end', '>=', $locked->period_start)
                ->exists();

            if ($overlapping) {
                throw new \RuntimeException('Sudah ada penggajian aktif untuk periode yang tumpang tindih. Batalkan atau selesaikan penggajian tersebut terlebih dahulu.');
            }

            $locked->update([
                'status'          => PayrollStatus::Processing,
                'processed_by'    => $processedByUserId,
                'total_employees' => $activeEmployeeCount,
            ]);
        });

        $payroll->refresh();

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
