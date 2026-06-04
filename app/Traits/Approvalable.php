<?php

namespace App\Traits;

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Models\Approval;

/**
 * Trait for models that go through a multi-level approval workflow.
 *
 * Usage: Add to any model (e.g., Payroll) and implement
 * getApprovalLevels() to define the required approval chain.
 */
trait Approvalable
{
    public function approvals()
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function pendingApprovals()
    {
        return $this->morphMany(Approval::class, 'approvable')
            ->where('status', ApprovalStatus::Pending);
    }

    public function currentApproval()
    {
        return $this->morphOne(Approval::class, 'approvable')
            ->where('status', ApprovalStatus::Pending)
            ->orderBy('level');
    }

    /**
     * Levels required for this approve-able item. Override to customize.
     *
     * @return ApprovalLevel[]
     */
    public function getApprovalLevels(): array
    {
        return ApprovalLevel::cases();
    }

    /**
     * Has the approval chain been fully completed?
     */
    public function isFullyApproved(): bool
    {
        $requiredLevels = count($this->getApprovalLevels());
        $approvedCount = $this->approvals()
            ->where('status', ApprovalStatus::Approved)
            ->count();

        return $approvedCount >= $requiredLevels;
    }

    /**
     * Has any level rejected this?
     */
    public function isRejected(): bool
    {
        return $this->approvals()
            ->where('status', ApprovalStatus::Rejected)
            ->exists();
    }

    /**
     * Get the next pending approval level, or null if all done.
     */
    public function nextApprovalLevel(): ?ApprovalLevel
    {
        $completedLevels = $this->approvals()
            ->where('status', ApprovalStatus::Approved)
            ->pluck('level')
            ->map(fn($l) => $l instanceof ApprovalLevel ? $l->value : $l)
            ->toArray();

        foreach ($this->getApprovalLevels() as $level) {
            if (!in_array($level->value, $completedLevels)) {
                return $level;
            }
        }

        return null;
    }
}
