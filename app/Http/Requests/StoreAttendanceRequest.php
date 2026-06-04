<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date'        => ['required', 'date', 'date_format:Y-m-d'],
            'clock_in'    => ['nullable', 'date_format:H:i'],
            'clock_out'   => ['nullable', 'date_format:H:i'],
            'status'      => ['required', Rule::in(['present', 'absent', 'late', 'half_day', 'sick', 'leave'])],
            'type'        => ['required', Rule::in(['wfo', 'wfh', 'remote'])],
            'notes'       => ['nullable', 'string', 'max:500'],
            'latitude'    => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'   => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}