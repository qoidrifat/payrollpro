<?php

namespace App\Services;

use App\Enums\IncidentStatus;
use App\Events\IncidentCreated;
use App\Events\IncidentUpdated;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use Illuminate\Support\Str;

class IncidentService
{
    /**
     * Create a new incident.
     */
    public function create(array $data, int $userId): Incident
    {
        $incident = Incident::create([
            'title'            => $data['title'],
            'slug'             => Str::slug($data['title']) . '-' . Str::random(6),
            'severity'         => $data['severity'],
            'status'           => IncidentStatus::Investigating,
            'affected_services' => $data['affected_services'] ?? [],
            'started_at'       => now(),
            'created_by'       => $userId,
        ]);

        // Sync affected services
        if (!empty($data['service_ids'])) {
            $incident->services()->sync($data['service_ids']);
        }

        // Initial update entry
        IncidentUpdate::create([
            'incident_id' => $incident->id,
            'message'     => $data['initial_message'] ?? 'We are investigating reports of an issue.',
            'status'      => IncidentStatus::Investigating,
            'created_by'  => $userId,
        ]);

        AuditService::log(
            'incident_created',
            "Incident '{$incident->title}' created",
            'Incident',
            $incident->id,
        );

        event(new IncidentCreated($incident));

        return $incident;
    }

    /**
     * Add an update to an incident.
     */
    public function addUpdate(Incident $incident, array $data, int $userId): IncidentUpdate
    {
        $update = IncidentUpdate::create([
            'incident_id' => $incident->id,
            'message'     => $data['message'],
            'status'      => $data['status'] ?? $incident->status,
            'created_by'  => $userId,
        ]);

        // Update incident status if progressing
        if (isset($data['status']) && $data['status'] !== $incident->status->value) {
            $incident->update(['status' => $data['status']]);
        }

        event(new IncidentUpdated($incident, $update));

        return $update;
    }

    /**
     * Resolve an incident.
     */
    public function resolve(Incident $incident, string $notes, int $userId): void
    {
        IncidentUpdate::create([
            'incident_id' => $incident->id,
            'message'     => $notes ?: 'This incident has been resolved.',
            'status'      => IncidentStatus::Resolved,
            'created_by'  => $userId,
        ]);

        $incident->resolve($notes);

        AuditService::log(
            'incident_resolved',
            "Incident '{$incident->title}' resolved",
            'Incident',
            $incident->id,
        );

        event(new IncidentUpdated($incident));
    }
}
