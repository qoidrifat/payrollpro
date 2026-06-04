<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollStatus;
use App\Models\Payroll;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;

class ApprovePayroll
{
    public function __construct(
        private readonly ApprovalService $approvalService,
    ) {}

    /**
     * Approve a payroll at the current user's approval level.
     * When all levels are complete, payroll status is set to 'approved'.
     */
    public function execute(Payroll $payroll, User $user): array
    {
        Gate::authorize('approve', $payroll);

        if ($payroll->status !== PayrollStatus::Processed) {
            throw new \RuntimeException('Hanya penggajian yang sudah diproses yang dapat disetujui.');
        }

        // Initialize approval chain if this is the first approval attempt
        if (!$payroll->currentApproval && !$payroll->approvals()->exists()) {
            $this->approvalService->initializeChain($payroll);
        }

        $currentApproval = $payroll->currentApproval()->first();

        if (!$currentApproval) {
            throw new \RuntimeException('Tidak ada tahap persetujuan tertunda. Penggajian mungkin sudah disetujui sepenuhnya.');
        }

        $this->approvalService->approve($currentApproval, $user);

        // If fully approved, update payroll status
        if ($payroll->fresh()->isFullyApproved()) {
            $payroll->update([
                'status'      => PayrollStatus::Approved,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            AuditService::payrollChange('approved', $payroll->id, "Payroll '{$payroll->name}' fully approved.");

            return [
                'fully_approved' => true,
                'message'        => 'Penggajian disetujui sepenuhnya! Semua tingkat persetujuan selesai.',
            ];
        }

        $nextLevel = $payroll->fresh()->nextApprovalLevel();

        $message = "Disetujui di tingkat {$currentApproval->level->label()}."
            . ($nextLevel ? " Selanjutnya: persetujuan {$nextLevel->label()} diperlukan." : '');

        return [
            'fully_approved' => false,
            'next_level'     => $nextLevel?->label(),
            'message'        => $message,
        ];
    }
}
