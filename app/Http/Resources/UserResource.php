<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roles = $this->getRoleNames();

        // Only expose the full permission set to Admin/HR. Regular employees
        // don't need it and shipping it leaks the authorization surface.
        $isAdminOrHr = $roles->intersect(['Admin', 'HR'])->isNotEmpty();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'account_status' => $this->account_status,
            'has_employee_record' => $this->employee()->exists(),
            'roles' => $roles,
            'permissions' => $isAdminOrHr ? $this->getAllPermissions()->pluck('name') : [],
            'employee' => $this->whenLoaded('employee', fn () => new EmployeeResource($this->employee)),
            'approved_at' => $this->approved_at?->toISOString(),
            'suspended_at' => $this->suspended_at?->toISOString(),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
