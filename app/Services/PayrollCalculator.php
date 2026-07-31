<?php

namespace App\Services;

use App\DTOs\PayrollCalculationResult;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Support\Facades\DB;

class PayrollCalculator
{
    public function __construct(
        private readonly BpjsCalculator $bpjsCalculator,
        private readonly TaxCalculator $taxCalculator,
        private readonly OvertimeService $overtimeService,
    ) {}

    /**
     * Calculate payroll for a single employee.
     */
    public function calculateForEmployee(
        Employee $employee,
        ?string $periodStart = null,
        ?string $periodEnd = null,
    ): PayrollCalculationResult {
        $periodStart ??= now()->startOfMonth()->toDateString();
        $periodEnd ??= now()->endOfMonth()->toDateString();

        // Use BPJS/tax config for the payroll period's year, not the current
        // calendar year — matters for backfilled or prior-year runs.
        $periodYear = (int) \Carbon\Carbon::parse($periodEnd)->year;
        $this->bpjsCalculator->useYear($periodYear);
        $this->taxCalculator->useYear($periodYear);

        $baseSalary = (float) $employee->base_salary;

        // Resolve employee tax profile
        // Employee model casts marital_status to MaritalStatus enum, so
        // $employee->marital_status is already an enum instance (or null)
        $maritalStatus = $employee->marital_status;
        $dependents = (int) ($employee->dependents_count ?? 0);

        // Get active salary components effective during the payroll period
        $components = $employee->salaryComponents()
            ->active()
            ->effectiveForPeriod($periodStart, $periodEnd)
            ->get();

        $allowances = $components->where('type', 'allowance')->sum('amount');
        $deductions = $components->where('type', 'deduction')->sum('amount');
        $bonuses = $components->where('type', 'bonus')->sum('amount');
        $overtime = $components->where('type', 'overtime')->sum('amount');

        $allowancesTotal = (float) $allowances;
        $componentDeductions = (float) $deductions;
        $bonusesTotal = (float) $bonuses;
        $componentOvertime = (float) $overtime;

        // Approved overtime requests for the payroll period
        $approvedOvertimePay = $this->overtimeService->getOvertimeForPeriod(
            $employee->id,
            $periodStart,
            $periodEnd,
        );

        $overtimePay = $componentOvertime + $approvedOvertimePay;

        // Prorate regular wage (base + fixed allowances) for employees who
        // joined or resigned mid-period. Bonus/overtime are actual amounts and
        // are not prorated.
        $prorataFactor = $this->computeProrataFactor($employee, $periodStart, $periodEnd);
        if ($prorataFactor < 1.0) {
            $baseSalary = round($baseSalary * $prorataFactor, 2);
            $allowancesTotal = round($allowancesTotal * $prorataFactor, 2);
        }

        // Gross salary = base + allowances + bonuses + overtime
        // Round monetary aggregates to whole rupiah at each boundary to bound
        // float accumulation drift (IDR has no sub-rupiah denomination).
        $grossSalary = round($baseSalary + $allowancesTotal + $bonusesTotal + $overtimePay, 2);

        // BPJS wage base ("upah") = base salary + fixed allowances only.
        // BPJS regulations compute contributions on regular wage, NOT on
        // irregular pay (bonus/THR/overtime), so those are excluded here.
        $bpjsBase = $baseSalary + $allowancesTotal;

        // BPJS calculations (on wage base, not full gross)
        $bpjsKes = $this->bpjsCalculator->calculateKesehatan($bpjsBase);
        $bpjsJht = $this->bpjsCalculator->calculateJht($bpjsBase);
        $bpjsJp = $this->bpjsCalculator->calculateJp($bpjsBase);
        $bpjsJkk = $this->bpjsCalculator->calculateJkk($bpjsBase);
        $bpjsJkm = $this->bpjsCalculator->calculateJkm($bpjsBase);

        // Employee BPJS deductions for PPh21 calculation
        $employeeBpjsDeductions = $bpjsKes['employee'] + $bpjsJht['employee'] + $bpjsJp['employee'];

        // PPh21 with dynamic PTKP based on employee tax profile.
        // Regular income (base + fixed allowances) is annualized ×12; irregular
        // income (bonus/THR + overtime) is added once and charged only its
        // incremental tax — annualizing it would over-tax the employee ~12×.
        $regularGross = $baseSalary + $allowancesTotal;
        $irregularIncome = $bonusesTotal + $overtimePay;

        $pph21 = $this->taxCalculator->calculateMonthly(
            $regularGross,
            $employeeBpjsDeductions,
            $maritalStatus,
            $dependents,
            $irregularIncome,
        );

        $ptkp = $this->taxCalculator->getPtkp($maritalStatus, $dependents);
        $ptkpCategory = $this->taxCalculator->getPtkpCategory($maritalStatus, $dependents);

        // Attendance-based deductions for the period (absent / late / half_day).
        // Official leave (sick/leave) and present days are exempt.
        // See config/attendance.php -> payroll_deduction.
        $attendanceDeduction = $this->computeAttendanceDeduction($employee, $periodStart, $periodEnd);

        // "Other" deductions shown on the payslip = manual salary components
        // (loans, etc.) + attendance-based deductions.
        $deductionsTotal = round($componentDeductions + $attendanceDeduction['total'], 2);

        // Total deductions from employee salary
        $totalDeductions = round($bpjsKes['employee'] + $bpjsJht['employee'] + $bpjsJp['employee'] + $pph21 + $deductionsTotal, 2);

        // Net salary (take home pay). Floored at 0 — deductions can never
        // produce a negative take-home pay.
        $netSalary = max(0.0, round($grossSalary - $totalDeductions, 2));

        return new PayrollCalculationResult(
            employeeId: $employee->id,
            employeeName: $employee->full_name,
            grossSalary: $grossSalary,
            bpjsKesehatanCompany: $bpjsKes['company'],
            bpjsKesehatanEmployee: $bpjsKes['employee'],
            bpjsTkJhtCompany: $bpjsJht['company'],
            bpjsTkJhtEmployee: $bpjsJht['employee'],
            bpjsTkJpCompany: $bpjsJp['company'],
            bpjsTkJpEmployee: $bpjsJp['employee'],
            bpjsTkJkk: $bpjsJkk,
            bpjsTkJkm: $bpjsJkm,
            pph21: $pph21,
            allowancesTotal: $allowancesTotal,
            deductionsTotal: $deductionsTotal,
            bonusesTotal: $bonusesTotal,
            overtimePay: $overtimePay,
            netSalary: $netSalary,
            details: [
                'base_salary' => $baseSalary,
                'bpjs_kesehatan' => $bpjsKes,
                'bpjs_jht' => $bpjsJht,
                'bpjs_jp' => $bpjsJp,
                'bpjs_jkk' => $bpjsJkk,
                'bpjs_jkm' => $bpjsJkm,
                'employee_bpjs_deductions' => $employeeBpjsDeductions,
                'pph21' => $pph21,
                'ptkp' => $ptkp,
                'ptkp_category' => $ptkpCategory,
                'marital_status' => $maritalStatus?->code(),
                'dependents' => $dependents,
                'tax_year' => $this->taxCalculator->getTaxYear(),
                'gross_annualized' => $grossSalary * 12,
                'prorata_factor' => $prorataFactor,
                'component_deductions' => $componentDeductions,
                'attendance_deduction' => $attendanceDeduction['details'],
            ],
        );
    }

