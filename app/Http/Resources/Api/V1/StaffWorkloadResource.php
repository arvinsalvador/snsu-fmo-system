<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffWorkloadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'staff_profile' => [
                'id' => $this->id,
                'user_id' => $this->user_id,
                'employee_code' => $this->employee_code,
                'position' => $this->position,
                'designation' => $this->designation,
                'employment_status' => $this->employment_status,
                'availability_status' => $this->availability_status,
            ],
            'user' => [
                'id' => $this->user?->id,
                'uuid' => $this->user?->uuid,
                'name' => $this->user?->name,
            ],
            'availability_status' => $this->availability_status,
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'active_assigned_count' => (int) $this->active_assigned_count,
            'pending_assigned_count' => (int) $this->pending_assigned_count,
            'in_progress_assigned_count' => (int) $this->in_progress_assigned_count,
        ];
    }
}
