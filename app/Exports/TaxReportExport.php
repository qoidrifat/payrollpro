<?php

namespace App\Exports;

use App\Models\PayrollItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TaxReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private readonly int $year,
    ) {}

    /** Per-export row counter (instance-scoped; avoids static leak across exports) */
    private int $rowNumber = 0;

    public function collection(): Collection
    {
        $start = CarbonImmutable::create($this->year, 1, 1);

        return PayrollItem::with('employee:id,first_name,last_name,nik,position')
            ->whereHas('payroll', fn($q) => $q->whereBetween('period_end', [$start->toDateString(), $start->endOfYear()->toDateString()]))
            ->selectRaw('
                employee_id,
                SUM(gross_salary) as total_gross,
                SUM(pph21) as total_pph21,
                SUM(bpjs_kesehatan_employee + bpjs_tk_jht_employee + bpjs_tk_jp_employee) as total_bpjs_employee
            ')
            ->groupBy('employee_id')
            ->get();
    }

    public function title(): string
    {
        return 'PPh21 ' . $this->year;
    }

    public function headings(): array
    {
        return [
            'No',
            'NIK',
            'Nama Karyawan',
            'Posisi',
            'Total Penghasilan Bruto',
            'Total BPJS (Karyawan)',
            'Total PPh21',
            'Penghasilan Neto (Bruto - BPJS)',
            'Estimasi Tarif Efektif',
        ];
    }

    public function map($item): array
    {
        $no = ++$this->rowNumber;

        $employee = $item->employee;
        $taxableIncome = (float) $item->total_gross - (float) $item->total_bpjs_employee;
        $totalPph21 = (float) $item->total_pph21;
        $effectiveRate = $taxableIncome > 0
            ? round(($totalPph21 / $taxableIncome) * 100, 2)
            : 0;

        return [
            $no,
            $employee?->nik ?? '-',
            $employee?->full_name ?? '-',
            $employee?->position ?? '-',
            (float) $item->total_gross,
            (float) $item->total_bpjs_employee,
            $totalPph21,
            $taxableIncome,
            $effectiveRate . '%',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']],
            ],
        ];
    }
}
