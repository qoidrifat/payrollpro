<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'affected_services',
        'scheduled_start', 'scheduled_end',
        'started_at', 'completed_at',
        'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'affected_services' => 'array',
            'scheduled_start'   => 'datetime',
            'scheduled_end'     => 'datetime',
            'started_at'        => 'datetime',
            'completed_at'      => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_start', '>', now());
    }

    public function scopeActive($query)
    {
        return $query->where('scheduled_start', '<=', now())
            ->where(function ($q) {
                $q->whereNull('completed_at')
                  ->orWhere('completed_at', '>', now());
            });
    }

    public function scopePast($query)
    {
        return $query->where('scheduled_end', '<', now());
    }

    public function isActive(): bool
    {
        return $this->scheduled_start->isPast()
            && (!$this->completed_at || $this->completed_at->isFuture());
    }

    public function isUpcoming(): bool
    {
        return $this->scheduled_start->isFuture();
    }

    public function remainingSeconds(): int
    {
        if ($this->isActive()) {
            return max(0, (int) $this->scheduled_end->diffInSeconds(now()));
        }
        if ($this->isUpcoming()) {
            return max(0, (int) $this->scheduled_start->diffInSeconds(now()));
        }
        return 0;
    }
}
