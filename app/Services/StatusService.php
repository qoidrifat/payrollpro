<?php

namespace App\Services;

use App\Enums\ServiceStatus;
use App\Models\Incident;
use App\Models\MaintenanceSchedule;
use App\Models\SystemService;
use App\Models\UptimeLog;
use Illuminate\Support\Facades\DB;

class StatusService
{
    /**
     * Get full system status overview for the public status page.
     */
    public function getOverview(): array
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('system_services')) {
            return [
                'overall_status'     => ['status' => 'operational', 'label' => 'All Systems Operational', 'color' => 'emerald'],
                'services'           => [],
                'service_categories' => [],
                'active_incidents'   => [],
                'active_maintenance' => [],
                'upcoming_maintenance' => [],
                'resolved_incidents' => [],
                'summary'            => $this->emptySummary(),
            ];
        }

        $services = SystemService::public()->ordered()->get();
        $activeIncidents = Incident::active()->latest()->get();
        $upcomingMaintenance = MaintenanceSchedule::upcoming()->latest('scheduled_start')->take(3)->get();
        $activeMaintenance = MaintenanceSchedule::active()->latest('scheduled_start')->get();
        $resolvedIncidents = Incident::resolved()->latest('resolved_at')->take(14)->get();

        $overallStatus = $this->computeOverallStatus($services);

        return [
            'overall_status'       => $overallStatus,
            'services'             => $services->map(fn($s) => $this->formatService($s)),
            'service_categories'   => $services->groupBy('category')->map->map(fn($s) => $this->formatService($s)),
            'active_incidents'     => $activeIncidents,
            'active_maintenance'   => $activeMaintenance,
            'upcoming_maintenance' => $upcomingMaintenance,
            'resolved_incidents'   => $resolvedIncidents->take(7),
            'summary'              => [
                'total_services'       => $services->count(),
                'operational'          => $services->where('status', ServiceStatus::Operational)->count(),
                'degraded'             => $services->where('status', ServiceStatus::DegradedPerformance)->count(),
                'outage'               => $services->where('status', ServiceStatus::PartialOutage)->count()
                                        + $services->where('status', ServiceStatus::MajorOutage)->count(),
                'maintenance'          => $services->where('status', ServiceStatus::Maintenance)->count(),
                'active_incidents'     => $activeIncidents->count(),
                'avg_response_time_ms' => $services->avg('response_time_ms'),
                'avg_uptime'           => $services->avg('uptime_percentage'),
            ],
        ];
    }

    /**
     * Check health of a specific service and record uptime.
     */
    public function checkService(SystemService $service): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('system_services')) {
            return;
        }
        $startTime = microtime(true);

        // Perform a lightweight check based on service type
        $status = $this->performHealthCheck($service);

        $responseTime = (int) round((microtime(true) - $startTime) * 1000);

        $service->update([
            'status'           => $status,
            'response_time_ms' => $responseTime,
            'last_checked_at'  => now(),
            'uptime_percentage' => $service->calculateUptime(),
        ]);

        UptimeLog::create([
            'system_service_id' => $service->id,
            'status'            => $status,
            'checked_at'        => now(),
            'response_time_ms'  => $responseTime,
        ]);
    }

    /**
     * Check all services and record health.
     */
    public function checkAllServices(): array
    {
        $services = SystemService::all();
        $results = [];

        foreach ($services as $service) {
            $this->checkService($service);
            $results[$service->slug] = $service->fresh()->status->value;
        }

        return $results;
    }

    /**
     * Get uptime statistics for the last N days.
     */
    public function getUptimeStats(int $days = 90): array
    {
        $since = now()->subDays($days);
        $services = SystemService::public()->ordered()->get();

        $stats = [];
        foreach ($services as $service) {
            $logs = UptimeLog::where('system_service_id', $service->id)
                ->where('checked_at', '>=', $since)
                ->get();

            $total = $logs->count();
            $operational = $logs->where('status', ServiceStatus::Operational)->count();

            $stats[$service->slug] = [
                'name'       => $service->name,
                'uptime_pct' => $total > 0 ? round(($operational / $total) * 100, 2) : 100.0,
                'total_checks' => $total,
                'avg_response_ms' => $total > 0 ? (int) $logs->avg('response_time_ms') : 0,
            ];
        }

        return $stats;
    }

    private function formatService(SystemService $service): array
    {
        return [
            'id'                => $service->id,
            'name'              => $service->name,
            'slug'              => $service->slug,
            'description'       => $service->description,
            'category'          => $service->category,
            'status'            => $service->status->value,
            'status_label'      => $service->status->label(),
            'status_color'      => $service->status->color(),
            'response_time_ms'  => $service->response_time_ms,
            'uptime_percentage' => $service->uptime_percentage,
            'last_checked_at'   => $service->last_checked_at?->diffForHumans(),
        ];
    }

    private function emptySummary(): array
    {
        return [
            'total_services'       => 0,
            'operational'          => 0,
            'degraded'             => 0,
            'outage'               => 0,
            'maintenance'          => 0,
            'active_incidents'     => 0,
            'avg_response_time_ms' => 0,
            'avg_uptime'           => 100.0,
        ];
    }

    private function computeOverallStatus($services): array
    {
        $statuses = $services->pluck('status');
        $maxSeverity = $statuses->max(fn($s) => $s->severity());

        if ($maxSeverity >= 3) return ['status' => 'major_outage', 'label' => 'Major Outage', 'color' => 'red'];
        if ($maxSeverity >= 2) return ['status' => 'partial_outage', 'label' => 'Partial Outage', 'color' => 'amber'];
        if ($maxSeverity >= 1) return ['status' => 'degraded', 'label' => 'Degraded Performance', 'color' => 'yellow'];

        return ['status' => 'operational', 'label' => 'All Systems Operational', 'color' => 'emerald'];
    }

    private function performHealthCheck(SystemService $service): ServiceStatus
    {
        return match ($service->slug) {
            'database' => $this->checkDatabase(),
            'queue-workers' => $this->checkQueue(),
            'file-storage' => $this->checkStorage(),
            default => ServiceStatus::Operational,
        };
    }

    private function checkDatabase(): ServiceStatus
    {
        try {
            DB::connection()->getPdo();
            return ServiceStatus::Operational;
        } catch (\Exception) {
            return ServiceStatus::MajorOutage;
        }
    }

    private function checkQueue(): ServiceStatus
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            if ($failed > 50) return ServiceStatus::MajorOutage;
            if ($pending > 1000) return ServiceStatus::DegradedPerformance;
            return ServiceStatus::Operational;
        } catch (\Exception) {
            return ServiceStatus::MajorOutage;
        }
    }

    private function checkStorage(): ServiceStatus
    {
        try {
            $freeSpace = disk_free_space(storage_path());
            $totalSpace = disk_total_space(storage_path());
            $pct = ($freeSpace / $totalSpace) * 100;
            if ($pct < 5) return ServiceStatus::MajorOutage;
            if ($pct < 15) return ServiceStatus::DegradedPerformance;
            return ServiceStatus::Operational;
        } catch (\Exception) {
            return ServiceStatus::MajorOutage;
        }
    }
}
