<?php

namespace App\Services;

use App\Models\PayrollItem;
use App\Models\Payslip;
use App\Models\Setting;

class PayslipService
{
    /**
     * Collect company settings for the payslip template.
     */
    private function getCompanySettings(): array
    {
        $logoPath = public_path('maqna.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $devLogoPath = public_path('logoo.png');
        $devLogoBase64 = '';
        if (file_exists($devLogoPath)) {
            $devLogoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($devLogoPath));
        }

        $directorSignaturePath = public_path('ttd-direktur.jpg');
        $directorSignatureBase64 = '';
        if (file_exists($directorSignaturePath)) {
            $directorSignatureBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($directorSignaturePath));
        }

        return [
            'company_name'    => Setting::getValue('company_name', 'PT Maqna Tech Lab'),
            'company_address' => Setting::getValue('company_address', 'Jl. Kelud No. 06, Bangkalan Regency, East Java, 69116'),
            'company_phone'   => Setting::getValue('company_phone', '0021044521'),
            'company_email'   => Setting::getValue('company_email', 'admin@maqnatechlab.com'),
            'company_director' => Setting::getValue('company_director', 'Sofian Eka Sandra'),
            'company_logo'    => $logoBase64,  // Base64 inline to avoid Imagick file loading
            'dev_logo'        => $devLogoBase64,
            'director_signature' => $directorSignatureBase64,
        ];
    }

    /**
     * Prepare view data for the payslip template (used by both PDF and preview).
     */
    public function getViewData(PayrollItem $payrollItem): array
    {
        $payrollItem->load(['employee', 'payroll']);

        $payslipNumber = 'PS-' . date('Ymd') . '-' . str_pad($payrollItem->id, 4, '0', STR_PAD_LEFT);

        $baseSalary = $payrollItem->calculation_details['base_salary'] ?? 0;
        if (!$baseSalary) {
            $baseSalary = $payrollItem->gross_salary
                - $payrollItem->allowances_total
                - $payrollItem->bonuses_total
                - $payrollItem->overtime_pay;
        }

        $totalDeductions = $payrollItem->bpjs_kesehatan_employee
            + $payrollItem->bpjs_tk_jht_employee
            + $payrollItem->bpjs_tk_jp_employee
            + $payrollItem->pph21
            + $payrollItem->deductions_total;

        return [
            'item'             => $payrollItem,
            'employee'         => $payrollItem->employee,
            'payroll'          => $payrollItem->payroll,
            'payslipNumber'    => $payslipNumber,
            'baseSalary'       => $baseSalary,
            'totalDeductions'  => $totalDeductions,
            'company'          => $this->getCompanySettings(),
        ];
    }

    /**
     * Generate and save a payslip PDF.
     * Configures DomPDF to avoid Imagick (prevents Windows registry errors).
     */
    public function generate(PayrollItem $payrollItem): Payslip
    {
        $data = $this->getViewData($payrollItem);

        // Configure DomPDF to avoid Imagick (fixes Windows CoderModulesPath error)
        // Use array format since Barryvdh's wrapper expects array, not Options object
        $pdfWrapper = app('dompdf.wrapper');
        $pdfWrapper->setOptions([
            'isRemoteEnabled' => true,
            'isJavascriptEnabled' => true,
            'dpi' => 96,
        ]);
        $pdfWrapper->loadView('payslips.template', $data);
        $pdfWrapper->render();
        $this->addContinuationHeaders($pdfWrapper, $data);

        $filename = sprintf(
            'payslip_%s_%s_%s.pdf',
            $payrollItem->payroll->period_end->format('Ym'),
            str_replace(' ', '_', $payrollItem->employee->full_name),
            $payrollItem->id
        );

        $path = 'payslips/' . $filename;
        $fullPath = storage_path('app/public/' . $path);
        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        file_put_contents($fullPath, $pdfWrapper->output());

        return Payslip::updateOrCreate(
            ['payroll_item_id' => $payrollItem->id],
            [
                'payslip_number' => $data['payslipNumber'],
                'pdf_path' => $path,
                'generated_at' => now(),
            ]
        );
    }

    /**
     * Draw a compact branded header on every page after the first page.
     */
    private function addContinuationHeaders($pdfWrapper, array $data): void
    {
        $companyLogoPath = public_path('maqna.png');
        $companyLogoPath = file_exists($companyLogoPath) ? $companyLogoPath : null;

        $period = \Carbon\Carbon::parse($data['payroll']->period_start)->format('d M Y')
            . ' - '
            . \Carbon\Carbon::parse($data['payroll']->period_end)->format('d M Y');
        $companyName = (string) $data['company']['company_name'];
        $companyAddress = (string) $data['company']['company_address'];
        $companyContact = 'Telp: ' . $data['company']['company_phone'] . ' - Email: ' . $data['company']['company_email'];
        $payslipNumber = (string) $data['payslipNumber'];
        $status = strtoupper($data['payroll']->status->value ?? (string) $data['payroll']->status);

        $pdfWrapper->getDomPDF()->getCanvas()->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($companyLogoPath, $companyName, $companyAddress, $companyContact, $period, $payslipNumber, $status) {
            if ($pageNumber === 1) {
                return;
            }

            $pageWidth = $canvas->get_width();
            $marginX = 34;
            $headerY = 24;
            $headerWidth = $pageWidth - ($marginX * 2);
            $headerHeight = 62;
            $cornerRadius = 12;

            $canvas->clipping_roundrectangle($marginX, $headerY, $headerWidth, $headerHeight, $cornerRadius, $cornerRadius, $cornerRadius, $cornerRadius);
            $canvas->filled_rectangle($marginX, $headerY, $headerWidth * 0.46, $headerHeight, [0.118, 0.161, 0.231]);
            $canvas->filled_rectangle($marginX + ($headerWidth * 0.46), $headerY, $headerWidth * 0.34, $headerHeight, [0.114, 0.306, 0.847]);
            $canvas->filled_rectangle($marginX + ($headerWidth * 0.80), $headerY, $headerWidth * 0.20, $headerHeight, [0.486, 0.227, 0.929]);
            $canvas->filled_rectangle($marginX + $headerWidth - 92, $headerY - 22, 104, 104, [1, 1, 1, 'alpha' => 0.05]);
            $canvas->filled_rectangle($marginX - 28, $headerY + 42, 78, 78, [1, 1, 1, 'alpha' => 0.05]);
            $canvas->clipping_end();

            if ($companyLogoPath) {
                $canvas->image($companyLogoPath, $marginX + 14, $headerY + 13, 36, 36);
                $textX = $marginX + 64;
            } else {
                $textX = $marginX + 16;
            }

            $fontBold = $fontMetrics->getFont('Helvetica', 'bold');
            $fontRegular = $fontMetrics->getFont('Helvetica', 'normal');

            $canvas->text($textX, $headerY + 13, $companyName, $fontBold, 10, [1, 1, 1]);
            $canvas->text($textX, $headerY + 28, $companyAddress, $fontRegular, 7, [0.75, 0.86, 1]);
            $canvas->text($textX, $headerY + 40, $companyContact, $fontRegular, 7, [0.75, 0.86, 1]);

            $rightX = $pageWidth - $marginX - 96;
            $canvas->rectangle($rightX - 8, $headerY + 11, 74, 18, [1, 1, 1, 'alpha' => 0.35], 0.7);
            $canvas->text($rightX + 8, $headerY + 15, 'SLIP GAJI', $fontBold, 7, [1, 1, 1]);
            $canvas->text($rightX - 5, $headerY + 36, 'No. ' . $payslipNumber, $fontRegular, 6.5, [0.75, 0.86, 1]);
            $canvas->text($rightX + 7, $headerY + 48, 'Status: ' . $status . ' | Hal. ' . $pageNumber . '/' . $pageCount, $fontRegular, 6.5, [0.75, 0.86, 1]);
            $canvas->text($textX, $headerY + 52, 'Periode: ' . $period, $fontRegular, 6.5, [0.75, 0.86, 1]);
        });
    }

}
