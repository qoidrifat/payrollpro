<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_service_id', 'metric_type', 'value', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'value'       => 'decimal:4',
            'recorded_at' => 'datetime',
        ];
    }

    public function service()
    {
        return $this->belongsTo(SystemService::class, 'system_service_id');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('metric_type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->where('recorded_at', '>=', now()->subHours(24));
    }
}
