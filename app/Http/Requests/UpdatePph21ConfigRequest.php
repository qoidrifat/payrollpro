<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePph21ConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'brackets' => ['required', 'array'],
            'brackets.*.id' => ['nullable', 'integer', 'exists:pph21_configs,id'],
            'brackets.*.income_bracket_start' => ['required', 'numeric', 'min:0'],
            'brackets.*.income_bracket_end' => ['nullable', 'numeric', 'min:0'],
            'brackets.*.rate_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'brackets.*.applicable_year' => ['required', 'integer', 'min:2024', 'max:2035'],
            'brackets.*.is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'brackets.required' => 'Data bracket PPh21 wajib dikirim.',
            'brackets.*.income_bracket_start.required' => 'Batas bawah bracket wajib diisi.',
            'brackets.*.rate_percentage.required' => 'Persentase tarif wajib diisi.',
            'brackets.*.applicable_year.required' => 'Tahun berlaku wajib diisi.',
        ];
    }
}
