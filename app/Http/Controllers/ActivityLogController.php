<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    /**
     * Display a paginated list of activity logs with filters.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('view-activity-logs');

        $logs = ActivityLog::with('user')
            ->when($request->action, fn($q, $a) => $q->where('action', $a))
            ->when($request->user_id, fn($q, $u) => $q->where('user_id', $u))
            ->when($request->subject_type, fn($q, $s) => $q->where('subject_type', $s))
            ->when($request->date_from, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->date_to, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('ActivityLog/Index', [
            'logs' => $logs,
            'filters' => $request->only(['action', 'user_id', 'subject_type', 'date_from', 'date_to']),
        ]);
    }
}
