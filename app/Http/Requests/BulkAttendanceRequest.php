<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['required', 'integer', 'exists:employees,id'],
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(['present', 'absent', 'late', 'half_day', 'sick', 'leave'])],
            'type' => ['required', Rule::in(['wfo', 'wfh', 'remote'])],
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_ids.required' => 'Pilih minimal satu karyawan.',
            'employee_ids.*.exists' => 'Karyawan tidak ditemukan.',
            'date.date_format' => 'Format tanggal harus Y-m-d.',
            'status.in' => 'Status absensi tidak valid.',
            'type.in' => 'Tipe absensi tidak valid.',
        ];
    }
}
