<?php

namespace App\Services;

use App\Enums\MaritalStatus;
use App\Models\Pph21Config;
use App\Models\PtkpConfig;

class TaxCalculator
{
    /** Fallback PTKP when no DB config exists (TK/0 = single, no dependents) */
    private const FALLBACK_PTKP = 54000000;

    /** Additional PTKP per dependent beyond the 3 covered by category */
    private const ADDITIONAL_DEPENDENT_PTKP = 4500000;

    private array $brackets = [];

    private array $ptkpValues = [];

    private int $taxYear;

    public function __construct(int $taxYear = 0)
    {
        $this->taxYear = $taxYear ?: (int) date('Y');
        $this->loadBrackets();
        $this->loadPtkpValues();
    }

    private function loadBrackets(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('pph21_configs')) {
            return; // use fallback brackets built into calculateProgressiveTax()
        }

        $this->brackets = Pph21Config::active()
            ->forYear($this->taxYear)
            ->orderBy('income_bracket_start')
            ->get()
            ->toArray();
    }

    private function loadPtkpValues(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('ptkp_configs')) {
            return; // use fallback PTKP built into getPtkp()
        }

        $configs = PtkpConfig::active()
            ->forYear($this->taxYear)
            ->get();

        foreach ($configs as $config) {
            $this->ptkpValues[$config->category] = (float) $config->annual_amount;
        }
    }

    /**
     * Resolve PTKP for an employee based on marital status and dependents.
     *
     * PTKP categories (standard):
     *   TK/0 = Single, 0 dependents → 54,000,000
     *   TK/1 = Single, 1 dependent  → 58,500,000
     *   TK/2 = Single, 2 dependents → 63,000,000
     *   TK/3 = Single, 3 dependents → 67,500,000
     *   K/0  = Married, 0 dependents → 58,500,000
     *   K/1  = Married, 1 dependent  → 63,000,000
     *   K/2  = Married, 2 dependents → 67,500,000
     *   K/3  = Married, 3 dependents → 72,000,000
     *
     * Dependents > 3 add Rp 4,500,000 each.
     */
    public function getPtkp(
        ?MaritalStatus $maritalStatus = null,
        int $dependents = 0
    ): float {
        $maritalStatus ??= MaritalStatus::Single;

        // Cap the category index at 3 — extras are handled by per-dependent addition
        $categoryIndex = min($dependents, 3);
        $category = $maritalStatus->code() . '/' . $categoryIndex;

        $basePtkp = $this->ptkpValues[$category] ?? self::FALLBACK_PTKP;

        // TK/0 hardcoded fallback adjusts with dependents when no DB config
        if (!isset($this->ptkpValues[$category])) {
            $basePtkp = self::FALLBACK_PTKP
                + ($maritalStatus === MaritalStatus::Married ? 4500000 : 0)
                + ($categoryIndex * self::ADDITIONAL_DEPENDENT_PTKP);
        }

        // Additional dependents beyond 3
        if ($dependents > 3) {
            $extra = ($dependents - 3) * self::ADDITIONAL_DEPENDENT_PTKP;
            $basePtkp += $extra;
        }

        return $basePtkp;
    }

    /**
     * Build the PTKP category key from marital status and dependent count.
     *
     * Examples: "TK/0", "K/2"
     */
    public function getPtkpCategory(?MaritalStatus $maritalStatus = null, int $dependents = 0): string
    {
        $maritalStatus ??= MaritalStatus::Single;
        return $maritalStatus->code() . '/' . min($dependents, 3);
    }

    /**
     * Calculate PPh21 monthly tax for an employee.
     *
     * @param float $monthlyGrossSalary
     * @param float $monthlyBpjsEmployeeDeductions Total employee-paid BPJS per month
     * @param MaritalStatus|null $maritalStatus
     * @param int $dependents Number of dependents (tanggungan)
     * @return float Monthly PPh21
     */
    public function calculateMonthly(
        float $monthlyGrossSalary,
        float $monthlyBpjsEmployeeDeductions = 0,
        ?MaritalStatus $maritalStatus = null,
        int $dependents = 0,
    ): float {
        $annualGross = $monthlyGrossSalary * 12;

        // Position allowance (biaya jabatan): 5% of gross, max Rp 6,000,000/year
        $positionAllowance = min($annualGross * 0.05, 6000000);

        $annualBpjsDeductions = $monthlyBpjsEmployeeDeductions * 12;

        // Dynamic PTKP based on employee tax profile
        $ptkp = $this->getPtkp($maritalStatus, $dependents);

        // Taxable income (PKP - Penghasilan Kena Pajak)
        $pkp = $annualGross - $positionAllowance - $annualBpjsDeductions - $ptkp;

        if ($pkp <= 0) {
            return 0;
        }

        $annualTax = $this->calculateProgressiveTax($pkp);

        return round($annualTax / 12, 2);
    }

    private function calculateProgressiveTax(float $pkp): float
    {
        $tax = 0;
        $remainingIncome = $pkp;

        // Fallback brackets when no DB config exists
        $brackets = $this->brackets ?: [
            ['income_bracket_start' => 0,         'income_bracket_end' => 60000000,   'rate_percentage' => 5],
            ['income_bracket_start' => 60000000,  'income_bracket_end' => 250000000,  'rate_percentage' => 15],
            ['income_bracket_start' => 250000000, 'income_bracket_end' => 500000000,  'rate_percentage' => 25],
            ['income_bracket_start' => 500000000, 'income_bracket_end' => 5000000000, 'rate_percentage' => 30],
            ['income_bracket_start' => 5000000000,'income_bracket_end' => null,        'rate_percentage' => 35],
        ];

        foreach ($brackets as $bracket) {
            $start = (float) $bracket['income_bracket_start'];
            $end = $bracket['income_bracket_end']
                ? (float) $bracket['income_bracket_end']
                : PHP_FLOAT_MAX;
            $rate = (float) $bracket['rate_percentage'] / 100;

            // remainingIncome already represents the amount not yet allocated to brackets.
            // Simply cap it at the bracket width rather than subtracting start again.
            $taxableInBracket = min($remainingIncome, $end - $start);
            if ($taxableInBracket <= 0) {
                break;
            }

            $tax += $taxableInBracket * $rate;
            $remainingIncome -= $taxableInBracket;
        }

        return $tax;
    }

    public function getTaxYear(): int
    {
        return $this->taxYear;
    }
}
