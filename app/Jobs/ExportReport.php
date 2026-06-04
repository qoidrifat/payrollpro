<?php

namespace App\Jobs;

use App\Exports\AttendanceReportExport;
use App\Exports\PayrollReportExport;
use App\Exports\TaxReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ExportReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 30;
    public int $timeout = 300; // 5 minutes for large exports

    public function __construct(
        public readonly string $reportType,
        public readonly ?int $userId = null,
        public readonly array $filters = [],
    ) {}

    public function handle(): void
    {
        Log::info('Export report job started', [
            'type'    => $this->reportType,
            'user_id' => $this->userId,
        ]);

        match ($this->reportType) {
            'payroll'    => $this->exportPayroll(),
            'tax'        => $this->exportTax(),
            'attendance' => $this->exportAttendance(),
            default      => Log::warning("Unknown report type: {$this->reportType}"),
        };
    }

    private function exportPayroll(): void
    {
        $export = new PayrollReportExport(
            dateFrom: $this->filters['date_from'] ?? now()->startOfYear()->toDateString(),
            dateTo: $this->filters['date_to'] ?? now()->toDateString(),
            status: $this->filters['status'] ?? null,
        );
        $filename = 'Laporan_Payroll_' . now()->format('Ymd_His') . '.xlsx';
        Excel::store($export, $filename, 'public');
    }

    private function exportTax(): void
    {
        $export = new TaxReportExport(
            year: (int) ($this->filters['year'] ?? date('Y')),
        );
        $filename = 'Laporan_PPh21_Tahunan_' . now()->format('Ymd_His') . '.xlsx';
        Excel::store($export, $filename, 'public');
    }

    private function exportAttendance(): void
    {
        $export = new AttendanceReportExport(
            year: (int) ($this->filters['year'] ?? date('Y')),
            month: (int) ($this->filters['month'] ?? date('m')),
        );
        $filename = 'Laporan_Absensi_' . now()->format('Ymd_His') . '.xlsx';
        Excel::store($export, $filename, 'public');
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ExportReport job failed', [
            'type'  => $this->reportType,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
