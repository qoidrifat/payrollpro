<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id', 'employee_id',
        'gross_salary',
        'bpjs_kesehatan_company', 'bpjs_kesehatan_employee',
        'bpjs_tk_jht_company', 'bpjs_tk_jht_employee',
        'bpjs_tk_jp_company', 'bpjs_tk_jp_employee',
        'bpjs_tk_jkk', 'bpjs_tk_jkm',
        'pph21',
        'allowances_total', 'deductions_total', 'bonuses_total',
        'overtime_pay',
        'net_salary',
        'calculation_details', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'gross_salary' => 'decimal:2',
            'bpjs_kesehatan_company' => 'decimal:2',
            'bpjs_kesehatan_employee' => 'decimal:2',
            'bpjs_tk_jht_company' => 'decimal:2',
            'bpjs_tk_jht_employee' => 'decimal:2',
            'bpjs_tk_jp_company' => 'decimal:2',
            'bpjs_tk_jp_employee' => 'decimal:2',
            'bpjs_tk_jkk' => 'decimal:2',
            'bpjs_tk_jkm' => 'decimal:2',
            'pph21' => 'decimal:2',
            'allowances_total' => 'decimal:2',
            'deductions_total' => 'decimal:2',
            'bonuses_total' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'calculation_details' => 'array',
        ];
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payslip()
    {
        return $this->hasOne(Payslip::class);
    }
}
