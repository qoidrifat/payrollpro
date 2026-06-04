<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\SalaryComponent;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\PayrollCalculator;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    private array $employees = [];
    private array $niks = [];

    public function run(): void
    {
        $this->createEmployees();
        $this->createSalaryComponents();
        $this->createAttendanceRecords();
        $this->createPayrollRuns();
    }

    private function createEmployees(): void
    {
        // Hapus dan buat ulang data karyawan
        Employee::query()->forceDelete();

        // Gaji disesuaikan dengan UMK Bangkalan 2026 (Rp2.550.274) untuk sektor IT
        // Sektor IT biasanya 1.1x - 2.7x dari UMK tergantung level
        $data = [
            [
                'first_name' => 'Ahmad', 'last_name' => 'Fauzi', 'gender' => 'male',
                'position' => 'Senior Developer', 'department' => 'Engineering',
                'base_salary' => 7000000, 'employment_status' => 'permanent',
                'join_date' => '2024-03-15',
                'nik' => '3529420242275983',
                'marital_status' => 'married', 'dependents_count' => 2,
            ],
            [
                'first_name' => 'Rina', 'last_name' => 'Kusuma', 'gender' => 'female',
                'position' => 'UI/UX Designer', 'department' => 'Design',
                'base_salary' => 4000000, 'employment_status' => 'permanent',
                'join_date' => '2024-06-01',
                'marital_status' => 'single', 'dependents_count' => 0,
            ],
            [
                'first_name' => 'Budi', 'last_name' => 'Santoso', 'gender' => 'male',
                'position' => 'Junior Developer', 'department' => 'Engineering',
                'base_salary' => 4500000, 'employment_status' => 'contract',
                'join_date' => '2025-01-10',
                'marital_status' => 'single', 'dependents_count' => 0,
            ],
            [
                'first_name' => 'Dewi', 'last_name' => 'Lestari', 'gender' => 'female',
                'position' => 'Project Manager', 'department' => 'Management',
                'base_salary' => 7500000, 'employment_status' => 'permanent',
                'join_date' => '2023-09-01',
                'marital_status' => 'married', 'dependents_count' => 1,
            ],
            [
                'first_name' => 'Eko', 'last_name' => 'Prasetyo', 'gender' => 'male',
                'position' => 'System Administrator', 'department' => 'Infrastructure',
                'base_salary' => 5000000, 'employment_status' => 'permanent',
                'join_date' => '2024-08-20',
                'marital_status' => 'married', 'dependents_count' => 3,
            ],
            [
                'first_name' => 'Siti', 'last_name' => 'Nurhaliza', 'gender' => 'female',
                'position' => 'Content Writer', 'department' => 'Marketing',
                'base_salary' => 2800000, 'employment_status' => 'contract',
                'join_date' => '2025-03-01',
                'marital_status' => 'single', 'dependents_count' => 0,
            ],
            [
                'first_name' => 'Hendra', 'last_name' => 'Wijaya', 'gender' => 'male',
                'position' => 'Digital Marketer', 'department' => 'Marketing',
                'base_salary' => 3200000, 'employment_status' => 'permanent',
                'join_date' => '2025-06-15',
                'marital_status' => 'married', 'dependents_count' => 1,
            ],
            [
                'first_name' => 'Maya', 'last_name' => 'Anggraini', 'gender' => 'female',
                'position' => 'Finance & HR', 'department' => 'Operations',
                'base_salary' => 3500000, 'employment_status' => 'permanent',
                'join_date' => '2024-01-10',
                'marital_status' => 'married', 'dependents_count' => 0,
            ],
        ];

        foreach ($data as $i => $emp) {
            $nik = $emp['nik'] ?? $this->generateNik();
            $this->niks[] = $nik;

            $employee = Employee::create(array_merge($emp, [
                'nik'  => $nik,
                'npwp' => $this->generateNpwp(),
                'bpjs_kesehatan' => $this->generateBpjsNo(),
                'bpjs_ketenagakerjaan' => $this->generateBpjsNo(),
                'phone' => '08' . random_int(1000000000, 9999999999),
                'address' => 'Jl. Raya Bangkalan No. ' . random_int(1, 200),
                'city' => 'Bangkalan',
                'province' => 'Jawa Timur',
                'postal_code' => '69100',
                'bank_name' => ['BCA', 'BRI', 'Mandiri', 'BNI'][$i % 4],
                'bank_account_number' => $this->generateBankAccount(),
                'bank_account_name' => $emp['first_name'] . ' ' . $emp['last_name'],
            ]));

            $this->employees[] = $employee;
        }
    }

    private function createSalaryComponents(): void
    {
        $components = [];
        foreach ($this->employees as $emp) {
            $baseSalary = $emp->base_salary;

            $allowanceRows = [
                ['Tunjangan Transport', 'allowance', round($baseSalary * 0.05), true],
                ['Tunjangan Makan', 'allowance', round($baseSalary * 0.03), true],
            ];

            if ($emp->position === 'Senior Developer' || $emp->position === 'Project Manager') {
                $allowanceRows[] = ['Tunjangan Komunikasi', 'allowance', 300000, true];
            }

            foreach ($allowanceRows as $row) {
                SalaryComponent::create([
                    'employee_id' => $emp->id,
                    'name' => $row[0],
                    'type' => $row[1],
                    'amount' => $row[2],
                    'is_taxable' => $row[3],
                    'is_active' => true,
                ]);
            }

            if ($emp->id % 3 === 0) {
                SalaryComponent::create([
                    'employee_id' => $emp->id,
                    'name' => 'Pinjaman Koperasi',
                    'type' => 'deduction',
                    'amount' => 250000,
                    'is_taxable' => false,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function createAttendanceRecords(): void
    {
        // Attendance tidak dibuat di sini.
        // AttendanceDataSeeder akan membuat data absensi lengkap untuk Jan-Apr 2026
        // dengan pola realistis per karyawan, termasuk cuti, sakit, dan hari libur nasional.
        $this->command->info('Data absensi lengkap akan dibuat oleh AttendanceDataSeeder.');
    }

    private function createPayrollRuns(): void
    {
        $calculator = app(PayrollCalculator::class);
        $months = [
            ['name' => 'Payroll Januari 2026', 'start' => '2026-01-01', 'end' => '2026-01-31'],
            ['name' => 'Payroll Februari 2026', 'start' => '2026-02-01', 'end' => '2026-02-28'],
            ['name' => 'Payroll Maret 2026', 'start' => '2026-03-01', 'end' => '2026-03-31'],
            ['name' => 'Payroll April 2026', 'start' => '2026-04-01', 'end' => '2026-04-30'],
            ['name' => 'Payroll Mei 2026', 'start' => '2026-05-01', 'end' => '2026-05-31'],
        ];

        foreach ($months as $m) {
            $payroll = Payroll::create([
                'name' => $m['name'],
                'period_start' => $m['start'],
                'period_end' => $m['end'],
                'status' => 'draft',
                'total_employees' => count($this->employees),
            ]);

            $totals = ['gross' => 0, 'deductions' => 0, 'net' => 0];

            foreach ($this->employees as $emp) {
                $result = $calculator->calculateForEmployee($emp);

                // Add monthly bonus for January (THR-like)
                $bonusTotal = $result->bonusesTotal;
                if ($m['name'] === 'Payroll Januari 2026') {
                    $bonusTotal += $emp->base_salary * 0.5; // half month bonus
                }

                // Recalculate with bonus
                $netSalary = $result->grossSalary + $bonusTotal
                    - ($result->bpjsKesehatanEmployee + $result->bpjsTkJhtEmployee
                        + $result->bpjsTkJpEmployee + $result->pph21 + $result->deductionsTotal);

                $item = PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $emp->id,
                    'gross_salary' => $result->grossSalary + $bonusTotal,
                    'bpjs_kesehatan_company' => $result->bpjsKesehatanCompany,
                    'bpjs_kesehatan_employee' => $result->bpjsKesehatanEmployee,
                    'bpjs_tk_jht_company' => $result->bpjsTkJhtCompany,
                    'bpjs_tk_jht_employee' => $result->bpjsTkJhtEmployee,
                    'bpjs_tk_jp_company' => $result->bpjsTkJpCompany,
                    'bpjs_tk_jp_employee' => $result->bpjsTkJpEmployee,
                    'bpjs_tk_jkk' => $result->bpjsTkJkk,
                    'bpjs_tk_jkm' => $result->bpjsTkJkm,
                    'pph21' => $result->pph21,
                    'allowances_total' => $result->allowancesTotal,
                    'deductions_total' => $result->deductionsTotal,
                    'bonuses_total' => $bonusTotal,
                    'overtime_pay' => $result->overtimePay,
                    'net_salary' => $netSalary,
                    'calculation_details' => $result->details,
                ]);

                $totals['gross'] += $item->gross_salary;
                $totals['deductions'] += $item->bpjs_kesehatan_employee + $item->bpjs_tk_jht_employee
                    + $item->bpjs_tk_jp_employee + $item->pph21 + $item->deductions_total;
                $totals['net'] += $item->net_salary;
            }

            $payroll->update([
                'status' => 'paid',
                'total_gross' => $totals['gross'],
                'total_deductions' => $totals['deductions'],
                'total_net' => $totals['net'],
                'processed_by' => 1,
                'approved_by' => 1,
                'processed_at' => Carbon::parse($m['end'])->addDay()->setTime(9, 0, 0),
                'approved_at' => Carbon::parse($m['end'])->addDay()->setTime(14, 0, 0),
                'paid_at' => Carbon::parse($m['end'])->addDays(2)->setTime(10, 0, 0),
            ]);
        }
    }

    private function generateNik(): string
    {
        $nik = '3529' . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT)
            . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        return $nik;
    }

    private function generateNpwp(): string
    {
        return str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT)
            . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    private function generateBpjsNo(): string
    {
        return str_pad(random_int(0, 9999999), 7, '0', STR_PAD_LEFT)
            . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function generateBankAccount(): string
    {
        return str_pad(random_int(0, 99999999999), 12, '0', STR_PAD_LEFT);
    }
}
