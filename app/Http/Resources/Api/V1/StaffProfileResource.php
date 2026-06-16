<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'employee_code' => $this->employee_code,
            'position' => $this->position,
            'designation' => $this->designation,
            'employment_status' => $this->employment_status,
            'availability_status' => $this->availability_status,
            'remarks' => $this->remarks,
            'user' => new UserResource($this->whenLoaded('user')),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
