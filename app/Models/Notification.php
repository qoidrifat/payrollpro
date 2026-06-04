<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * Using 'user_notifications' table to avoid conflict with
     * Laravel's native notifications system (for Database channel).
     */
    protected $table = 'user_notifications';

    protected $fillable = [
        'user_id', 'type', 'title', 'body',
        'data', 'read_at', 'channel',
    ];

    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeOfType($query, NotificationType $type)
    {
        return $query->where('type', $type);
    }

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }
}
