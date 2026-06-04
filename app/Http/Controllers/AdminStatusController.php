<?php

namespace App\Http\Controllers;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\ServiceStatus;
use App\Events\ServiceStatusChanged;
use App\Models\Incident;
use App\Models\MaintenanceSchedule;
use App\Models\SystemService;
use App\Services\IncidentService;
use App\Services\StatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdminStatusController extends Controller
{
    public function __construct(
        private readonly StatusService $statusService,
        private readonly IncidentService $incidentService,
    ) {}

    /**
     * Admin dashboard for system status management.
     */
    public function index(): Response
    {
        Gate::authorize('manage', SystemService::class);

        $hasTable = \Illuminate\Support\Facades\Schema::hasTable('system_services');

        return Inertia::render('Status/Admin', [
            'services'        => $hasTable ? SystemService::ordered()->get() : [],
            'activeIncidents' => $hasTable ? Incident::active()->with('updates.creator')->latest()->get() : [],
            'resolvedIncidents' => $hasTable ? Incident::resolved()->latest('resolved_at')->take(5)->get() : [],
            'maintenance'     => $hasTable ? MaintenanceSchedule::with('creator')->latest('scheduled_start')->take(10)->get() : [],
            'uptimeStats'     => $hasTable ? $this->statusService->getUptimeStats() : [],
            'statusOptions'   => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], ServiceStatus::cases()),
            'severityOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], IncidentSeverity::cases()),
            'incidentStatusOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], IncidentStatus::cases()),
        ]);
    }

    /**
     * Update a service status.
     */
    public function updateService(Request $request, SystemService $service): RedirectResponse
    {
        Gate::authorize('manage', SystemService::class);

        $validated = $request->validate([
            'status' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $oldStatus = $service->status;
        $service->update([
            'status'      => ServiceStatus::from($validated['status']),
            'description' => $validated['description'] ?? $service->description,
        ]);

        if ($oldStatus !== $service->status) {
            event(new ServiceStatusChanged($service->fresh()));
        }

        return redirect()->back()->with('success', "Status {$service->name} diperbarui.");
    }

    /**
     * Create a new incident.
     */
    public function createIncident(Request $request): RedirectResponse
    {
        Gate::authorize('manage', SystemService::class);

        $validated = $request->validate([
            'title'             => ['required', 'string', 'max:255'],
            'severity'          => ['required', 'string'],
            'initial_message'   => ['required', 'string', 'max:500'],
            'service_ids'       => ['nullable', 'array'],
            'service_ids.*'     => ['integer', 'exists:system_services,id'],
            'affected_services' => ['nullable', 'array'],
        ]);

        $this->incidentService->create($validated, auth()->id());

        return redirect()->back()->with('success', 'Insiden berhasil dibuat.');
    }

    /**
     * Add an update to an incident.
     */
    public function updateIncident(Request $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('manage', SystemService::class);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'status'  => ['required', 'string'],
        ]);

        $this->incidentService->addUpdate($incident, $validated, auth()->id());

        return redirect()->back()->with('success', 'Insiden berhasil diperbarui.');
    }

    /**
     * Resolve an incident.
     */
    public function resolveIncident(Request $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('manage', SystemService::class);

        $validated = $request->validate([
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->incidentService->resolve($incident, $validated['resolution_notes'] ?? '', auth()->id());

        return redirect()->back()->with('success', 'Insiden berhasil diselesaikan.');
    }

    /**
     * Create a maintenance schedule and auto-sync all system services to maintenance status.
     */
    public function createMaintenance(Request $request): RedirectResponse
    {
        Gate::authorize('manage', SystemService::class);

        $validated = $request->validate([
            'title'             => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'scheduled_start'   => ['required', 'date'],
            'scheduled_end'     => ['required', 'date', 'after:scheduled_start'],
            'affected_services' => ['nullable', 'array'],
        ]);

        // Jika tidak ada affected_services yang dipilih, sync semua layanan
        $affectedServices = $validated['affected_services'] ?? [];
        if (empty($affectedServices)) {
            $affectedServices = SystemService::pluck('id')->toArray();
            $validated['affected_services'] = $affectedServices;
        }

        MaintenanceSchedule::create([
            ...$validated,
            'status'     => 'scheduled',
            'created_by' => auth()->id(),
        ]);

        // Update semua system service yang terdampak ke status 'maintenance' secara real-time
        SystemService::whereIn('id', $affectedServices)->update([
            'status'           => ServiceStatus::Maintenance,
            'last_checked_at'  => now(),
        ]);

        // Broadcast event untuk real-time update
        foreach (SystemService::whereIn('id', $affectedServices)->get() as $service) {
            event(new \App\Events\ServiceStatusChanged($service));
        }

        return redirect()->back()->with('success', 'Pemeliharaan berhasil dijadwalkan. ' . count($affectedServices) . ' layanan sistem dalam pemeliharaan.');
    }

    /**
     * Complete a maintenance schedule and restore affected services to operational.
     */
    public function completeMaintenance(Request $request, MaintenanceSchedule $maintenance): RedirectResponse
    {
        Gate::authorize('manage', SystemService::class);

        $maintenance->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        // Restore semua affected services kembali ke operational
        $affectedServices = $maintenance->affected_services ?? [];
        if (!empty($affectedServices)) {
            SystemService::whereIn('id', $affectedServices)->update([
                'status'           => ServiceStatus::Operational,
                'last_checked_at'  => now(),
            ]);

            // Broadcast event untuk real-time update
            foreach (SystemService::whereIn('id', $affectedServices)->get() as $service) {
                event(new \App\Events\ServiceStatusChanged($service));
            }
        }

        return redirect()->back()->with('success', 'Pemeliharaan selesai. ' . count($affectedServices) . ' layanan sistem kembali operasional.');
    }

    /**
     * Cancel a maintenance schedule and restore affected services immediately.
     */
    public function cancelMaintenance(Request $request, MaintenanceSchedule $maintenance): RedirectResponse
    {
        Gate::authorize('manage', SystemService::class);

        $maintenance->update([
            'status'       => 'cancelled',
            'completed_at' => now(),
        ]);

        // Restore semua affected services kembali ke operational
        $affectedServices = $maintenance->affected_services ?? [];
        if (!empty($affectedServices)) {
            SystemService::whereIn('id', $affectedServices)->update([
                'status'           => ServiceStatus::Operational,
                'last_checked_at'  => now(),
            ]);

            foreach (SystemService::whereIn('id', $affectedServices)->get() as $service) {
                event(new \App\Events\ServiceStatusChanged($service));
            }
        }

        return redirect()->back()->with('success', 'Pemeliharaan dibatalkan. Layanan sistem kembali operasional.');
    }

    /**
     * Create default services (first-time setup).
     */
    public function seedDefaults(): RedirectResponse
    {
        Gate::authorize('manage', SystemService::class);

        $defaults = [
            ['name' => 'Payroll Processing Engine', 'slug' => 'payroll-engine', 'category' => 'Core Services', 'sort_order' => 1],
            ['name' => 'Attendance System', 'slug' => 'attendance-system', 'category' => 'Core Services', 'sort_order' => 2],
            ['name' => 'QR Attendance Service', 'slug' => 'qr-attendance', 'category' => 'Core Services', 'sort_order' => 3],
            ['name' => 'Payslip Generator', 'slug' => 'payslip-generator', 'category' => 'Core Services', 'sort_order' => 4],
            ['name' => 'Tax Calculation Engine', 'slug' => 'tax-engine', 'category' => 'Core Services', 'sort_order' => 5],
            ['name' => 'Notification Service', 'slug' => 'notification-service', 'category' => 'Infrastructure', 'sort_order' => 6],
            ['name' => 'Queue Workers', 'slug' => 'queue-workers', 'category' => 'Infrastructure', 'sort_order' => 7],
            ['name' => 'Realtime WebSocket', 'slug' => 'websocket', 'category' => 'Infrastructure', 'sort_order' => 8],
            ['name' => 'Database', 'slug' => 'database', 'category' => 'Infrastructure', 'sort_order' => 9],
            ['name' => 'API Services', 'slug' => 'api-services', 'category' => 'Infrastructure', 'sort_order' => 10],
            ['name' => 'File Storage', 'slug' => 'file-storage', 'category' => 'Infrastructure', 'sort_order' => 11],
            ['name' => 'Email Delivery', 'slug' => 'email-delivery', 'category' => 'Infrastructure', 'sort_order' => 12],
        ];

        foreach ($defaults as $svc) {
            SystemService::firstOrCreate(
                ['slug' => $svc['slug']],
                [
                    ...$svc,
                    'status'       => ServiceStatus::Operational,
                    'is_public'    => true,
                    'description'  => $svc['name'],
                ]
            );
        }

        return redirect()->back()->with('success', 'Layanan default berhasil diinisialisasi.');
    }
}
