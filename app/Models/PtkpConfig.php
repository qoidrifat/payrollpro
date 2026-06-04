<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PTKP (Penghasilan Tidak Kena Pajak) configuration.
 *
 * Stores annual non-taxable income thresholds per category (TK/0, K/1, etc.)
 * for each applicable year, supporting tax versioning and compliance updates.
 */
class PtkpConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'category', 'description', 'annual_amount',
        'applicable_year', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'annual_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'applicable_year' => 'integer',
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

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
