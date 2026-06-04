<?php

namespace App\Exports;

use App\Models\Payroll;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private readonly string $dateFrom,
        private readonly string $dateTo,
        private readonly ?string $status = null,
    ) {}

    public function collection(): Collection
    {
        return Payroll::withCount('items')
            ->whereBetween('period_end', [$this->dateFrom, $this->dateTo])
            ->when($this->status, fn($q, $s) => $q->where('status', $s))
            ->latest('period_end')
            ->get();
    }

    public function title(): string
    {
        return 'Laporan Payroll';
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Payroll',
            'Periode Mulai',
            'Periode Selesai',
            'Status',
            'Jumlah Karyawan',
            'Total Gross',
            'Total Potongan',
            'Total Net',
            'Diproses Pada',
        ];
    }

    public function map($payroll): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $payroll->name,
            $payroll->period_start->format('d/m/Y'),
            $payroll->period_end->format('d/m/Y'),
            match ($payroll->status?->value) {
                'draft'     => 'Draft',
                'processing'=> 'Diproses',
                'processed' => 'Selesai',
                'approved'  => 'Disetujui',
                'paid'      => 'Dibayar',
                default     => $payroll->status?->value ?? '-',
            },
            $payroll->items_count,
            $payroll->total_gross,
            $payroll->total_deductions,
            $payroll->total_net,
            $payroll->processed_at?->format('d/m/Y H:i'),
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
