<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BpjsConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'payer', 'rate_percentage',
        'salary_cap', 'applicable_year', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate_percentage' => 'decimal:2',
            'salary_cap' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('applicable_year', $year);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
