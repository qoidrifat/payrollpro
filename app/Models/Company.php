<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'domain', 'database',
        'address', 'phone', 'email', 'npwp',
        'tax_config', 'settings',
        'is_active', 'subscription_plan',
    ];

    protected function casts(): array
    {
        return [
            'tax_config' => 'array',
            'settings'   => 'array',
            'is_active'  => 'boolean',
        ];
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function attendances()
    {
        return $this->hasManyThrough(Attendance::class, Employee::class);
    }

    public function officeLocations()
    {
        return $this->hasMany(OfficeLocation::class);
    }

    /**
     * Get a company-specific setting with fallback to global default.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key]
            ?? app(\App\Services\SettingService::class)->get($key, $default);
    }
}
