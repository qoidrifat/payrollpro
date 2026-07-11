<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewManualAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'HR']) === true;
    }

    public function rules(): array
    {
        if ($this->routeIs('manual-attendance-requests.reject')) {
            return [
                'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
            ];
        }

        return [
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal 5 karakter.',
        ];
    }
}
