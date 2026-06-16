<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'work_order_number' => $this->work_order_number,
            'requestor_id' => $this->requestor_id,
            'department_id' => $this->department_id,
            'building_id' => $this->building_id,
            'floor_id' => $this->floor_id,
            'room_id' => $this->room_id,
            'category_id' => $this->category_id,
            'priority_id' => $this->priority_id,
            'status_id' => $this->status_id,
            'approval_status' => $this->approval_status,
            'preferred_staff_id' => $this->preferred_staff_id,
            'title' => $this->title,
            'description' => $this->description,
            'requested_at' => $this->requested_at,
            'target_completion_date' => $this->target_completion_date,
            'completed_at' => $this->completed_at,
            'approved_at' => $this->approved_at,
            'rejected_at' => $this->rejected_at,
            'requestor' => new UserResource($this->whenLoaded('requestor')),
            'department' => new MasterDataResource($this->whenLoaded('department')),
            'building' => new BuildingResource($this->whenLoaded('building')),
            'floor' => new FloorResource($this->whenLoaded('floor')),
            'room' => new RoomResource($this->whenLoaded('room')),
            'category' => new MasterDataResource($this->whenLoaded('category')),
            'priority' => new PriorityResource($this->whenLoaded('priority')),
            'status' => new WorkOrderStatusResource($this->whenLoaded('status')),
            'preferred_staff' => new StaffProfileResource($this->whenLoaded('preferredStaff')),
            'attachments' => WorkOrderAttachmentResource::collection($this->whenLoaded('attachments')),
            'approvals' => WorkOrderApprovalResource::collection($this->whenLoaded('approvals')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
