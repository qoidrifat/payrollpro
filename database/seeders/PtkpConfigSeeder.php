<?php

namespace Database\Seeders;

use App\Models\PtkpConfig;
use Illuminate\Database\Seeder;

class PtkpConfigSeeder extends Seeder
{
    /**
     * Seed PTKP (Penghasilan Tidak Kena Pajak) values for 2025 and 2026.
     *
     * PTKP categories follow Indonesian tax law:
     *   TK/0 = Single, no dependents     → Rp 54.000.000
     *   TK/1 = Single, 1 dependent       → Rp 58.500.000
     *   TK/2 = Single, 2 dependents      → Rp 63.000.000
     *   TK/3 = Single, 3 dependents      → Rp 67.500.000
     *   K/0  = Married, 0 dependents     → Rp 58.500.000
     *   K/1  = Married, 1 dependent      → Rp 63.000.000
     *   K/2  = Married, 2 dependents     → Rp 67.500.000
     *   K/3  = Married, 3 dependents     → Rp 72.000.000
     *
     * Dependents beyond 3: + Rp 4.500.000 each (handled in TaxCalculator logic).
     */
    public function run(): void
    {
        $configs = [
            ['category' => 'TK/0', 'description' => 'Tidak Kawin, 0 tanggungan', 'annual_amount' => 54000000],
            ['category' => 'TK/1', 'description' => 'Tidak Kawin, 1 tanggungan', 'annual_amount' => 58500000],
            ['category' => 'TK/2', 'description' => 'Tidak Kawin, 2 tanggungan', 'annual_amount' => 63000000],
            ['category' => 'TK/3', 'description' => 'Tidak Kawin, 3 tanggungan', 'annual_amount' => 67500000],
            ['category' => 'K/0',  'description' => 'Kawin, 0 tanggungan',      'annual_amount' => 58500000],
            ['category' => 'K/1',  'description' => 'Kawin, 1 tanggungan',      'annual_amount' => 63000000],
            ['category' => 'K/2',  'description' => 'Kawin, 2 tanggungan',      'annual_amount' => 67500000],
            ['category' => 'K/3',  'description' => 'Kawin, 3 tanggungan',      'annual_amount' => 72000000],
        ];

        foreach ([2025, 2026] as $year) {
            foreach ($configs as $config) {
                PtkpConfig::firstOrCreate(
                    [
                        'category'        => $config['category'],
                        'applicable_year' => $year,
                    ],
                    [
                        'description'   => $config['description'],
                        'annual_amount' => $config['annual_amount'],
                        'is_active'     => true,
                    ]
                );
            }
        }

        $this->command->info('PTKP config seeded for 2025 and 2026.');
    }
}
