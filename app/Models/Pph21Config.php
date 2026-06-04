<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pph21Config extends Model
{
    use HasFactory;

    protected $fillable = [
        'income_bracket_start', 'income_bracket_end',
        'rate_percentage', 'applicable_year', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'income_bracket_start' => 'decimal:2',
            'income_bracket_end' => 'decimal:2',
            'rate_percentage' => 'decimal:3',
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
}
