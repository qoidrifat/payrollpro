<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private readonly ?string $status = null,
        private readonly ?string $department = null,
    ) {}

    public function collection()
    {
        return Employee::query()
            ->when($this->status === 'active', fn($q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn($q) => $q->where('is_active', false))
            ->when($this->department, fn($q, $d) => $q->where('department', $d))
            ->orderBy('first_name')
            ->get();
    }

    public function title(): string
    {
        return 'Data Karyawan';
    }

    public function headings(): array
    {
        return [
            'No',
            'NIK',
            'Nama Depan',
            'Nama Belakang',
            'Jenis Kelamin',
            'Posisi',
            'Departemen',
            'Status Kerja',
            'Gaji Pokok',
            'Nomor HP',
            'Email',
            'Bank',
            'No. Rekening',
            'NPWP',
            'BPJS Kesehatan',
            'BPJS Ketenagakerjaan',
            'Tanggal Masuk',
            'Status Aktif',
            'Kota',
            'Provinsi',
        ];
    }

    public function map($employee): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $employee->nik,
            $employee->first_name,
            $employee->last_name ?? '-',
            $employee->gender === 'male' ? 'Laki-laki' : 'Perempuan',
            $employee->position,
            $employee->department ?? '-',
            match ($employee->employment_status?->value) {
                'permanent' => 'Tetap',
                'contract'  => 'Kontrak',
                'probation' => 'Probation',
                'intern'    => 'Magang',
                default     => $employee->employment_status?->value ?? '-',
            },
            $employee->base_salary,
            $employee->phone ?? '-',
            $employee->user?->email ?? '-',
            $employee->bank_name ?? '-',
            "'" . $employee->bank_account_number,
            $employee->npwp ?? '-',
            $employee->bpjs_kesehatan ?? '-',
            $employee->bpjs_ketenagakerjaan ?? '-',
            $employee->join_date?->format('d/m/Y'),
            $employee->is_active ? 'Aktif' : 'Non-Aktif',
            $employee->city ?? '-',
            $employee->province ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '4F46E5'],
                ],
            ],
        ];
    }
}
