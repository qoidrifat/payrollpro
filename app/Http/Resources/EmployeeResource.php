<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
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
            'nik' => mask_sensitive($this->nik),
            'npwp' => mask_sensitive($this->npwp),
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'position' => $this->position,
            'department' => $this->department,
            'join_date' => $this->join_date?->toDateString(),
            'resign_date' => $this->resign_date?->toDateString(),
            'employment_status' => $this->employment_status?->value,
            'employment_status_label' => $this->employment_status?->label(),
            'marital_status' => $this->marital_status?->value,
            'dependents_count' => $this->dependents_count ?? 0,
            'base_salary' => (float) $this->base_salary,
            'is_active' => $this->is_active,
            'phone' => $this->phone,
            'city' => $this->city,
            'province' => $this->province,
            'bank_name' => $this->bank_name,
            'bank_account_number' => mask_sensitive($this->bank_account_number),
            'bank_account_name' => $this->bank_account_name,
            'bpjs_kesehatan' => mask_sensitive($this->bpjs_kesehatan),
            'bpjs_ketenagakerjaan' => mask_sensitive($this->bpjs_ketenagakerjaan),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'component_count' => $this->whenCounted('salaryComponents'),
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
        ];
    }
}
