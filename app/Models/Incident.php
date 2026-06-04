<?php

namespace App\Models;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'severity', 'status',
        'affected_services', 'started_at', 'resolved_at',
        'resolution_notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'severity'         => IncidentSeverity::class,
            'status'           => IncidentStatus::class,
            'affected_services' => 'array',
            'started_at'       => 'datetime',
            'resolved_at'      => 'datetime',
        ];
    }

    public function services()
    {
        return $this->belongsToMany(SystemService::class, 'incident_service')
            ->withTimestamps();
    }

    public function updates()
    {
        return $this->hasMany(IncidentUpdate::class)->latest();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', IncidentStatus::Resolved);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', IncidentStatus::Resolved);
    }

    public function scopeBySeverity($query, IncidentSeverity $severity)
    {
        return $query->where('severity', $severity);
    }

    public function resolve(string $notes = null): void
    {
        $this->update([
            'status'           => IncidentStatus::Resolved,
            'resolved_at'      => now(),
            'resolution_notes' => $notes,
        ]);
    }

    public function durationInMinutes(): int
    {
        $end = $this->resolved_at ?? now();
        return (int) $this->started_at->diffInMinutes($end);
    }
}
