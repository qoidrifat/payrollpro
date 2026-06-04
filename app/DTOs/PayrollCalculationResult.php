<?php

namespace App\DTOs;

class PayrollCalculationResult
{
    public function __construct(
        public readonly int $employeeId,
        public readonly string $employeeName,
        public readonly float $grossSalary,
        public readonly float $bpjsKesehatanCompany,
        public readonly float $bpjsKesehatanEmployee,
        public readonly float $bpjsTkJhtCompany,
        public readonly float $bpjsTkJhtEmployee,
        public readonly float $bpjsTkJpCompany,
        public readonly float $bpjsTkJpEmployee,
        public readonly float $bpjsTkJkk,
        public readonly float $bpjsTkJkm,
        public readonly float $pph21,
        public readonly float $allowancesTotal,
        public readonly float $deductionsTotal,
        public readonly float $bonusesTotal,
        public readonly float $overtimePay,
        public readonly float $netSalary,
        public readonly array $details = [],
    ) {}

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'employee_name' => $this->employeeName,
            'gross_salary' => $this->grossSalary,
            'bpjs_kesehatan_company' => $this->bpjsKesehatanCompany,
            'bpjs_kesehatan_employee' => $this->bpjsKesehatanEmployee,
            'bpjs_tk_jht_company' => $this->bpjsTkJhtCompany,
            'bpjs_tk_jht_employee' => $this->bpjsTkJhtEmployee,
            'bpjs_tk_jp_company' => $this->bpjsTkJpCompany,
            'bpjs_tk_jp_employee' => $this->bpjsTkJpEmployee,
            'bpjs_tk_jkk' => $this->bpjsTkJkk,
            'bpjs_tk_jkm' => $this->bpjsTkJkm,
            'pph21' => $this->pph21,
            'allowances_total' => $this->allowancesTotal,
            'deductions_total' => $this->deductionsTotal,
            'bonuses_total' => $this->bonusesTotal,
            'overtime_pay' => $this->overtimePay,
            'net_salary' => $this->netSalary,
            'calculation_details' => $this->details,
        ];
    }
}
