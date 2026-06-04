<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn() => new EmployeeResource($this->employee)),
            'gross_salary' => (float) $this->gross_salary,
            'bpjs_kesehatan_company' => (float) $this->bpjs_kesehatan_company,
            'bpjs_kesehatan_employee' => (float) $this->bpjs_kesehatan_employee,
            'bpjs_tk_jht_company' => (float) $this->bpjs_tk_jht_company,
            'bpjs_tk_jht_employee' => (float) $this->bpjs_tk_jht_employee,
            'bpjs_tk_jp_company' => (float) $this->bpjs_tk_jp_company,
            'bpjs_tk_jp_employee' => (float) $this->bpjs_tk_jp_employee,
            'bpjs_tk_jkk' => (float) $this->bpjs_tk_jkk,
            'bpjs_tk_jkm' => (float) $this->bpjs_tk_jkm,
            'pph21' => (float) $this->pph21,
            'allowances_total' => (float) $this->allowances_total,
            'deductions_total' => (float) $this->deductions_total,
            'bonuses_total' => (float) $this->bonuses_total,
            'overtime_pay' => (float) $this->overtime_pay,
            'net_salary' => (float) $this->net_salary,
            'calculation_details' => $this->calculation_details,
            'notes' => $this->notes,
            'payroll' => $this->whenLoaded('payroll', fn() => new PayrollResource($this->payroll)),
            'payslip' => $this->whenLoaded('payslip'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
