<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\PayslipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayslipTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_template_renders_like_the_preview_template(): void
    {
        $item = $this->makePayrollItem();

        $html = view('payslips.template', app(PayslipService::class)->getViewData($item))->render();

        $this->assertStringContainsString('class="pdf-preview-card ', $html);
        $this->assertStringContainsString('class="header-shell"', $html);
        $this->assertStringContainsString('Data Karyawan', $html);
        $this->assertStringContainsString('Periode &amp; Pembayaran', $html);
        $this->assertStringContainsString('01 May 2026 - 31 May 2026', $html);
        $this->assertStringContainsString('Status: APPROVED', $html);
        $this->assertStringContainsString('BPJS TK - JHT (2%)', $html);
        $this->assertStringContainsString('class="section company-contribution-section"', $html);
        $this->assertStringContainsString('class="pdf-preview-card page-2-card"', $html);
        $this->assertStringContainsString('table-layout: fixed;', $html);
        $this->assertStringContainsString('Take Home Pay', $html);
        $this->assertStringContainsString('Rp 7.150.000', $html);
        $this->assertStringContainsString('Sistem dikembangkan oleh:', $html);
        $this->assertStringContainsString('class="developer-logo"', $html);
        $this->assertStringContainsString('class="director-signature-img"', $html);
        $this->assertStringNotContainsString('â', $html);
    }

    public function test_pdf_template_can_be_generated_by_the_download_service(): void
    {
        $item = $this->makePayrollItem();

        $payslip = app(PayslipService::class)->generate($item);
        $path = storage_path('app/public/' . $payslip->pdf_path);

        $this->assertFileExists($path);

        @unlink($path);
    }

    private function makePayrollItem(): PayrollItem
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Siti',
            'last_name' => 'Aminah',
            'nik' => '1234567890123456',
            'npwp' => '09.123.456.7-890.000',
            'position' => 'Finance Staff',
            'department' => 'Finance',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
        ]);

        $payroll = Payroll::factory()->create([
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
            'status' => 'approved',
        ]);

        return PayrollItem::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employee->id,
            'gross_salary' => 7500000,
            'allowances_total' => 500000,
            'bonuses_total' => 250000,
            'overtime_pay' => 125000,
            'bpjs_kesehatan_employee' => 75000,
            'bpjs_tk_jht_employee' => 150000,
            'bpjs_tk_jp_employee' => 75000,
            'bpjs_kesehatan_company' => 300000,
            'bpjs_tk_jht_company' => 277500,
            'bpjs_tk_jp_company' => 150000,
            'bpjs_tk_jkk' => 18000,
            'bpjs_tk_jkm' => 22500,
            'pph21' => 100000,
            'deductions_total' => 50000,
            'net_salary' => 7150000,
            'calculation_details' => ['base_salary' => 6625000],
        ]);
    }
}
