<?php

namespace Tests\Unit\Services;

use App\DTOs\PayrollCalculationResult;
use App\Enums\EmploymentStatus;
use App\Enums\MaritalStatus;
use App\Models\BpjsConfig;
use App\Models\Employee;
use App\Models\Pph21Config;
use App\Models\PtkpConfig;
use App\Models\SalaryComponent;
use App\Services\BpjsCalculator;
use App\Services\OvertimeService;
use App\Services\PayrollCalculator;
use App\Services\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCalculatorEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private PayrollCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed BPJS config for current year
        $this->seedBpjsConfigs();

        // Seed PPh21 tax brackets
        $this->seedPph21Brackets();

        // Seed PTKP values
        $this->seedPtkpValues();

        $bpjsCalculator = new BpjsCalculator();
        $taxCalculator = new TaxCalculator(date('Y'));
        $overtimeService = $this->createMock(OvertimeService::class);

        $overtimeService->method('getOvertimeForPeriod')
            ->willReturn(0.0);

        $this->calculator = new PayrollCalculator(
            $bpjsCalculator,
            $taxCalculator,
            $overtimeService,
        );
    }

    // ─── BPJS Cap Edge Cases ─────────────────────────────────────

    private function seedBpjsConfigs(): void
    {
        $year = date('Y');

        // BPJS Kesehatan: 4% company, 1% employee, cap Rp12,000,000
        BpjsConfig::factory()->create([
            'type' => 'kesehatan', 'payer' => 'company', 'rate_percentage' => 4.0,
            'salary_cap' => 12000000, 'is_active' => true, 'applicable_year' => $year,
        ]);
        BpjsConfig::factory()->create([
            'type' => 'kesehatan', 'payer' => 'employee', 'rate_percentage' => 1.0,
            'salary_cap' => 12000000, 'is_active' => true, 'applicable_year' => $year,
        ]);

        // BPJS JHT: 3.7% company, 2% employee, no cap
        BpjsConfig::factory()->create([
            'type' => 'tk_jht', 'payer' => 'company', 'rate_percentage' => 3.7,
            'salary_cap' => null, 'is_active' => true, 'applicable_year' => $year,
        ]);
        BpjsConfig::factory()->create([
            'type' => 'tk_jht', 'payer' => 'employee', 'rate_percentage' => 2.0,
            'salary_cap' => null, 'is_active' => true, 'applicable_year' => $year,
        ]);

        // BPJS JP: 2% company, 1% employee, cap Rp10,000,000
        BpjsConfig::factory()->create([
            'type' => 'tk_jp', 'payer' => 'company', 'rate_percentage' => 2.0,
            'salary_cap' => 10000000, 'is_active' => true, 'applicable_year' => $year,
        ]);
        BpjsConfig::factory()->create([
            'type' => 'tk_jp', 'payer' => 'employee', 'rate_percentage' => 1.0,
            'salary_cap' => 10000000, 'is_active' => true, 'applicable_year' => $year,
        ]);

        // BPJS JKK: 0.24% company only
        BpjsConfig::factory()->create([
            'type' => 'tk_jkk', 'payer' => 'company', 'rate_percentage' => 0.24,
            'salary_cap' => null, 'is_active' => true, 'applicable_year' => $year,
        ]);

        // BPJS JKM: 0.3% company only
        BpjsConfig::factory()->create([
            'type' => 'tk_jkm', 'payer' => 'company', 'rate_percentage' => 0.30,
            'salary_cap' => null, 'is_active' => true, 'applicable_year' => $year,
        ]);
    }

    private function seedPph21Brackets(): void
    {
        $year = date('Y');

        $brackets = [
            [0, 60000000, 5],
            [60000000, 250000000, 15],
            [250000000, 500000000, 25],
            [500000000, 5000000000, 30],
            [5000000000, null, 35],
        ];

        foreach ($brackets as [$start, $end, $rate]) {
            Pph21Config::factory()->create([
                'income_bracket_start' => $start,
                'income_bracket_end'   => $end,
                'rate_percentage'      => $rate,
                'is_active'            => true,
                'applicable_year'       => $year,
            ]);
        }
    }

    private function seedPtkpValues(): void
    {
        $year = date('Y');

        $ptkp = [
            'TK/0' => 54000000,
            'TK/1' => 58500000,
            'TK/2' => 63000000,
            'TK/3' => 67500000,
            'K/0'  => 58500000,
            'K/1'  => 63000000,
            'K/2'  => 67500000,
            'K/3'  => 72000000,
        ];

        foreach ($ptkp as $category => $amount) {
            PtkpConfig::factory()->create([
                'category'       => $category,
                'annual_amount'  => $amount,
                'is_active'      => true,
                'applicable_year' => $year,
            ]);
        }
    }

    public function test_applies_bpjs_kesehatan_cap_for_high_salary(): void
    {
        // Salary above cap (Rp12,000,000) — BPJS Kesehatan should be capped
        $employee = Employee::factory()->create([
            'base_salary' => 20000000, // above cap
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        // BPJS Kesehatan should be calculated from capped salary (12,000,000)
        // 4% company = 480,000, 1% employee = 120,000
        $this->assertEquals(480000, $result->bpjsKesehatanCompany);
        $this->assertEquals(120000, $result->bpjsKesehatanEmployee);
    }

    public function test_applies_bpjs_jp_cap_for_high_salary(): void
    {
        // Salary above JP cap (Rp10,000,000) — JP should be capped
        $employee = Employee::factory()->create([
            'base_salary' => 20000000,
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        // JP from capped salary (10,000,000): 2% company = 200,000, 1% employee = 100,000
        $this->assertEquals(200000, $result->bpjsTkJpCompany);
        $this->assertEquals(100000, $result->bpjsTkJpEmployee);
    }

    public function test_does_not_apply_cap_for_salary_below_threshold(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000, // well below caps
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        // Full salary used: 4% = 200,000, 1% = 50,000
        $this->assertEquals(200000, $result->bpjsKesehatanCompany);
        $this->assertEquals(50000, $result->bpjsKesehatanEmployee);
    }

    // ─── PPh21 Tax Bracket Edge Cases ────────────────────────────

    public function test_returns_zero_tax_when_income_below_ptkp(): void
    {
        // Salary very low — PKP should be 0
        $employee = Employee::factory()->create([
            'base_salary' => 2000000, // Rp2,000,000/month
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        $this->assertEquals(0, $result->pph21);
    }

    public function test_calculates_first_bracket_tax_5_percent(): void
    {
        // Annual PKP around Rp50,000,000 — should be in 5% bracket
        $employee = Employee::factory()->create([
            'base_salary' => 7000000, // Rp7,000,000/month
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        // Should have some tax, but only in the 5% bracket
        $this->assertGreaterThan(0, $result->pph21);
        $this->assertLessThan(200000, $result->pph21); // under Rp200k
    }

    public function test_crosses_into_second_bracket_15_percent(): void
    {
        // High salary — should reach 15% bracket
        $employee = Employee::factory()->create([
            'base_salary' => 25000000, // Rp25,000,000/month
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        // Annual gross: 300,000,000 - position allowance 6,000,000 - PTKP 54,000,000
        // PKP ≈ 240,000,000 - BPJS deductions
        // First 60M at 5% = 3,000,000, then remaining ~180M at 15% = 27,000,000
        // Total annual tax ~30,000,000 / 12 ≈ 2,500,000/month
        $this->assertGreaterThan(2000000, $result->pph21);
        $this->assertLessThan(3500000, $result->pph21);
    }

    public function test_calculates_married_with_dependents_correctly(): void
    {
        // Married with 3 dependents — higher PTKP
        $employee = Employee::factory()->create([
            'base_salary' => 15000000,
            'marital_status' => MaritalStatus::Married,
            'dependents_count' => 3, // K/3
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        // PTKP K/3 = Rp72,000,000 (vs TK/0 = Rp54,000,000)
        // Higher PTKP = lower tax
        $details = $result->details;
        $this->assertEquals('K/3', $details['ptkp_category']);
        $this->assertEquals(72000000, $details['ptkp']);
        $this->assertGreaterThan(0, $result->pph21);
    }

    public function test_handles_maximum_dependents_ptkp(): void
    {
        // More than 3 dependents: additional Rp4,500,000/dependent beyond 3
        $employee = Employee::factory()->create([
            'base_salary' => 20000000,
            'marital_status' => MaritalStatus::Married,
            'dependents_count' => 5, // K/3 + 2 extra = base 72M + 2*4.5M = 81M
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        $details = $result->details;
        // PTKP = K/3 (72,000,000) + 2 extras (2 * 4,500,000) = 81,000,000
        $this->assertEquals('K/3', $details['ptkp_category']);
        $this->assertEquals(81000000, $details['ptkp']);
    }

    // ─── Component Edge Cases ─────────────────────────────────────

    public function test_handles_multiple_component_types(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        // Create all types of components
        SalaryComponent::factory()->create([
            'employee_id' => $employee->id, 'type' => 'allowance', 'amount' => 500000, 'is_active' => true,
        ]);
        SalaryComponent::factory()->create([
            'employee_id' => $employee->id, 'type' => 'deduction', 'amount' => 200000, 'is_active' => true,
        ]);
        SalaryComponent::factory()->create([
            'employee_id' => $employee->id, 'type' => 'bonus', 'amount' => 1000000, 'is_active' => true,
        ]);
        SalaryComponent::factory()->create([
            'employee_id' => $employee->id, 'type' => 'overtime', 'amount' => 300000, 'is_active' => true,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        $this->assertEquals(500000, $result->allowancesTotal);
        $this->assertEquals(200000, $result->deductionsTotal);
        $this->assertEquals(1000000, $result->bonusesTotal);
        $this->assertEquals(300000, $result->overtimePay);
        // Gross = base 5M + allowances 500K + bonus 1M + overtime 300K = 6,800,000
        $this->assertEquals(6800000, $result->grossSalary);
    }

    // ─── Overtime Edge Cases ──────────────────────────────────────

    public function test_includes_overtime_from_service(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        // Create a new mock for this specific test
        $overtimeService = $this->createMock(OvertimeService::class);
        $overtimeService->method('getOvertimeForPeriod')
            ->willReturn(750000.0); // Rp750,000 overtime this month

        $bpjsCalculator = new BpjsCalculator();
        $taxCalculator = new TaxCalculator(date('Y'));

        $calculator = new PayrollCalculator(
            $bpjsCalculator,
            $taxCalculator,
            $overtimeService,
        );

        $result = $calculator->calculateForEmployee($employee);

        // Base (5M) + overtime (750K) = 5,750,000
        $this->assertEquals(5750000, $result->grossSalary);
        $this->assertEquals(750000, $result->overtimePay);
    }

    // ─── Validation Edge Cases ───────────────────────────────────

    public function test_handles_null_marital_status_gracefully(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
            'marital_status' => null, // Should default to Single
        ]);

        // Should not throw
        $result = $this->calculator->calculateForEmployee($employee);

        $this->assertInstanceOf(PayrollCalculationResult::class, $result);
        $this->assertGreaterThan(0, $result->netSalary);
    }

    public function test_handles_minimum_wage_salary(): void
    {
        // UMK Bangkalan 2026 minimum
        $employee = Employee::factory()->create([
            'base_salary' => 2400000, // UMR level
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        $this->assertGreaterThan(0, $result->netSalary);
        // Net should be close to gross minus BPJS (very low tax or zero)
        $this->assertLessThan($result->grossSalary, $result->netSalary);
        $this->assertEquals(0, $result->pph21); // Below PTKP
    }

    public function test_handles_deductions_exceeding_gross(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        SalaryComponent::factory()->create([
            'employee_id' => $employee->id, 'type' => 'deduction', 'amount' => 10000000, 'is_active' => true,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        // Net may be negative since deductions exceed gross
        // This is expected behavior — system doesn't prevent negative net
        $this->assertLessThan($result->grossSalary, $result->netSalary);
        $this->assertEquals(10000000, $result->deductionsTotal);
    }

    public function test_provides_detailed_calculation_breakdown(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 10000000,
            'marital_status' => MaritalStatus::Married,
            'dependents_count' => 2,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        $details = $result->details;

        // Check all keys exist
        $this->assertArrayHasKey('base_salary', $details);
        $this->assertArrayHasKey('bpjs_kesehatan', $details);
        $this->assertArrayHasKey('bpjs_jht', $details);
        $this->assertArrayHasKey('bpjs_jp', $details);
        $this->assertArrayHasKey('pph21', $details);
        $this->assertArrayHasKey('ptkp', $details);
        $this->assertArrayHasKey('ptkp_category', $details);
        $this->assertArrayHasKey('marital_status', $details);
        $this->assertArrayHasKey('dependents', $details);
        $this->assertArrayHasKey('gross_annualized', $details);

        // Verify PTKP category
        $this->assertEquals('K/2', $details['ptkp_category']);
        $this->assertEquals(2, $details['dependents']);
        $this->assertEquals('K', $details['marital_status']);
    }

    /** @test */
    public function test_calculate_for_all_active_with_multiple_employees(): void
    {
        Employee::factory()->count(5)->create([
            'employment_status' => EmploymentStatus::Permanent,
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        $results = $this->calculator->calculateForAllActive();

        $this->assertCount(5, $results);
        foreach ($results as $result) {
            $this->assertInstanceOf(PayrollCalculationResult::class, $result);
            $this->assertGreaterThan(0, $result->netSalary);
        }
    }

    public function test_calculates_correct_net_salary(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        // Net = Gross - (BPJS employee portion + PPh21 + other deductions)
        $expectedDeductions = $result->bpjsKesehatanEmployee
            + $result->bpjsTkJhtEmployee
            + $result->bpjsTkJpEmployee
            + $result->pph21
            + $result->deductionsTotal;

        $expectedNet = $result->grossSalary - $expectedDeductions;

        $this->assertEquals($expectedNet, $result->netSalary);
    }
}
