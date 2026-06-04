<?php

namespace App\Events;

use App\Models\Incident;
use App\Models\IncidentUpdate;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Incident $incident,
        public readonly ?IncidentUpdate $update = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('system-status')];
    }

    public function broadcastAs(): string
    {
        return 'incident.updated';
    }
}
