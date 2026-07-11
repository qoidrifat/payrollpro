<?php

namespace App\Http\Controllers;

use App\Enums\ManualAttendanceRequestStatus;
use App\Http\Requests\ReviewManualAttendanceRequest;
use App\Http\Requests\StoreManualAttendanceRequest;
use App\Models\ManualAttendanceRequest;
use App\Scopes\TenantScope;
use App\Services\ManualAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ManualAttendanceRequestController extends Controller
{
    public function __construct(
        private readonly ManualAttendanceService $manualAttendanceService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ManualAttendanceRequest::class);

        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $manualRequests = $this->queryForIndex($status, $search)
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString()
            ->through(fn (ManualAttendanceRequest $manualRequest) => $this->serialize($manualRequest));

        return Inertia::render('ManualAttendanceRequests/Index', [
            'manualRequests' => $manualRequests,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'summary' => [
                'pending' => ManualAttendanceRequest::where('status', ManualAttendanceRequestStatus::Pending)->count(),
                'approved' => ManualAttendanceRequest::where('status', ManualAttendanceRequestStatus::Approved)->count(),
                'rejected' => ManualAttendanceRequest::where('status', ManualAttendanceRequestStatus::Rejected)->count(),
            ],
        ]);
    }

    public function store(StoreManualAttendanceRequest $request): RedirectResponse
    {
        Gate::authorize('create', ManualAttendanceRequest::class);

        $manualRequest = $this->manualAttendanceService->create(
            $request->user()->employee,
            $request->validated(),
            $request->file('evidence'),
        );

        $typeLabel = $manualRequest->request_type->value === 'manual_clock_in'
            ? 'Manual Clock-In Menunggu Verifikasi'
            : 'Manual Clock-Out Menunggu Verifikasi';

        return back()->with('success', "{$typeLabel}. Pengajuan Anda sudah dikirim ke HR/Admin.");
    }

    public function approve(ReviewManualAttendanceRequest $request, ManualAttendanceRequest $manualAttendanceRequest): RedirectResponse
    {
        Gate::authorize('review', $manualAttendanceRequest);

        $this->manualAttendanceService->approve($manualAttendanceRequest, $request->user());

        return back()->with('success', 'Pengajuan absen manual berhasil disetujui.');
    }

    public function reject(ReviewManualAttendanceRequest $request, ManualAttendanceRequest $manualAttendanceRequest): RedirectResponse
    {
        Gate::authorize('review', $manualAttendanceRequest);

        $this->manualAttendanceService->reject(
            $manualAttendanceRequest,
            $request->user(),
            $request->validated('rejection_reason'),
        );

        return back()->with('success', 'Pengajuan absen manual berhasil ditolak.');
    }

    public function latestForEmployee(Request $request): JsonResponse
    {
        $employee = $request->user()?->employee;

        abort_if(! $employee, 403);

        $latest = ManualAttendanceRequest::with(['reviewer:id,name', 'attendance:id,clock_in,clock_out,source'])
            ->where('employee_id', $employee->id)
            ->whereDate('requested_date', now()->toDateString())
            ->latest()
            ->get()
            ->map(fn (ManualAttendanceRequest $manualRequest) => $this->serialize($manualRequest))
            ->values();

        return response()->json([
            'manualRequests' => $latest,
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ManualAttendanceRequest::class);

        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();
        $companyId = TenantScope::currentCompanyId() ?? 'global';
        $cacheKey = 'manual-attendance-requests:poll:' . $companyId . ':' . md5($status . '|' . $search);

        return response()->json(Cache::remember($cacheKey, 65, function () use ($status, $search) {
            $latestUpdatedAt = $this->queryForIndex($status, $search)->max('updated_at');

            return [
                'latestUpdatedAt' => $latestUpdatedAt ? Carbon::parse($latestUpdatedAt)->toIso8601String() : null,
                'pendingCount' => ManualAttendanceRequest::where('status', ManualAttendanceRequestStatus::Pending)->count(),
            ];
        }));
    }

    private function queryForIndex(string $status = '', string $search = '')
    {
        return ManualAttendanceRequest::query()
            ->with(['employee:id,first_name,last_name,position,department', 'reviewer:id,name', 'attendance:id,clock_in,clock_out,source'])
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('employee', function ($employeeQuery) use ($search) {
                    $employeeQuery
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%");
                });
            });
    }

    private function serialize(ManualAttendanceRequest $manualRequest): array
    {
        return [
            'id' => $manualRequest->id,
            'employee' => $manualRequest->employee ? [
                'id' => $manualRequest->employee->id,
                'first_name' => $manualRequest->employee->first_name,
                'last_name' => $manualRequest->employee->last_name,
                'full_name' => $manualRequest->employee->full_name,
                'position' => $manualRequest->employee->position,
                'department' => $manualRequest->employee->department,
            ] : null,
            'attendance_id' => $manualRequest->attendance_id,
            'request_type' => $manualRequest->request_type->value,
            'request_type_label' => $manualRequest->request_type->value === 'manual_clock_in' ? 'Manual Clock-In' : 'Manual Clock-Out',
            'requested_date' => $manualRequest->requested_date?->toDateString(),
            'requested_date_label' => $manualRequest->requested_date?->format('d M Y'),
            'requested_time' => substr((string) $manualRequest->requested_time, 0, 5),
            'reason' => $manualRequest->reason,
            'evidence_path' => $manualRequest->evidence_path,
            'status' => $manualRequest->status->value,
            'reviewer' => $manualRequest->reviewer ? [
                'id' => $manualRequest->reviewer->id,
                'name' => $manualRequest->reviewer->name,
            ] : null,
            'reviewed_at' => $manualRequest->reviewed_at?->toIso8601String(),
            'rejection_reason' => $manualRequest->rejection_reason,
            'source' => $manualRequest->source,
            'created_at' => $manualRequest->created_at?->toIso8601String(),
            'updated_at' => $manualRequest->updated_at?->toIso8601String(),
        ];
    }
}
