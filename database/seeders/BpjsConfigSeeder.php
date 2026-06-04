<?php

namespace Database\Seeders;

use App\Models\BpjsConfig;
use Illuminate\Database\Seeder;

class BpjsConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            [
                'name' => 'BPJS Kesehatan - Company',
                'type' => 'kesehatan',
                'payer' => 'company',
                'rate_percentage' => 4.00,
                'salary_cap' => 12000000,
                'applicable_year' => 2025,
                'description' => '4% dari gaji bulanan, maksimal Rp 12.000.000',
            ],
            [
                'name' => 'BPJS Kesehatan - Employee',
                'type' => 'kesehatan',
                'payer' => 'employee',
                'rate_percentage' => 1.00,
                'salary_cap' => 12000000,
                'applicable_year' => 2025,
                'description' => '1% dari gaji bulanan, maksimal Rp 12.000.000',
            ],
            [
                'name' => 'BPJS TK JHT - Company',
                'type' => 'tk_jht',
                'payer' => 'company',
                'rate_percentage' => 3.70,
                'salary_cap' => null,
                'applicable_year' => 2025,
                'description' => '3.7% dari gaji bulanan',
            ],
            [
                'name' => 'BPJS TK JHT - Employee',
                'type' => 'tk_jht',
                'payer' => 'employee',
                'rate_percentage' => 2.00,
                'salary_cap' => null,
                'applicable_year' => 2025,
                'description' => '2% dari gaji bulanan',
            ],
            [
                'name' => 'BPJS TK JP - Company',
                'type' => 'tk_jp',
                'payer' => 'company',
                'rate_percentage' => 2.00,
                'salary_cap' => 10547400,
                'applicable_year' => 2025,
                'description' => '2% dari gaji bulanan, maksimal Rp 10.547.400 (PPU 2026)',
            ],
            [
                'name' => 'BPJS TK JP - Employee',
                'type' => 'tk_jp',
                'payer' => 'employee',
                'rate_percentage' => 1.00,
                'salary_cap' => 10547400,
                'applicable_year' => 2025,
                'description' => '1% dari gaji bulanan, maksimal Rp 10.547.400 (PPU 2026)',
            ],
            [
                'name' => 'BPJS TK JKK',
                'type' => 'tk_jkk',
                'payer' => 'company',
                'rate_percentage' => 0.24,
                'salary_cap' => null,
                'applicable_year' => 2025,
                'description' => '0.24% dari gaji bulanan (company only, risiko rendah). Range: 0.24%-1.74% sesuai klasifikasi risiko usaha.',
            ],
            [
                'name' => 'BPJS TK JKM',
                'type' => 'tk_jkm',
                'payer' => 'company',
                'rate_percentage' => 0.30,
                'salary_cap' => null,
                'applicable_year' => 2025,
                'description' => '0.3% dari gaji bulanan (company only)',
            ],
        ];

        foreach ([2025, 2026] as $year) {
            foreach ($configs as $config) {
                BpjsConfig::create(array_merge($config, ['applicable_year' => $year]));
            }
        }
    }
}
