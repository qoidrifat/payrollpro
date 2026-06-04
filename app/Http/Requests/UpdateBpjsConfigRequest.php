<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBpjsConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'configs' => ['required', 'array'],
            'configs.*.id' => ['nullable', 'integer', 'exists:bpjs_configs,id'],
            'configs.*.name' => ['required', 'string', 'max:255'],
            'configs.*.type' => ['required', 'string', 'in:kesehatan,tk_jht,tk_jp,tk_jkk,tk_jkm'],
            'configs.*.payer' => ['required', 'string', 'in:company,employee'],
            'configs.*.rate_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'configs.*.salary_cap' => ['nullable', 'numeric', 'min:0'],
            'configs.*.applicable_year' => ['required', 'integer', 'min:2024', 'max:2035'],
            'configs.*.description' => ['nullable', 'string', 'max:500'],
            'configs.*.is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'configs.required' => 'Data konfigurasi BPJS wajib dikirim.',
            'configs.*.name.required' => 'Nama program BPJS wajib diisi.',
            'configs.*.type.required' => 'Tipe BPJS wajib diisi.',
            'configs.*.payer.required' => 'Pembayar (company/employee) wajib diisi.',
            'configs.*.rate_percentage.required' => 'Persentase tarif wajib diisi.',
            'configs.*.rate_percentage.numeric' => 'Persentase tarif harus berupa angka.',
            'configs.*.rate_percentage.max' => 'Persentase tarif maksimal 100%.',
            'configs.*.applicable_year.required' => 'Tahun berlaku wajib diisi.',
        ];
    }
}
