<?php

namespace App\Models;

use App\Enums\SalaryComponentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'name', 'type', 'amount',
        'is_taxable', 'is_active',
        'effective_from', 'effective_until', 'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => SalaryComponentType::class,
            'amount' => 'decimal:2',
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeAllowances($query)
    {
        return $query->where('type', 'allowance');
    }

    public function scopeDeductions($query)
    {
        return $query->where('type', 'deduction');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
