<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'total_gross' => (float) $this->total_gross,
            'total_deductions' => (float) $this->total_deductions,
            'total_net' => (float) $this->total_net,
            'total_employees' => $this->total_employees,
            'progress_percentage' => $this->progress_percentage,
            'current_batch' => $this->current_batch,
            'total_batches' => $this->total_batches,
            'notes' => $this->notes,
            'processed_by' => $this->whenLoaded('processedBy', fn() => new UserResource($this->processedBy)),
            'approved_by' => $this->whenLoaded('approvedBy', fn() => new UserResource($this->approvedBy)),
            'processed_at' => $this->processed_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'items' => PayrollItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),
        ];
    }
}
