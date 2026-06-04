<?php

namespace Database\Seeders;

use App\Models\Pph21Config;
use Illuminate\Database\Seeder;

class Pph21ConfigSeeder extends Seeder
{
    public function run(): void
    {
        // PTKP 2025: Rp 54.000.000/year for TK/0 (single, no dependents)
        // Progressive rates per Indonesian tax law
        $brackets = [
            ['income_bracket_start' => 0,        'income_bracket_end' => 60000000,     'rate_percentage' => 5.0],
            ['income_bracket_start' => 60000000,  'income_bracket_end' => 250000000,   'rate_percentage' => 15.0],
            ['income_bracket_start' => 250000000, 'income_bracket_end' => 500000000,   'rate_percentage' => 25.0],
            ['income_bracket_start' => 500000000, 'income_bracket_end' => 5000000000,  'rate_percentage' => 30.0],
            ['income_bracket_start' => 5000000000,'income_bracket_end' => null,         'rate_percentage' => 35.0],
        ];

        foreach ([2025, 2026] as $year) {
            foreach ($brackets as $bracket) {
                Pph21Config::create(array_merge($bracket, [
                    'applicable_year' => $year,
                    'is_active' => true,
                ]));
            }
        }
    }
}
