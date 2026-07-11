<?php

namespace Tests\Unit\Services;

use App\Enums\MaritalStatus;
use App\Services\TaxCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaxCalculatorTest extends TestCase
{
    private TaxCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new TaxCalculator(2025);
    }

    public function test_ptkp_tk0(): void
    {
        $ptkp = $this->calculator->getPtkp(MaritalStatus::Single, 0);
        $this->assertEquals(54000000, $ptkp);
    }

    public function test_ptkp_tk1(): void
    {
        $ptkp = $this->calculator->getPtkp(MaritalStatus::Single, 1);
        $this->assertEquals(58500000, $ptkp);
    }

    public function test_ptkp_tk3(): void
    {
        $ptkp = $this->calculator->getPtkp(MaritalStatus::Single, 3);
        $this->assertEquals(67500000, $ptkp);
    }

    public function test_ptkp_k0(): void
    {
        $ptkp = $this->calculator->getPtkp(MaritalStatus::Married, 0);
        $this->assertEquals(58500000, $ptkp);
    }

    public function test_ptkp_k3(): void
    {
        $ptkp = $this->calculator->getPtkp(MaritalStatus::Married, 3);
        $this->assertEquals(72000000, $ptkp);
    }

    public function test_ptkp_tk5(): void
    {
        // Dependents capped at 3 (PMK 101/2016) — 5 dependents = TK/3, no extra.
        $ptkp = $this->calculator->getPtkp(MaritalStatus::Single, 5);
        $this->assertEquals(67500000, $ptkp);
    }

    public function test_ptkp_category_tk0(): void
    {
        $category = $this->calculator->getPtkpCategory(MaritalStatus::Single, 0);
        $this->assertEquals('TK/0', $category);
    }

    public function test_ptkp_category_k2(): void
    {
        $category = $this->calculator->getPtkpCategory(MaritalStatus::Married, 2);
        $this->assertEquals('K/2', $category);
    }

    public function test_ptkp_category_caps_at_3(): void
    {
        $category = $this->calculator->getPtkpCategory(MaritalStatus::Married, 5);
        $this->assertEquals('K/3', $category);
    }

    #[DataProvider('taxCalculationProvider')]
    public function test_calculate_monthly(
        float $monthlyGross,
        float $monthlyBpjs,
        ?MaritalStatus $status,
        int $dependents,
        float $expectedMonthlyTax,
    ): void {
        $tax = $this->calculator->calculateMonthly(
            $monthlyGross,
            $monthlyBpjs,
            $status,
            $dependents,
        );
        $this->assertEquals($expectedMonthlyTax, $tax);
    }

    public static function taxCalculationProvider(): array
    {
        return [
            // Below PTKP - no tax
            'TK/0 below PTKP'     => [4000000, 100000, MaritalStatus::Single, 0, 0],
            'K/3 below PTKP'      => [5000000, 100000, MaritalStatus::Married, 3, 0],

            // TK/0, 10jt = 120jt/yr
            // Position allowance: min(6jt, 6jt) = 6jt
            // BPJS: 300k*12 = 3.6jt
            // PTKP: 54jt
            // PKP: 120-6-3.6-54 = 56.4jt → all @5% = 2.82jt/yr = 235rb/mo
            'TK/0, 10jt gross'    => [10000000, 300000, MaritalStatus::Single, 0, 235000.00],

            // K/1, 15jt = 180jt/yr
            // Position allowance: min(9jt, 6jt) = 6jt
            // BPJS: 400k*12 = 4.8jt
            // PTKP K/1: 54jt + 4.5jt + 4.5jt = 63jt
            // PKP: 180-6-4.8-63 = 106.2jt
            // Bracket 1: 60jt @5% = 3jt
            // Bracket 2: 46.2jt @15% = 6.93jt
            // Total: 9.93jt/yr = 827.5rb/mo
            'K/1, 15jt gross'     => [15000000, 400000, MaritalStatus::Married, 1, 827500.00],

            // TK/0, 50jt = 600jt/yr
            // Position allowance: min(30jt, 6jt) = 6jt
            // BPJS: 1jt*12 = 12jt
            // PTKP: 54jt
            // PKP: 600-6-12-54 = 528jt
            // Bracket 1: 60jt @5% = 3jt
            // Bracket 2: 190jt @15% = 28.5jt
            // Bracket 3: 250jt @25% = 62.5jt
            // Bracket 4: 28jt @30% = 8.4jt
            // Total: 102.4jt/yr = 8,533,333.33/mo
            'TK/0, 50jt gross'    => [50000000, 1000000, MaritalStatus::Single, 0, 8533333.33],
        ];
    }

    public function test_tax_year(): void
    {
        $this->assertEquals(2025, $this->calculator->getTaxYear());
    }

    public function test_zero_salary_returns_zero_tax(): void
    {
        $tax = $this->calculator->calculateMonthly(0, 0, MaritalStatus::Single, 0);
        $this->assertEquals(0, $tax);
    }

    public function test_null_marital_status_defaults_to_single(): void
    {
        $taxWithNull = $this->calculator->calculateMonthly(8000000, 200000, null, 0);
        $taxWithSingle = $this->calculator->calculateMonthly(8000000, 200000, MaritalStatus::Single, 0);
        $this->assertEquals($taxWithSingle, $taxWithNull);
    }
}
