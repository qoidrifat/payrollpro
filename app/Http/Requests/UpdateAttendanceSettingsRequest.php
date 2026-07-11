<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAttendanceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'HR']) === true;
    }

    public function rules(): array
    {
        return [
            'operational_start' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'operational_end' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'qr_refresh_interval' => ['required', 'integer', 'min:30', 'max:3600'],
            'timezone' => ['required', 'string', 'max:50', Rule::in(timezone_identifiers_list())],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $start = $this->input('operational_start');
            $end = $this->input('operational_end');

            if ($start && $end && $start >= $end) {
                $validator->errors()->add(
                    'operational_end',
                    'Jam selesai operasional harus lebih besar dari jam mulai.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'operational_start.required' => 'Jam mulai operasional wajib diisi.',
            'operational_start.regex' => 'Format jam mulai harus HH:MM (contoh: 06:30).',
            'operational_end.required' => 'Jam selesai operasional wajib diisi.',
            'operational_end.regex' => 'Format jam selesai harus HH:MM (contoh: 17:00).',
            'timezone.in' => 'Zona waktu tidak valid.',
            'qr_refresh_interval.integer' => 'Interval refresh QR harus berupa angka.',
            'qr_refresh_interval.min' => 'Interval refresh QR minimal 30 detik.',
            'qr_refresh_interval.max' => 'Interval refresh QR maksimal 3600 detik.',
        ];
    }
}
