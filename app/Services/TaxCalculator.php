<?php

namespace App\Services;

use App\Enums\MaritalStatus;
use App\Models\Pph21Config;
use App\Models\PtkpConfig;
use Illuminate\Support\Facades\Cache;

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

    /**
     * Reload tax brackets/PTKP for a specific year (e.g. the payroll period
     * year) instead of the current calendar year.
     */
    public function useYear(int $year): void
    {
        $year = $year ?: (int) date('Y');
        if ($year === $this->taxYear) {
            return;
        }
        $this->taxYear = $year;
        $this->brackets = [];
        $this->ptkpValues = [];
        $this->loadBrackets();
        $this->loadPtkpValues();
    }

    private function loadBrackets(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('pph21_configs')) {
            return; // use fallback brackets built into calculateProgressiveTax()
        }

        $this->brackets = Cache::remember("tax:pph21-brackets:{$this->taxYear}", 3600, function () {
            return Pph21Config::active()
                ->forYear($this->taxYear)
                ->orderBy('income_bracket_start')
                ->get()
                ->toArray();
        });
    }

    private function loadPtkpValues(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('ptkp_configs')) {
            return; // use fallback PTKP built into getPtkp()
        }

        $this->ptkpValues = Cache::remember("tax:ptkp-values:{$this->taxYear}", 3600, function () {
            $configs = PtkpConfig::active()
                ->forYear($this->taxYear)
                ->get();

            $values = [];

            foreach ($configs as $config) {
                $values[$config->category] = (float) $config->annual_amount;
            }

            return $values;
        });
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
     * Per Indonesian tax law (PMK 101/PMK.010/2016), dependents are capped at
     * 3 — additional dependents beyond 3 do NOT increase PTKP.
     */
    public function getPtkp(
        ?MaritalStatus $maritalStatus = null,
        int $dependents = 0
    ): float {
        $maritalStatus ??= MaritalStatus::Single;

        // Cap dependents at 3 — the statutory maximum. Extras add nothing.
        $categoryIndex = min($dependents, 3);
        $category = $maritalStatus->code() . '/' . $categoryIndex;

        $basePtkp = $this->ptkpValues[$category] ?? self::FALLBACK_PTKP;

        // TK/0 hardcoded fallback adjusts with dependents when no DB config
        if (!isset($this->ptkpValues[$category])) {
            $basePtkp = self::FALLBACK_PTKP
                + ($maritalStatus === MaritalStatus::Married ? 4500000 : 0)
                + ($categoryIndex * self::ADDITIONAL_DEPENDENT_PTKP);
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
     * Regular income (base + fixed allowances) is annualized ×12 and its tax
     * spread across the year. Irregular income (bonus/THR/overtime) must NOT be
     * annualized — annualizing it inflates the taxable base 12× and massively
     * over-taxes the employee. Instead, the irregular amount is added to the
     * annual base once, and only the incremental tax it produces is charged in
     * the month it is paid.
     *
     * @param float $monthlyRegularGross Recurring monthly gross (base + fixed allowances)
     * @param float $monthlyBpjsEmployeeDeductions Total employee-paid BPJS per month
     * @param MaritalStatus|null $maritalStatus
     * @param int $dependents Number of dependents (tanggungan)
     * @param float $irregularIncome One-off income this month (bonus/THR/overtime), added once
     * @return float Monthly PPh21 for this period
     */
    public function calculateMonthly(
        float $monthlyRegularGross,
        float $monthlyBpjsEmployeeDeductions = 0,
        ?MaritalStatus $maritalStatus = null,
        int $dependents = 0,
        float $irregularIncome = 0,
    ): float {
        $annualBpjsDeductions = $monthlyBpjsEmployeeDeductions * 12;

        // Dynamic PTKP based on employee tax profile
        $ptkp = $this->getPtkp($maritalStatus, $dependents);

        // --- Regular income: annualized and spread across 12 months ---
        $annualRegularGross = $monthlyRegularGross * 12;
        $regularPositionAllowance = min($annualRegularGross * 0.05, 6000000);
        $pkpRegular = $annualRegularGross - $regularPositionAllowance - $annualBpjsDeductions - $ptkp;
        $annualRegularTax = $pkpRegular > 0 ? $this->calculateProgressiveTax($pkpRegular) : 0;
        $monthlyRegularTax = $annualRegularTax / 12;

        // Fast path: no irregular income — behaves exactly as the annualized
        // regular-only calculation (preserves existing monthly results).
        if ($irregularIncome <= 0) {
            return round($monthlyRegularTax, 2);
        }

        // --- Add irregular income once and charge only its incremental tax ---
        $annualGrossWithIrregular = $annualRegularGross + $irregularIncome;
        $totalPositionAllowance = min($annualGrossWithIrregular * 0.05, 6000000);
        $pkpTotal = $annualGrossWithIrregular - $totalPositionAllowance - $annualBpjsDeductions - $ptkp;
        $annualTotalTax = $pkpTotal > 0 ? $this->calculateProgressiveTax($pkpTotal) : 0;

        // Tax attributable to the irregular income, charged in full this month
        $irregularTax = max(0, $annualTotalTax - $annualRegularTax);

        return round($monthlyRegularTax + $irregularTax, 2);
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
