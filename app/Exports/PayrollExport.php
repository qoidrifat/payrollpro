<?php

namespace App\Exports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(private readonly Payroll $payroll) {}

    public function collection()
    {
        return $this->payroll->items()->with('employee')->get();
    }

    public function title(): string
    {
        return 'Slip Gaji ' . $this->payroll->period_end->format('F Y');
    }

    public function headings(): array
    {
        return [
            'No',
            'NIK',
            'Nama Karyawan',
            'Posisi',
            'Gaji Pokok',
            'Tunjangan',
            'Bonus',
            'Lembur',
            'Gaji Bruto',
            'BPJS Kes (Karyawan)',
            'BPJS TK JHT (Karyawan)',
            'BPJS TK JP (Karyawan)',
            'PPh 21',
            'Potongan Lain',
            'Total Potongan',
            'Gaji Bersih (Take Home Pay)',
            'No. Rekening',
            'Bank',
        ];
    }

    public function map($item): array
    {
        static $no = 0;
        $no++;

        $totalEmployeeDeductions = $item->bpjs_kesehatan_employee
            + $item->bpjs_tk_jht_employee
            + $item->bpjs_tk_jp_employee
            + $item->pph21
            + $item->deductions_total;

        $baseSalary = $item->gross_salary - $item->allowances_total - $item->bonuses_total - $item->overtime_pay;

        return [
            $no,
            $item->employee->nik,
            $item->employee->full_name,
            $item->employee->position,
            $baseSalary,
            $item->allowances_total,
            $item->bonuses_total,
            $item->overtime_pay,
            $item->gross_salary,
            $item->bpjs_kesehatan_employee,
            $item->bpjs_tk_jht_employee,
            $item->bpjs_tk_jp_employee,
            $item->pph21,
            $item->deductions_total,
            $totalEmployeeDeductions,
            $item->net_salary,
            "'" . $item->employee->bank_account_number,
            $item->employee->bank_name,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']], 'fill' => [
                'fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5'],
            ]],
        ];
    }
}
