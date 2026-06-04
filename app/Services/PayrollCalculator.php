<?php

namespace App\Services;

use App\DTOs\PayrollCalculationResult;
use App\Models\Employee;
use App\Models\Payroll;

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
    public function calculateForEmployee(Employee $employee): PayrollCalculationResult
    {
        $baseSalary = (float) $employee->base_salary;

        // Resolve employee tax profile
        // Employee model casts marital_status to MaritalStatus enum, so
        // $employee->marital_status is already an enum instance (or null)
        $maritalStatus = $employee->marital_status;
        $dependents = (int) ($employee->dependents_count ?? 0);

        // Get active salary components
        $components = $employee->salaryComponents()->active()->get();

        $allowances = $components->where('type', 'allowance')->sum('amount');
        $deductions = $components->where('type', 'deduction')->sum('amount');
        $bonuses = $components->where('type', 'bonus')->sum('amount');
        $overtime = $components->where('type', 'overtime')->sum('amount');

        $allowancesTotal = (float) $allowances;
        $deductionsTotal = (float) $deductions;
        $bonusesTotal = (float) $bonuses;
        $componentOvertime = (float) $overtime;

        // Approved overtime requests for the payroll period
        $approvedOvertimePay = $this->overtimeService->getOvertimeForPeriod(
            $employee->id,
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        );

        $overtimePay = $componentOvertime + $approvedOvertimePay;

        // Gross salary = base + allowances + bonuses + overtime
        $grossSalary = $baseSalary + $allowancesTotal + $bonusesTotal + $overtimePay;

        // BPJS calculations
        $bpjsKes = $this->bpjsCalculator->calculateKesehatan($grossSalary);
        $bpjsJht = $this->bpjsCalculator->calculateJht($grossSalary);
        $bpjsJp = $this->bpjsCalculator->calculateJp($grossSalary);
        $bpjsJkk = $this->bpjsCalculator->calculateJkk($grossSalary);
        $bpjsJkm = $this->bpjsCalculator->calculateJkm($grossSalary);

        // Employee BPJS deductions for PPh21 calculation
        $employeeBpjsDeductions = $bpjsKes['employee'] + $bpjsJht['employee'] + $bpjsJp['employee'];

        // PPh21 with dynamic PTKP based on employee tax profile
        $pph21 = $this->taxCalculator->calculateMonthly(
            $grossSalary,
            $employeeBpjsDeductions,
            $maritalStatus,
            $dependents,
        );

        $ptkp = $this->taxCalculator->getPtkp($maritalStatus, $dependents);
        $ptkpCategory = $this->taxCalculator->getPtkpCategory($maritalStatus, $dependents);

        // Total deductions from employee salary
        $totalDeductions = $bpjsKes['employee'] + $bpjsJht['employee'] + $bpjsJp['employee'] + $pph21 + $deductionsTotal;

        // Net salary (take home pay)
        $netSalary = $grossSalary - $totalDeductions;

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
            ],
        );
    }

    /**
     * Calculate payroll for all active employees.
     *
     * @return PayrollCalculationResult[]
     */
    public function calculateForAllActive(): array
    {
        $employees = Employee::active()->get();
        $results = [];

        foreach ($employees as $employee) {
            $results[] = $this->calculateForEmployee($employee);
        }

        return $results;
    }
}
