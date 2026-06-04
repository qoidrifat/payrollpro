<?php

namespace App\Services;

use App\Models\BpjsConfig;

class BpjsCalculator
{
    private array $configs = [];

    public function __construct()
    {
        $this->loadConfigs();
    }

    private function loadConfigs(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('bpjs_configs')) {
            return; // use hardcoded default rates in calculate methods
        }

        $configs = BpjsConfig::active()->forYear(date('Y'))->get();

        foreach ($configs as $config) {
            $key = "{$config->type}_{$config->payer}";
            $this->configs[$key] = $config;
        }
    }

    /**
     * Calculate BPJS Kesehatan contributions.
     * Rate: 5% total (4% company, 1% employee), capped at salary_cap.
     */
    public function calculateKesehatan(float $monthlySalary): array
    {
        $cappedSalary = $this->applyCap($monthlySalary, 'kesehatan_company');

        $companyConfig = $this->configs['kesehatan_company'] ?? null;
        $employeeConfig = $this->configs['kesehatan_employee'] ?? null;
        $companyRate = $companyConfig?->rate_percentage ?? 4.0;
        $employeeRate = $employeeConfig?->rate_percentage ?? 1.0;

        return [
            'company' => round($cappedSalary * $companyRate / 100, 2),
            'employee' => round($cappedSalary * $employeeRate / 100, 2),
        ];
    }

    /**
     * Calculate BPJS TK JHT (Jaminan Hari Tua).
     * Rate: 5.7% total (3.7% company, 2% employee).
     */
    public function calculateJht(float $monthlySalary): array
    {
        $companyConfig = $this->configs['tk_jht_company'] ?? null;
        $employeeConfig = $this->configs['tk_jht_employee'] ?? null;
        $companyRate = $companyConfig?->rate_percentage ?? 3.7;
        $employeeRate = $employeeConfig?->rate_percentage ?? 2.0;

        return [
            'company' => round($monthlySalary * $companyRate / 100, 2),
            'employee' => round($monthlySalary * $employeeRate / 100, 2),
        ];
    }

    /**
     * Calculate BPJS TK JP (Jaminan Pensiun).
     * Rate: 3% total (2% company, 1% employee), capped at salary_cap.
     */
    public function calculateJp(float $monthlySalary): array
    {
        $cappedSalary = $this->applyCap($monthlySalary, 'tk_jp_company');

        $companyConfig = $this->configs['tk_jp_company'] ?? null;
        $employeeConfig = $this->configs['tk_jp_employee'] ?? null;
        $companyRate = $companyConfig?->rate_percentage ?? 2.0;
        $employeeRate = $employeeConfig?->rate_percentage ?? 1.0;

        return [
            'company' => round($cappedSalary * $companyRate / 100, 2),
            'employee' => round($cappedSalary * $employeeRate / 100, 2),
        ];
    }

    /**
     * Calculate BPJS TK JKK (Jaminan Kecelakaan Kerja).
     * Company only. Default rate: 0.24%.
     */
    public function calculateJkk(float $monthlySalary): float
    {
        $config = $this->configs['tk_jkk_company'] ?? null;
        $rate = $config?->rate_percentage ?? 0.24;
        return round($monthlySalary * $rate / 100, 2);
    }

    /**
     * Calculate BPJS TK JKM (Jaminan Kematian).
     * Company only. Default rate: 0.3%.
     */
    public function calculateJkm(float $monthlySalary): float
    {
        $config = $this->configs['tk_jkm_company'] ?? null;
        $rate = $config?->rate_percentage ?? 0.30;
        return round($monthlySalary * $rate / 100, 2);
    }

    private function applyCap(float $salary, string $configKey): float
    {
        $config = $this->configs[$configKey] ?? null;
        $cap = $config?->salary_cap;
        if ($cap && $salary > $cap) {
            return $cap;
        }
        return $salary;
    }
}
