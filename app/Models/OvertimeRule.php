<?php

namespace App\Models;

use App\Enums\OvertimeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'overtime_type', 'name',
        'multiplier_first_hour', 'multiplier_subsequent_hours',
        'max_hours_per_day', 'max_hours_per_week',
        'requires_approval', 'applicable_year',
        'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'overtime_type'               => OvertimeType::class,
            'multiplier_first_hour'       => 'decimal:2',
            'multiplier_subsequent_hours' => 'decimal:2',
            'max_hours_per_day'           => 'integer',
            'max_hours_per_week'          => 'integer',
            'requires_approval'           => 'boolean',
            'is_active'                   => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, OvertimeType $type)
    {
        return $query->where('overtime_type', $type);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('applicable_year', $year);
    }
}
