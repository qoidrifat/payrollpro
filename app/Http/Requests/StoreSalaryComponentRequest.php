<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalaryComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id ?? $this->route('employee');

        return [
            'base_salary' => ['required', 'numeric', 'min:0'],
            'components' => ['nullable', 'array'],
            'components.*.id' => [
                'nullable',
                'integer',
                Rule::exists('salary_components', 'id')
                    ->where(fn ($query) => $query->where('employee_id', $employeeId)),
            ],
            'components.*.name' => ['required_with:components', 'string', 'max:255'],
            'components.*.type' => ['required_with:components', 'string', 'in:allowance,deduction,bonus,overtime'],
            'components.*.amount' => ['required_with:components', 'numeric', 'min:0'],
            'components.*.is_taxable' => ['boolean'],
            'components.*.is_active' => ['boolean'],
            'components.*.description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'base_salary.min' => 'Gaji pokok tidak boleh negatif.',
            'components.*.type.in' => 'Tipe komponen harus allowance, deduction, bonus, atau overtime.',
        ];
    }
}
