<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn() => new EmployeeResource($this->employee)),
            'date' => $this->date?->toDateString(),
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'notes' => $this->notes,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
