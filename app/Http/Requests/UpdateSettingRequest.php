<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['required', 'string', 'max:500'],
            'company_phone' => ['required', 'string', 'max:50'],
            'company_npwp' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Nama perusahaan wajib diisi.',
            'company_address.required' => 'Alamat perusahaan wajib diisi.',
            'company_phone.required' => 'Nomor telepon perusahaan wajib diisi.',
            'company_npwp.required' => 'NPWP perusahaan wajib diisi.',
        ];
    }
}
