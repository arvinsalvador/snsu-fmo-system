<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'work_order_id' => $this->work_order_id,
            'assigned_staff_id' => $this->assigned_staff_id,
            'assigned_by' => $this->assigned_by,
            'assignment_type' => $this->assignment_type,
            'remarks' => $this->remarks,
            'assigned_at' => $this->assigned_at,
            'unassigned_at' => $this->unassigned_at,
            'is_active' => $this->unassigned_at === null,
            'assigned_staff' => new StaffProfileResource($this->whenLoaded('assignedStaff')),
            'assigner' => new UserResource($this->whenLoaded('assignedBy')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
