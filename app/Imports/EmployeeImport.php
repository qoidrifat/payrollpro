<?php

namespace App\Imports;

use App\Models\Employee;
use App\Scopes\TenantScope;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EmployeeImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;

    private int $rowCount = 0;

    public function model(array $row)
    {
        $this->rowCount++;

        $companyId = TenantScope::currentCompanyId();
        if (!$companyId && Auth::check()) {
            $companyId = Auth::user()->employee?->company_id;
        }

        return new Employee([
            'company_id'            => $companyId,
            'nik'                   => $row['nik'],
            'npwp'                  => $row['npwp'] ?? null,
            'bpjs_kesehatan'        => $row['bpjs_kesehatan'] ?? null,
            'bpjs_ketenagakerjaan'  => $row['bpjs_ketenagakerjaan'] ?? null,
            'first_name'            => $row['first_name'] ?? $row['nama_depan'],
            'last_name'             => $row['last_name'] ?? $row['nama_belakang'] ?? null,
            'gender'                => $row['gender'] ?? $row['jenis_kelamin'],
            'position'              => $row['position'] ?? $row['posisi'] ?? $row['jabatan'],
            'department'            => $row['department'] ?? $row['departemen'] ?? null,
            'join_date'             => $row['join_date'] ?? $row['tanggal_masuk'],
            'employment_status'     => $row['employment_status'] ?? $row['status_kerja'] ?? 'contract',
            'base_salary'           => (float) ($row['base_salary'] ?? $row['gaji_pokok'] ?? 0),
            'bank_name'             => $row['bank_name'] ?? $row['bank'] ?? null,
            'bank_account_number'   => $row['bank_account_number'] ?? $row['no_rekening'] ?? null,
            'bank_account_name'     => $row['bank_account_name'] ?? $row['nama_rekening'] ?? null,
            'phone'                 => $row['phone'] ?? $row['telepon'] ?? null,
            'address'               => $row['address'] ?? $row['alamat'] ?? null,
            'city'                  => $row['city'] ?? $row['kota'] ?? null,
            'province'              => $row['province'] ?? $row['provinsi'] ?? null,
            'postal_code'           => $row['postal_code'] ?? $row['kode_pos'] ?? null,
            'emergency_contact_name'  => $row['emergency_contact_name'] ?? $row['kontak_darurat'] ?? null,
            'emergency_contact_phone' => $row['emergency_contact_phone'] ?? $row['telepon_darurat'] ?? null,
            'notes'                 => $row['notes'] ?? $row['catatan'] ?? null,
            'is_active'             => true,
        ]);
    }

    /**
     * Get the number of successfully imported rows.
     */
    public function getImportedCount(): int
    {
        return $this->rowCount;
    }

    public function rules(): array
    {
        return [
            'nik'                         => ['required', 'digits:16', 'unique:employees,nik'],
            'first_name'                  => ['required', 'string', 'max:100'],
            'nama_depan'                  => ['required_without:first_name', 'string', 'max:100'],
            'gender'                      => ['required', 'in:male,female'],
            'jenis_kelamin'               => ['required_without:gender', 'in:male,female'],
            'position'                    => ['required', 'string', 'max:100'],
            'posisi'                      => ['required_without:position', 'string', 'max:100'],
            'jabatan'                     => ['required_without:position', 'string', 'max:100'],
            'join_date'                   => ['required', 'date'],
            'tanggal_masuk'               => ['required_without:join_date', 'date'],
            'employment_status'           => ['required', Rule::in(['permanent', 'contract', 'probation', 'intern'])],
            'status_kerja'                => ['required_without:employment_status', Rule::in(['permanent', 'contract', 'probation', 'intern'])],
            'base_salary'                 => ['required', 'numeric', 'min:0'],
            'gaji_pokok'                  => ['required_without:base_salary', 'numeric', 'min:0'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nik.required'       => 'NIK wajib diisi.',
            'nik.digits'         => 'NIK harus 16 digit.',
            'nik.unique'         => 'NIK sudah terdaftar.',
            'first_name.required' => 'Nama depan wajib diisi.',
            'gender.in'          => 'Jenis kelamin harus male atau female.',
            'employment_status.in' => 'Status kerja harus permanent, contract, probation, atau intern.',
            'base_salary.min'    => 'Gaji pokok tidak boleh negatif.',
        ];
    }
}
