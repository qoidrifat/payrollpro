<?php

namespace App\Models;

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'approvable_type', 'approvable_id',
        'level', 'status', 'approver_id',
        'comments', 'approved_at', 'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'level' => ApprovalLevel::class,
            'status' => ApprovalStatus::class,
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function approvable()
    {
        return $this->morphTo();
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', ApprovalStatus::Pending);
    }

    public function scopeByLevel($query, ApprovalLevel $level)
    {
        return $query->where('level', $level);
    }

    public function scopeForApprover($query, int $userId)
    {
        return $query->where('approver_id', $userId);
    }

    public function markApproved(int $approverId, ?string $comments = null): void
    {
        $this->update([
            'status'      => ApprovalStatus::Approved,
            'approver_id' => $approverId,
            'comments'    => $comments,
            'approved_at' => now(),
        ]);
    }

    public function markRejected(int $approverId, string $comments): void
    {
        $this->update([
            'status'      => ApprovalStatus::Rejected,
            'approver_id' => $approverId,
            'comments'    => $comments,
            'rejected_at' => now(),
        ]);
    }
}
