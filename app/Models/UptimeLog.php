<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UptimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_service_id', 'status', 'checked_at', 'response_time_ms',
    ];

    protected function casts(): array
    {
        return [
            'status'           => ServiceStatus::class,
            'checked_at'       => 'datetime',
            'response_time_ms' => 'integer',
        ];
    }

    public function service()
    {
        return $this->belongsTo(SystemService::class, 'system_service_id');
    }

    public function scopeForPeriod($query, string $start, string $end)
    {
        return $query->whereBetween('checked_at', [$start, $end]);
    }
}
