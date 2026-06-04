<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemService extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'category',
        'status', 'response_time_ms', 'uptime_percentage',
        'is_public', 'last_checked_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status'           => ServiceStatus::class,
            'response_time_ms' => 'integer',
            'uptime_percentage' => 'decimal:2',
            'is_public'        => 'boolean',
            'last_checked_at'  => 'datetime',
            'sort_order'       => 'integer',
        ];
    }

    public function incidents()
    {
        return $this->belongsToMany(Incident::class, 'incident_service')
            ->withTimestamps();
    }

    public function metrics()
    {
        return $this->hasMany(ServiceMetric::class);
    }

    public function uptimeLogs()
    {
        return $this->hasMany(UptimeLog::class);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeOperational($query)
    {
        return $query->where('status', ServiceStatus::Operational);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Calculate current uptime percentage based on last 90 days.
     */
    public function calculateUptime(): float
    {
        $total = $this->uptimeLogs()->where('checked_at', '>=', now()->subDays(90))->count();

        if ($total === 0) return 100.0;

        $operational = $this->uptimeLogs()
            ->where('checked_at', '>=', now()->subDays(90))
            ->where('status', ServiceStatus::Operational)
            ->count();

        return round(($operational / $total) * 100, 2);
    }
}
