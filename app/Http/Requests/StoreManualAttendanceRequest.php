<?php

namespace App\Http\Requests;

use App\Enums\ManualAttendanceRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->employee()->exists() === true;
    }

    public function rules(): array
    {
        $maxBackfillDays = (int) config('attendance.manual_request.max_backfill_days', 30);
        $earliest = now()->subDays($maxBackfillDays)->toDateString();

        return [
            'request_type' => ['required', Rule::enum(ManualAttendanceRequestType::class)],
            'requested_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:' . $earliest],
            'requested_time' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:2048', 'extensions:jpg,jpeg,png,pdf,webp'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_type.required' => 'Tipe pengajuan wajib dipilih.',
            'request_type.Illuminate\Validation\Rules\Enum' => 'Tipe pengajuan tidak valid.',
            'requested_date.before_or_equal' => 'Tanggal pengajuan tidak boleh melewati hari ini.',
            'requested_date.after_or_equal' => 'Tanggal pengajuan terlalu lama; klaim retroaktif dibatasi.',
            'requested_time.date_format' => 'Format jam pengajuan harus HH:MM.',
            'reason.required' => 'Alasan kendala wajib diisi.',
            'reason.min' => 'Alasan kendala minimal 10 karakter.',
            'evidence.mimes' => 'Bukti harus berupa JPG, PNG, WEBP, atau PDF.',
            'evidence.max' => 'Ukuran bukti maksimal 2 MB.',
        ];
    }
}
