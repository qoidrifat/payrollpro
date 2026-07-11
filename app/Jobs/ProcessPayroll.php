<?php

namespace App\Jobs;

use App\Events\PayrollProcessed;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\PayrollAnomalyDetector;
use App\Services\PayrollCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Process payroll for all active employees in configurable chunks.
 *
 * Supports resumable processing: if the job fails mid-way, re-dispatching
 * continues from the last completed batch (current_batch on Payroll).
 */
class ProcessPayroll implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Number of employees per chunk */
    private const CHUNK_SIZE = 50;

    /** Retry up to 3 times with exponential backoff */
    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 600; // 10 minutes

    public function __construct(
        public readonly Payroll $payroll,
    ) {}

    public function handle(PayrollCalculator $calculator, PayrollAnomalyDetector $detector): void
    {
        $this->payroll->refresh();

        if (! in_array($this->payroll->status->value, ['draft', 'processing'])) {
            Log::warning('ProcessPayroll job skipped — invalid status', [
                'payroll_id' => $this->payroll->id,
                'status' => $this->payroll->status->value,
            ]);

            return;
        }

        // Scope to the payroll's own company. This job runs on the queue with
        // no tenant context bound, so Employee::active() would otherwise be
        // unscoped and pull employees from every company into one payroll.
        $companyId = $this->payroll->company_id;

        $employeeCount = Employee::forCompany($companyId)->active()->count();

        if ($employeeCount === 0) {
            Log::warning('ProcessPayroll job skipped — no active employees', [
                'payroll_id' => $this->payroll->id,
            ]);

            return;
        }

        $totalBatches = (int) ceil($employeeCount / self::CHUNK_SIZE);
        $currentBatch = $this->payroll->current_batch ?? 0;

        // Mark as processing on first run
        if ($currentBatch === 0) {
            $this->payroll->update([
                'status' => 'processing',
                'total_batches' => $totalBatches,
                'current_batch' => 0,
                'progress_percentage' => 0,
            ]);
        }

        $totalGross = $this->payroll->total_gross ?? 0;
        $totalDeductions = 0;
        $totalNet = 0;

        // Resume from the last completed batch
        for ($batch = $currentBatch + 1; $batch <= $totalBatches; $batch++) {
            $offset = ($batch - 1) * self::CHUNK_SIZE;

            $employees = Employee::forCompany($companyId)->active()
                ->orderBy('id')
                ->skip($offset)
                ->take(self::CHUNK_SIZE)
                ->get();

            if ($employees->isEmpty()) {
                break;
            }

            DB::transaction(function () use ($employees, $calculator) {
                foreach ($employees as $employee) {
                    $result = $calculator->calculateForEmployee(
                        $employee,
                        $this->payroll->period_start->toDateString(),
                        $this->payroll->period_end->toDateString(),
                    );

                    // upsert to handle resumable processing
                    PayrollItem::updateOrCreate(
                        [
                            'payroll_id' => $this->payroll->id,
                            'employee_id' => $employee->id,
                        ],
                        $result->toArray()
                    );
                }
            });

            // Recalculate totals from current items for accuracy
            $items = PayrollItem::where('payroll_id', $this->payroll->id)->get();

            $totalGross = $items->sum('gross_salary');
            $totalDeductions = $items->sum(fn ($i) => (float) $i->bpjs_kesehatan_employee
                + (float) $i->bpjs_tk_jht_employee
                + (float) $i->bpjs_tk_jp_employee
                + (float) $i->pph21
                + (float) $i->deductions_total
            );
            $totalNet = $items->sum('net_salary');

            $progress = (int) round(($batch / $totalBatches) * 100);

            $this->payroll->update([
                'current_batch' => $batch,
                'progress_percentage' => $progress,
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
                'total_employees' => $items->count(),
            ]);

            Log::info('Payroll batch completed', [
                'payroll_id' => $this->payroll->id,
                'batch' => "{$batch}/{$totalBatches}",
                'progress' => $progress.'%',
            ]);
        }

        // Finalize
        $this->payroll->update([
            'status' => 'processed',
            'processed_at' => now(),
            'progress_percentage' => 100,
        ]);

        PayrollProcessed::dispatch($this->payroll);

        $analysis = $detector->runFullAnalysis($this->payroll->id);

        Log::info('Payroll processing completed', [
            'payroll_id' => $this->payroll->id,
            'employees' => $this->payroll->total_employees,
            'anomaly_issues' => $analysis['total_issues'],
            'anomaly_severity' => $analysis['severity'],
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessPayroll job failed', [
            'payroll_id' => $this->payroll->id,
            'current_batch' => $this->payroll->current_batch,
            'progress' => $this->payroll->progress_percentage.'%',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Keep processing status so it can be resumed
        $this->payroll->update(['status' => 'processing']);
    }
}
