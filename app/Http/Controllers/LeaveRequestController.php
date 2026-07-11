<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $leaveRequests = LeaveRequest::query()
            ->with(['employee:id,first_name,last_name,position,department', 'approvedBy:id,name'])
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('employee', function ($employeeQuery) use ($search) {
                    $employeeQuery
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return Inertia::render('LeaveRequests/Index', [
            'leaveRequests' => $leaveRequests,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'summary' => [
                'pending' => LeaveRequest::where('status', ApprovalStatus::Pending)->count(),
                'approved' => LeaveRequest::where('status', ApprovalStatus::Approved)->count(),
                'rejected' => LeaveRequest::where('status', ApprovalStatus::Rejected)->count(),
            ],
        ]);
    }

    public function approve(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->ensureNotSelfReview($leaveRequest);

        if ($leaveRequest->status !== ApprovalStatus::Pending) {
            return back()->with('error', 'Pengajuan cuti ini sudah diproses.');
        }

        $leaveRequest->update([
            'status' => ApprovalStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Pengajuan cuti berhasil disetujui.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->ensureNotSelfReview($leaveRequest);

        if ($leaveRequest->status !== ApprovalStatus::Pending) {
            return back()->with('error', 'Pengajuan cuti ini sudah diproses.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $leaveRequest->update([
            'status' => ApprovalStatus::Rejected,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('success', 'Pengajuan cuti berhasil ditolak.');
    }

    /**
     * A reviewer (Admin/HR who is also an employee) must not approve or reject
     * their own leave request. Tenant isolation is already enforced by the
     * BelongsToCompany global scope on route-model binding.
     */
    private function ensureNotSelfReview(LeaveRequest $leaveRequest): void
    {
        $reviewerEmployeeId = auth()->user()?->employee?->id;

        abort_if(
            $reviewerEmployeeId !== null && $reviewerEmployeeId === $leaveRequest->employee_id,
            403,
            'Anda tidak dapat meninjau pengajuan cuti milik Anda sendiri.'
        );
    }
}
