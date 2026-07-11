<?php

namespace App\Services;

use App\Repositories\SettingRepository;

class SettingService
{
    public function __construct(
        private readonly SettingRepository $repository
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->repository->get($key, $default);
    }

    public function set(string $key, mixed $value, string $group = 'general', string $type = 'text'): void
    {
        $this->repository->set($key, $value, $group, $type);
    }

    public function getCompanySettings(): array
    {
        return [
            'company_name' => $this->get('company_name', ''),
            'company_address' => $this->get('company_address', ''),
            'company_phone' => $this->get('company_phone', ''),
            'company_npwp' => $this->get('company_npwp', ''),
            'company_email' => $this->get('company_email', ''),
        ];
    }

    public function updateCompanySettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value, 'company');
        }
    }

    public function getAttendanceSettings(): array
    {
        return [
            'operational_start' => $this->get('attendance_operational_start', config('attendance.operational_hours.start', '06:30')),
            'operational_end' => $this->get('attendance_operational_end', config('attendance.operational_hours.end', '17:00')),
            'qr_refresh_interval' => (int) $this->get('attendance_qr_refresh_interval', config('attendance.qr.refresh_interval_seconds', 300)),
            'timezone' => $this->get('attendance_timezone', config('attendance.operational_hours.timezone', 'Asia/Jakarta')),
        ];
    }

    public function updateAttendanceSettings(array $settings): void
    {
        $mapping = [
            'operational_start' => 'attendance_operational_start',
            'operational_end' => 'attendance_operational_end',
            'qr_refresh_interval' => 'attendance_qr_refresh_interval',
            'timezone' => 'attendance_timezone',
        ];

        foreach ($settings as $key => $value) {
            if (isset($mapping[$key])) {
                $this->set($mapping[$key], $value, 'attendance');
            }
        }
    }

    public function getNotificationSettings(?int $userId = null): array
    {
        // Personal preferences (per user) take priority over global defaults
        if ($userId) {
            return [
                'email_notifications' => (bool) ($this->get("notification_email_{$userId}") ?? $this->get('notification_email', true)),
                'in_app_notifications' => (bool) ($this->get("notification_in_app_{$userId}") ?? $this->get('notification_in_app', true)),
            ];
        }

        return [
            'email_notifications' => (bool) $this->get('notification_email', true),
            'in_app_notifications' => (bool) $this->get('notification_in_app', true),
        ];
    }

    public function updateNotificationSettings(array $settings, ?int $userId = null): void
    {
        foreach ($settings as $key => $value) {
            if ($userId) {
                $this->repository->set("notification_{$key}_{$userId}", $value, 'notification_user', 'boolean');
            } else {
                $this->set($key, $value, 'notification');
            }
        }
    }

    public function getByGroup(string $group): array
    {
        return $this->repository->getByGroup($group);
    }

    /**
     * Get all available setting groups for role-based settings.
     */
    public function getAvailableGroups(): array
    {
        return [
            'company' => 'Profil Perusahaan',
            'attendance' => 'Operasional Absensi',
            'payroll' => 'Penggajian',
            'notification' => 'Notifikasi',
        ];
    }
}
