<?php

namespace App\Actions\Approval;

use App\Models\Approval;
use App\Models\User;
use App\Services\ApprovalService;

class RejectApproval
{
    public function __construct(
        private readonly ApprovalService $approvalService,
    ) {}

    /**
     * Reject an approval step.
     */
    public function execute(Approval $approval, User $user, string $comments): void
    {
        $this->approvalService->reject($approval, $user, $comments);
    }
}
