<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'leave_type' => ['required', 'string', 'in:annual,sick,personal,maternity,paternity,marriage,bereavement,unpaid'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'Tanggal mulai cuti tidak boleh sebelum hari ini.',
            'end_date.after_or_equal' => 'Tanggal selesai cuti harus setelah atau sama dengan tanggal mulai.',
            'reason.required' => 'Alasan cuti wajib diisi.',
            'leave_type.in' => 'Jenis cuti tidak valid.',
        ];
    }
}
