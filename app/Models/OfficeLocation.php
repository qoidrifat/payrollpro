<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeLocation extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id', 'name', 'address',
        'latitude', 'longitude', 'radius_meters',
        'is_active', 'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'latitude'       => 'decimal:8',
            'longitude'      => 'decimal:8',
            'radius_meters'  => 'integer',
            'is_active'      => 'boolean',
            'is_primary'     => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Get the default radius for this location.
     * If office has a configured radius, use it; otherwise default to 100m.
     */
    public function getEffectiveRadius(): int
    {
        return $this->radius_meters > 0 ? $this->radius_meters : 100;
    }
}
