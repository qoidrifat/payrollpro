<?php

namespace App\Events;

use App\Models\SystemService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SystemService $service,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('system-status')];
    }

    public function broadcastAs(): string
    {
        return 'service.status-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'id'                => $this->service->id,
            'name'              => $this->service->name,
            'slug'              => $this->service->slug,
            'status'            => $this->service->status->value,
            'status_label'      => $this->service->status->label(),
            'status_color'      => $this->service->status->color(),
            'response_time_ms'  => $this->service->response_time_ms,
            'uptime_percentage' => $this->service->uptime_percentage,
        ];
    }
}
