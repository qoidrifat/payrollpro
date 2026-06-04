<?php

namespace App\Services;

use App\Models\PayrollItem;

class PayrollAnomalyDetector
{
    /** Z-score threshold for flagging anomalies (2.5 standard deviations) */
    private const Z_SCORE_THRESHOLD = 2.5;

    /** Minimum number of data points needed for meaningful statistical analysis */
    private const MIN_SAMPLE_SIZE = 5;

    /**
     * Detect salary anomalies across current payroll items.
     *
     * Returns an array of anomaly descriptions keyed by employee_id.
     */
    public function detectSalaryAnomalies(int $payrollId): array
    {
        $items = PayrollItem::where('payroll_id', $payrollId)->get();

        if ($items->count() < self::MIN_SAMPLE_SIZE) {
            return [];
        }

        $salaries = $items->pluck('gross_salary')->filter(fn($v) => $v > 0)->values();

        if ($salaries->count() < self::MIN_SAMPLE_SIZE) {
            return [];
        }

        $mean = $salaries->avg();
        $stdDev = $this->standardDeviation($salaries->toArray(), $mean);
        $median = $this->median($salaries->toArray());
        $mad = $this->medianAbsoluteDeviation($salaries->toArray(), $median);

        $anomalies = [];

        foreach ($items as $item) {
            $salary = (float) $item->gross_salary;
            if ($salary <= 0) continue;

            // Z-score check
            $zScore = $stdDev > 0 ? abs(($salary - $mean) / $stdDev) : 0;

            // Modified Z-score (MAD-based, more robust)
            $modifiedZScore = $mad > 0
                ? 0.6745 * abs($salary - $median) / $mad
                : 0;

            if ($zScore > self::Z_SCORE_THRESHOLD || $modifiedZScore > self::Z_SCORE_THRESHOLD) {
                $anomalies[(int) $item->employee_id] = [
                    'employee_id'     => $item->employee_id,
                    'employee_name'   => $item->employee_name,
                    'gross_salary'    => $salary,
                    'mean'            => round($mean, 2),
                    'median'          => round($median, 2),
                    'z_score'         => round($zScore, 3),
                    'modified_z_score' => round($modifiedZScore, 3),
                    'severity'        => $zScore > 4 ? 'critical' : ($zScore > 3 ? 'high' : 'medium'),
                    'description'     => match (true) {
                        $salary > $mean + 3 * $stdDev => "Salary significantly above company average (z={$zScore})",
                        $salary < $mean - 3 * $stdDev => "Salary significantly below company average (z={$zScore})",
                        default => "Salary deviates from company average (z={$zScore})",
                    },
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Detect duplicate payroll processing for the same employee in overlapping periods.
     */
    public function detectDuplicates(int $payrollId): array
    {
        $items = PayrollItem::where('payroll_id', $payrollId)
            ->with('payroll')
            ->get();

        $duplicates = [];

        foreach ($items as $item) {
            $conflicting = PayrollItem::where('employee_id', $item->employee_id)
                ->where('payroll_id', '!=', $payrollId)
                ->whereHas('payroll', function ($q) use ($item) {
                    $q->where(function ($q) use ($item) {
                        $q->whereBetween('period_start', [
                            $item->payroll->period_start,
                            $item->payroll->period_end,
                        ])->orWhereBetween('period_end', [
                            $item->payroll->period_start,
                            $item->payroll->period_end,
                        ]);
                    });
                })
                ->count();

            if ($conflicting > 0) {
                $duplicates[(int) $item->employee_id] = [
                    'employee_id'    => $item->employee_id,
                    'employee_name'  => $item->employee_name,
                    'conflicting_runs' => $conflicting,
                    'description'    => "Employee appears in {$conflicting} other payroll run(s) with overlapping periods.",
                ];
            }
        }

        return $duplicates;
    }

    /**
     * Detect potential overtime abuse by comparing individual hours to company average.
     */
    public function detectOvertimeAbuse(int $payrollId, ?int $companyId = null): array
    {
        $items = PayrollItem::where('payroll_id', $payrollId)->get();

        $overtimeAmounts = $items->pluck('overtime_pay')
            ->filter(fn($v) => $v > 0)
            ->values();

        if ($overtimeAmounts->count() < self::MIN_SAMPLE_SIZE) {
            return [];
        }

        $mean = $overtimeAmounts->avg();
        $stdDev = $this->standardDeviation($overtimeAmounts->toArray(), $mean);

        $abuses = [];

        foreach ($items as $item) {
            $overtime = (float) $item->overtime_pay;
            if ($overtime <= 0) continue;

            $zScore = $stdDev > 0 ? ($overtime - $mean) / $stdDev : 0;

            if ($zScore > self::Z_SCORE_THRESHOLD) {
                $abuses[(int) $item->employee_id] = [
                    'employee_id'    => $item->employee_id,
                    'employee_name'  => $item->employee_name,
                    'overtime_pay'   => $overtime,
                    'company_avg'    => round($mean, 2),
                    'z_score'        => round($zScore, 2),
                    'severity'       => $zScore > 4 ? 'critical' : 'high',
                    'description'    => "Overtime pay significantly above company average. Possible abuse or misconfiguration.",
                ];
            }
        }

        return $abuses;
    }

    /**
     * Run all anomaly checks and return a consolidated report.
     */
    public function runFullAnalysis(int $payrollId): array
    {
        $salaryAnomalies = $this->detectSalaryAnomalies($payrollId);
        $duplicates = $this->detectDuplicates($payrollId);
        $overtimeAbuse = $this->detectOvertimeAbuse($payrollId);

        $totalIssues = count($salaryAnomalies) + count($duplicates) + count($overtimeAbuse);

        // Log all findings
        foreach ($salaryAnomalies as $anomaly) {
            SecurityLogger::securityViolation('payroll_salary_anomaly', $anomaly);
        }
        foreach ($duplicates as $dup) {
            SecurityLogger::securityViolation('payroll_duplicate_detected', $dup);
        }
        foreach ($overtimeAbuse as $abuse) {
            SecurityLogger::securityViolation('overtime_abuse_detected', $abuse);
        }

        return [
            'payroll_id'        => $payrollId,
            'total_issues'      => $totalIssues,
            'severity'          => $totalIssues > 0
                ? collect(array_merge($salaryAnomalies, $overtimeAbuse))
                    ->pluck('severity')
                    ->contains('critical') ? 'critical' : 'warning'
                : 'clean',
            'salary_anomalies'  => $salaryAnomalies,
            'duplicates'        => $duplicates,
            'overtime_abuse'    => $overtimeAbuse,
        ];
    }

    private function standardDeviation(array $values, ?float $mean = null): float
    {
        $mean ??= array_sum($values) / count($values);
        $squaredDiffs = array_map(fn($v) => pow($v - $mean, 2), $values);
        return sqrt(array_sum($squaredDiffs) / count($values));
    }

    private function median(array $values): float
    {
        sort($values);
        $count = count($values);
        $mid = (int) floor($count / 2);

        if ($count % 2 === 0) {
            return ($values[$mid - 1] + $values[$mid]) / 2;
        }

        return $values[$mid];
    }

    private function medianAbsoluteDeviation(array $values, float $median): float
    {
        $deviations = array_map(fn($v) => abs($v - $median), $values);
        return $this->median($deviations);
    }
}
