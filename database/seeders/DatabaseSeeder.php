<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Urutan seeding:
            // 1. Default company context
            CompanySeeder::class,
            // 2. Admin user & role/permission
            AdminUserSeeder::class,
            // 3. Konfigurasi BPJS, PPh21, dan PTKP (untuk kalkulasi payroll)
            BpjsConfigSeeder::class,
            Pph21ConfigSeeder::class,
            PtkpConfigSeeder::class,
            // 4. Data dummy: karyawan, komponen gaji, payroll
            DummyDataSeeder::class,
            // 5. Relasi user ke employee
            EmployeeUserSeeder::class,
            // 6. Data absensi lengkap Jan-Jun 2026
            AttendanceDataSeeder::class,
        ]);
    }
}
