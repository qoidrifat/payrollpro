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
        ];
    }

    public function updateCompanySettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value, 'company');
        }
    }

    public function getByGroup(string $group): array
    {
        return $this->repository->getByGroup($group);
    }
}