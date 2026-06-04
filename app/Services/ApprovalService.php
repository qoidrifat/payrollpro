<?php

namespace App\Services;

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Events\ApprovalCompleted;
use App\Models\Approval;
use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use Illuminate\Database\Eloquent\Model;

class ApprovalService
{
    /**
     * Initialize the approval chain for an approve-able model.
     * Creates one pending Approval record per required level.
     */
    public function initializeChain(Model $approvable): void
    {
        $levels = method_exists($approvable, 'getApprovalLevels')
            ? $approvable->getApprovalLevels()
            : ApprovalLevel::cases();

        foreach ($levels as $level) {
            Approval::create([
                'approvable_type' => get_class($approvable),
                'approvable_id'   => $approvable->getKey(),
                'level'           => $level,
                'status'          => ApprovalStatus::Pending,
            ]);
        }

        // Notify users at the first level
        $this->notifyLevelUsers($approvable, ApprovalLevel::first());
    }

    /**
     * Approve a specific approval level.
     */
    public function approve(Approval $approval, User $approver, ?string $comments = null): void
    {
        if ($approval->status !== ApprovalStatus::Pending) {
            throw new \RuntimeException("Approval #{$approval->id} is not pending.");
        }

        $approval->markApproved($approver->id, $comments);

        AuditService::approval('approved', $approval->approvable_type, $approval->approvable_id, [
            'level'        => $approval->level->label(),
            'approved_by'  => $approver->id,
            'comments'     => $comments,
        ]);

        $nextLevel = $approval->level->next();

        if ($nextLevel) {
            $this->notifyLevelUsers($approval->approvable, $nextLevel);
        } else {
            // All levels completed
            ApprovalCompleted::dispatch(
                approvableType: $approval->approvable_type,
                approvableId: $approval->approvable_id,
                newStatus: 'approved',
                approvedBy: $approver->id,
            );
        }
    }

    /**
     * Reject a specific approval level. This terminates the chain.
     */
    public function reject(Approval $approval, User $approver, string $comments): void
    {
        if ($approval->status !== ApprovalStatus::Pending) {
            throw new \RuntimeException("Approval #{$approval->id} is not pending.");
        }

        $approval->markRejected($approver->id, $comments);

        AuditService::approval('rejected', $approval->approvable_type, $approval->approvable_id, [
            'level'        => $approval->level->label(),
            'rejected_by'  => $approver->id,
            'comments'     => $comments,
        ]);

        ApprovalCompleted::dispatch(
            approvableType: $approval->approvable_type,
            approvableId: $approval->approvable_id,
            newStatus: 'rejected',
            approvedBy: $approver->id,
        );
    }

    /**
     * Cancel all pending approvals in the chain.
     */
    public function cancelChain(Model $approvable): void
    {
        $approvable->pendingApprovals()->update([
            'status' => ApprovalStatus::Cancelled,
        ]);
    }

    /**
     * Notify users assigned to a specific approval level.
     */
    private function notifyLevelUsers(Model $approvable, ApprovalLevel $level): void
    {
        $roleMap = [
            ApprovalLevel::Manager->value => 'HR',        // Managers are HR role for now
            ApprovalLevel::HR->value      => 'HR',
            ApprovalLevel::Finance->value => 'Admin',     // Finance is Admin role
        ];

        $role = $roleMap[$level->value] ?? 'Admin';
        $users = User::role($role)->get();

        $approvableName = $approvable->name ?? get_class($approvable) . ' #' . $approvable->getKey();

        foreach ($users as $user) {
            $user->notify(new ApprovalRequestedNotification(
                approvableType: class_basename($approvable),
                approvableId: $approvable->getKey(),
                approvableName: $approvableName,
                requiredLevel: $level->label(),
            ));
        }
    }
}
