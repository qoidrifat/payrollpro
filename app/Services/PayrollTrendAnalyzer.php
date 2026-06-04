<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\PayrollItem;
use Carbon\Carbon;

class PayrollTrendAnalyzer
{
    /**
     * Analyze payroll trends over the last N months.
     *
     * Returns: total_payroll, avg_salary, employee_count, overtime_total per month.
     */
    public function monthlyTrends(int $months = 6, ?int $companyId = null): array
    {
        $results = [];
        $now = Carbon::now();

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthStr = $month->format('Y-m');

            $payrolls = Payroll::whereYear('period_end', $month->year)
                ->whereMonth('period_end', $month->month)
                ->where('status', '!=', 'draft')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->get();

            $totalGross = $payrolls->sum('total_gross');
            $totalNet = $payrolls->sum('total_net');
            $totalEmployees = $payrolls->sum('total_employees');
            $avgSalary = $totalEmployees > 0
                ? round($totalNet / $totalEmployees, 2)
                : 0;

            $results[] = [
                'month'           => $monthStr,
                'total_gross'     => (float) $totalGross,
                'total_net'       => (float) $totalNet,
                'employee_count'  => (int) $totalEmployees,
                'avg_salary'      => $avgSalary,
                'payroll_runs'    => $payrolls->count(),
            ];
        }

        // Add trend indicators
        return $this->enrichWithTrends($results);
    }

    /**
     * Analyze year-over-year comparison.
     */
    public function yearOverYearComparison(?int $companyId = null): array
    {
        $currentYear = (int) date('Y');
        $previousYear = $currentYear - 1;

        $current = $this->aggregateForYear($currentYear, $companyId);
        $previous = $this->aggregateForYear($previousYear, $companyId);

        return [
            'current_year'  => $currentYear,
            'previous_year' => $previousYear,
            'current'       => $current,
            'previous'      => $previous,
            'changes'       => [
                'total_gross'     => $this->pctChange($previous['total_gross'] ?? 0, $current['total_gross'] ?? 0),
                'total_net'       => $this->pctChange($previous['total_net'] ?? 0, $current['total_net'] ?? 0),
                'employee_count'  => $this->pctChange($previous['employee_count'] ?? 0, $current['employee_count'] ?? 0),
                'avg_salary'      => $this->pctChange($previous['avg_salary'] ?? 0, $current['avg_salary'] ?? 0),
            ],
        ];
    }

    /**
     * Detect payroll spikes (months where total deviates >30% from the moving average).
     */
    public function detectSpikes(int $windowMonths = 12, ?int $companyId = null): array
    {
        $trends = $this->monthlyTrends($windowMonths, $companyId);

        if (count($trends) < 3) {
            return [];
        }

        $spikes = [];
        $totals = array_column($trends, 'total_net');

        foreach ($trends as $i => $trend) {
            if ($i < 1) continue; // skip oldest month

            $prev = $totals[$i - 1];
            $change = $prev > 0 ? abs(($totals[$i] - $prev) / $prev) : 0;

            if ($change > 0.30) {
                $spikes[] = [
                    'month'       => $trend['month'],
                    'total_net'   => $trend['total_net'],
                    'previous'    => $prev,
                    'pct_change'  => round($change * 100, 1),
                    'direction'   => $totals[$i] > $prev ? 'up' : 'down',
                    'description' => "Payroll spike detected: {$trend['month']} deviates " . round($change * 100, 1) . '% from prior month.',
                ];
            }
        }

        return $spikes;
    }

    /**
     * Generate a full payroll health report.
     */
    public function healthReport(?int $companyId = null): array
    {
        $trends = $this->monthlyTrends(6, $companyId);
        $spikes = $this->detectSpikes(12, $companyId);
        $yoy = $this->yearOverYearComparison($companyId);

        return [
            'generated_at'  => now()->toIso8601String(),
            'monthly_trends' => $trends,
            'spikes_detected' => $spikes,
            'year_over_year'  => $yoy,
            'health_status'  => $this->assessHealth($spikes, $yoy),
        ];
    }

    private function aggregateForYear(int $year, ?int $companyId): array
    {
        $payrolls = Payroll::whereYear('period_end', $year)
            ->where('status', '!=', 'draft')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->get();

        $totalGross = $payrolls->sum('total_gross');
        $totalNet = $payrolls->sum('total_net');
        $totalEmployees = $payrolls->sum('total_employees');

        return [
            'total_gross'    => (float) $totalGross,
            'total_net'      => (float) $totalNet,
            'employee_count' => (int) $totalEmployees,
            'avg_salary'     => $totalEmployees > 0
                ? round($totalNet / max($totalEmployees, 1), 2)
                : 0,
            'payroll_runs'   => $payrolls->count(),
        ];
    }

    private function enrichWithTrends(array $results): array
    {
        if (count($results) < 2) {
            return $results;
        }

        $enriched = [];
        foreach ($results as $i => $result) {
            $result['trend'] = 'stable';
            if ($i > 0) {
                $prev = $results[$i - 1]['total_net'];
                $curr = $result['total_net'];
                $change = $prev > 0 ? ($curr - $prev) / $prev : 0;
                $result['pct_change'] = round($change * 100, 1);
                $result['trend'] = $change > 0.1 ? 'up' : ($change < -0.1 ? 'down' : 'stable');
            }
            $enriched[] = $result;
        }

        return $enriched;
    }

    private function pctChange($old, $new): ?float
    {
        if ($old == 0) return null;
        return round((($new - $old) / $old) * 100, 1);
    }

    private function assessHealth(array $spikes, array $yoy): string
    {
        $criticalSpikes = array_filter($spikes, fn($s) => $s['pct_change'] > 50);

        if (count($criticalSpikes) > 1) return 'critical';
        if (count($spikes) > 2) return 'warning';
        if (count($spikes) > 0) return 'attention';

        $yoyChange = abs($yoy['changes']['total_net'] ?? 0);
        if ($yoyChange > 30) return 'warning';

        return 'healthy';
    }
}
