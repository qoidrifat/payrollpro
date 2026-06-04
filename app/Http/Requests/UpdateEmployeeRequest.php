<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id ?? $this->route('employee');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'nik' => ['required', 'digits:16', 'unique:employees,nik,' . $employeeId],
            'npwp' => ['nullable', 'digits:16'],
            'gender' => ['required', 'in:male,female'],
            'position' => ['required', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'join_date' => ['required', 'date'],
            'employment_status' => ['required', 'in:permanent,contract,probation,intern'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:20'],
            'bank_account_name' => ['nullable', 'string', 'max:100'],
            'bpjs_kesehatan' => ['nullable', 'string', 'max:13'],
            'bpjs_ketenagakerjaan' => ['nullable', 'string', 'max:13'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'resign_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'nik.unique' => 'NIK sudah terdaftar dalam sistem.',
            'nik.digits' => 'NIK harus 16 digit.',
            'base_salary.min' => 'Gaji pokok tidak boleh negatif.',
        ];
    }
}
