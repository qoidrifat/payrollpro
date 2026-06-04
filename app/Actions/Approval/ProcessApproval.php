<?php

namespace App\Actions\Approval;

use App\Models\Approval;
use App\Models\User;
use App\Services\ApprovalService;

class ProcessApproval
{
    public function __construct(
        private readonly ApprovalService $approvalService,
    ) {}

    /**
     * Approve an approval step.
     */
    public function execute(Approval $approval, User $user, ?string $comments = null): void
    {
        $this->approvalService->approve($approval, $user, $comments);
    }
}
