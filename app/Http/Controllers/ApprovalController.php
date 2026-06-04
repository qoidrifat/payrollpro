<?php

namespace App\Http\Controllers;

use App\Actions\Approval\ProcessApproval;
use App\Actions\Approval\RejectApproval;
use App\Http\Requests\ApproveRejectRequest;
use App\Models\Approval;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function __construct(
        private readonly ApprovalService $approvalService,
        private readonly ProcessApproval $approvalProcessAction,
        private readonly RejectApproval $rejectApprovalAction,
    ) {}

    /**
     * List pending approvals for the current user.
     */
    public function index(): Response
    {
        // Show approvals the current user can act on based on their role
        $user = auth()->user();

        $pendingApprovals = Approval::pending()
            ->with('approvable')
            ->when(
                $user->hasRole('HR'),
                fn($q) => $q->whereIn('level', [1, 2]) // HR can do Manager + HR levels
            )
            ->when(
                $user->hasRole('Admin'),
                fn($q) => $q // Admin can see all
            )
            ->when(
                $user->hasRole('Employee'),
                fn($q) => $q->where('level', 1) // Employees can only do Manager level
            )
            ->latest()
            ->paginate(15);

        return Inertia::render('Approvals/Index', [
            'approvals' => $pendingApprovals,
        ]);
    }

    /**
     * Approve an approval step.
     */
    public function approve(ApproveRejectRequest $request, Approval $approval): RedirectResponse
    {
        try {
            $this->approvalProcessAction->execute($approval, auth()->user(), $request->validated()['comments'] ?? null);

            return redirect()->back()
                ->with('success', "Disetujui di tingkat {$approval->level->label()}.");
        } catch (\RuntimeException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Reject an approval step.
     */
    public function reject(ApproveRejectRequest $request, Approval $approval): RedirectResponse
    {
        try {
            $this->rejectApprovalAction->execute($approval, auth()->user(), $request->validated()['comments'] ?? 'Ditolak');

            return redirect()->back()
                ->with('success', "Ditolak di tingkat {$approval->level->label()}.");
        } catch (\RuntimeException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show approval history for an approve-able item.
     */
    public function history(string $type, int $id): Response
    {
        $approvals = Approval::where('approvable_type', $type)
            ->where('approvable_id', $id)
            ->with('approver')
            ->orderBy('level')
            ->get();

        return Inertia::render('Approvals/History', [
            'approvals'         => $approvals,
            'approvable_type'   => $type,
            'approvable_id'     => $id,
        ]);
    }
}
