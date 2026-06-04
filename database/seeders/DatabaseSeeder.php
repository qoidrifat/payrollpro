<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Urutan seeding:
            // 1. Admin user & role/permission
            AdminUserSeeder::class,
            // 2. Konfigurasi BPJS, PPh21, dan PTKP (untuk kalkulasi payroll)
            BpjsConfigSeeder::class,
            Pph21ConfigSeeder::class,
            PtkpConfigSeeder::class,
            // 3. Data dummy: karyawan, komponen gaji, payroll
            DummyDataSeeder::class,
            // 4. Relasi user ke employee
            EmployeeUserSeeder::class,
            // 5. Data absensi lengkap Jan-Jun 2026
            AttendanceDataSeeder::class,
        ]);
    }
}
