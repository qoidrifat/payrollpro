<?php

namespace App\Http\Controllers;

use App\Exports\PayrollExport;
use App\Models\Payslip;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\PayslipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class PayslipController extends Controller
{
    public function __construct(private readonly PayslipService $payslipService) {}

    /**
     * Authorize that the current user can access the given payroll item.
     * Allows employees to access their own payslips.
     */
    private function authorizePayrollItem(PayrollItem $item): void
    {
        $user = request()->user();

        // Employee can access their own payslip
        if ($user->employee && $user->employee->id === $item->employee_id) {
            return;
        }

        // Otherwise require payroll permission
        Gate::authorize('view', $item->payroll);
    }

    /**
     * Preview a payslip as a modern HTML page in the browser.
     */
    public function preview(PayrollItem $item)
    {
        $this->authorizePayrollItem($item);

        $data = $this->payslipService->getViewData($item);

        return Inertia::render('Payslip/Preview', [
            'payslip' => [
                'item_id'                 => $item->id,
                'number'                  => $data['payslipNumber'],
                'employee_name'           => $item->employee->full_name,
                'nik'                     => mask_sensitive($item->employee->nik),
                'npwp'                    => mask_sensitive($item->employee->npwp),
                'position'                => $item->employee->position,
                'department'              => $item->employee->department,
                'bank_name'               => $item->employee->bank_name,
                'bank_account'            => mask_sensitive($item->employee->bank_account_number),
                'period'                  => \Carbon\Carbon::parse($item->payroll->period_start)->format('d M Y') . ' — ' . \Carbon\Carbon::parse($item->payroll->period_end)->format('d M Y'),
                'status'                  => $item->payroll->status->value,
                'print_date'              => now()->format('d F Y'),
                'print_time'              => now()->format('H:i'),
                'company_name'            => $data['company']['company_name'],
                'company_address'         => $data['company']['company_address'],
                'company_phone'           => $data['company']['company_phone'],
                'company_email'           => $data['company']['company_email'],
                'company_director'        => $data['company']['company_director'],
                'company_logo'             => $data['company']['company_logo'],
                'gross_salary'            => (float) $item->gross_salary,
                'net_salary'              => (float) $item->net_salary,
                'base_salary'             => (float) $data['baseSalary'],
                'allowances_total'        => (float) $item->allowances_total,
                'deductions_total'        => (float) $item->deductions_total,
                'bonuses_total'           => (float) $item->bonuses_total,
                'overtime_pay'            => (float) $item->overtime_pay,
                'total_deductions'        => (float) $data['totalDeductions'],
                'pph21'                   => (float) $item->pph21,
                'bpjs_kesehatan_employee' => (float) $item->bpjs_kesehatan_employee,
                'bpjs_kesehatan_company'  => (float) $item->bpjs_kesehatan_company,
                'bpjs_tk_jht_employee'    => (float) $item->bpjs_tk_jht_employee,
                'bpjs_tk_jht_company'     => (float) $item->bpjs_tk_jht_company,
                'bpjs_tk_jp_employee'     => (float) $item->bpjs_tk_jp_employee,
                'bpjs_tk_jp_company'      => (float) $item->bpjs_tk_jp_company,
                'bpjs_tk_jkk'             => (float) $item->bpjs_tk_jkk,
                'bpjs_tk_jkm'             => (float) $item->bpjs_tk_jkm,
            ],
        ]);
    }

    /**
     * Generate and download a single payslip PDF.
     */
    public function generate(PayrollItem $item)
    {
        $this->authorizePayrollItem($item);

        $payslip = $this->payslipService->generate($item);

        return response()->download(
            storage_path('app/public/' . $payslip->pdf_path),
            'Slip_Gaji_' . str_replace(' ', '_', $item->employee->full_name) . '_' . $item->payroll->period_end->format('F_Y') . '.pdf'
        );
    }

    /**
     * Bulk generate and download all payslips for a payroll as ZIP.
     */
    public function bulkDownload(Payroll $payroll)
    {
        Gate::authorize('view', $payroll);

        $employeeId = $this->getEmployeeIdIfScoped();
        $items = $employeeId
            ? $payroll->items()->where('employee_id', $employeeId)->get()
            : $payroll->items()->with('employee')->get();

        if ($items->count() === 0) {
            return back()->with('error', 'Tidak ada data untuk dicetak.');
        }

        if ($items->count() === 1) {
            return $this->generate($items->first());
        }

        $zipPath = storage_path('app/temp/payroll_' . $payroll->id . '_payslips.zip');
        $dir = dirname($zipPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Gagal membuat file ZIP.');
        }

        foreach ($items as $item) {
            $payslip = $this->payslipService->generate($item);
            $pdfPath = storage_path('app/public/' . $payslip->pdf_path);
            if (file_exists($pdfPath)) {
                $zip->addFile(
                    $pdfPath,
                    'Slip_Gaji_' . str_replace(' ', '_', $item->employee->full_name) . '.pdf'
                );
            }
        }

        $zip->close();

        return response()->download(
            $zipPath,
            'Slip_Gaji_' . $payroll->name . '.zip'
        )->deleteFileAfterSend();
    }

    /**
     * Export payroll data to Excel.
     */
    public function exportExcel(Payroll $payroll)
    {
        Gate::authorize('view', $payroll);

        return Excel::download(
            new PayrollExport($payroll),
            'Payroll_' . $payroll->period_end->format('F_Y') . '.xlsx'
        );
    }
}
