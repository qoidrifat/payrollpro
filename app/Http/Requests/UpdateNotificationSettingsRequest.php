<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'email_notifications' => ['boolean'],
            'in_app_notifications' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email_notifications.boolean' => 'Pengaturan notifikasi email harus valid.',
            'in_app_notifications.boolean' => 'Pengaturan notifikasi in-app harus valid.',
        ];
    }
}
