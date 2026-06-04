<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private readonly int $year,
        private readonly int $month,
    ) {}

    public function collection(): Collection
    {
        return Employee::active()
            ->with(['attendances' => fn($q) => $q
                ->whereYear('date', $this->year)
                ->whereMonth('date', $this->month)
            ])
            ->orderBy('first_name')
            ->get();
    }

    public function title(): string
    {
        $monthName = \Carbon\Carbon::create($this->year, $this->month, 1)->locale('id')->monthName;
        return "Absensi {$monthName} {$this->year}";
    }

    public function headings(): array
    {
        return [
            'No',
            'NIK',
            'Nama Karyawan',
            'Departemen',
            'Posisi',
            'Total Hari Kerja',
            'Hadir',
            'Terlambat',
            'Absen',
            'Izin/Sakit',
            'Persentase Kehadiran',
        ];
    }

    public function map($employee): array
    {
        static $no = 0;
        $no++;

        $attendances = $employee->attendances;
        $workDays = $attendances->count();
        $presentDays = $attendances->where('status', 'present')->count();
        $lateDays = $attendances->where('status', 'late')->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $leaveDays = $attendances->whereIn('status', ['sick', 'leave', 'half_day'])->count();

        return [
            $no,
            $employee->nik,
            $employee->full_name,
            $employee->department ?? '-',
            $employee->position,
            $workDays,
            $presentDays,
            $lateDays,
            $absentDays,
            $leaveDays,
            $workDays > 0 ? round(($presentDays / $workDays) * 100, 2) . '%' : '0%',
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