    /**
     * Compute attendance-based deductions for an employee over the payroll
     * period. Unexcused statuses are charged a configurable rate; official
     * leave (sick/leave) and present days are exempt.
     *
     * @return array{total: float, details: array}
     */
    private function computeAttendanceDeduction(Employee $employee, string $periodStart, string $periodEnd): array
    {
        $config = config('attendance.payroll_deduction', []);

        if (empty($config['enabled'])) {
            return ['total' => 0.0, 'details' => ['enabled' => false]];
        }

        $rates = $config['rates'] ?? [];

        // Raw count per status for the period (bypasses the enum cast so keys
        // stay plain strings). Attendances are already scoped by employee_id.
        $counts = DB::table('attendances')
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();

        $absentCount  = (int) ($counts['absent'] ?? 0);
        $lateCount    = (int) ($counts['late'] ?? 0);
        $halfDayCount = (int) ($counts['half_day'] ?? 0);

        $absentRate  = (float) ($rates['absent'] ?? 0);
        $lateRate    = (float) ($rates['late'] ?? 0);
        $halfDayRate = (float) ($rates['half_day'] ?? 0);

        $absentAmount  = $absentCount * $absentRate;
        $lateAmount    = $lateCount * $lateRate;
        $halfDayAmount = $halfDayCount * $halfDayRate;

        $total = round($absentAmount + $lateAmount + $halfDayAmount, 2);

        return [
            'total' => $total,
            'details' => [
                'enabled'  => true,
                'absent'   => ['count' => $absentCount,  'rate' => $absentRate,  'amount' => $absentAmount],
                'late'     => ['count' => $lateCount,     'rate' => $lateRate,    'amount' => $lateAmount],
                'half_day' => ['count' => $halfDayCount,  'rate' => $halfDayRate, 'amount' => $halfDayAmount],
                'exempt'   => $config['exempt'] ?? ['present', 'sick', 'leave'],
                'total'    => $total,
            ],
        ];
    }

    /**
     * Compute the prorate factor (0..1) for an employee within a period based
     * on join and resign dates. Returns 1.0 when the employee was employed for
     * the entire period.
     */
    private function computeProrataFactor(Employee $employee, string $periodStart, string $periodEnd): float
    {
        $start = \Carbon\Carbon::parse($periodStart)->startOfDay();
        $end = \Carbon\Carbon::parse($periodEnd)->startOfDay();

        $join = $employee->join_date ? $employee->join_date->copy()->startOfDay() : null;
        $resign = $employee->resign_date ? $employee->resign_date->copy()->startOfDay() : null;

        // Fast path: employed for the whole period.
        if ((! $join || $join->lte($start)) && (! $resign || $resign->gte($end))) {
            return 1.0;
        }

        $effectiveStart = ($join && $join->gt($start)) ? $join : $start;
        $effectiveEnd = ($resign && $resign->lt($end)) ? $resign : $end;

        // Not employed at all during the period.
        if ($effectiveEnd->lt($effectiveStart)) {
            return 0.0;
        }

        $totalDays = $start->diffInDays($end) + 1;
        $workedDays = $effectiveStart->diffInDays($effectiveEnd) + 1;

        return min(1.0, max(0.0, $workedDays / $totalDays));
    }

    /**
     * Calculate payroll for all active employees.
     *
     * @return PayrollCalculationResult[]
     */
    public function calculateForAllActive(?string $periodStart = null, ?string $periodEnd = null): array
    {
        $employees = Employee::active()->get();
        $results = [];

        foreach ($employees as $employee) {
            $results[] = $this->calculateForEmployee($employee, $periodStart, $periodEnd);
        }

        return $results;
    }
}
