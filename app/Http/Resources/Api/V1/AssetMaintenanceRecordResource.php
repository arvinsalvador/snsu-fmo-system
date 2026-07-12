<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetMaintenanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'asset_id' => $this->asset_id,
            'asset' => $this->whenLoaded('asset', fn () => [
                'id' => $this->asset->id,
                'asset_tag' => $this->asset->asset_tag,
                'name' => $this->asset->name,
                'location' => $this->asset->location,
                'status' => $this->asset->status,
            ]),
            'maintenance_type' => new MasterDataResource($this->whenLoaded('maintenanceType')),
            'maintenance_type_id' => $this->maintenance_type_id,
            'maintenance_schedule' => $this->whenLoaded('maintenanceSchedule', fn () => $this->maintenanceSchedule ? [
                'id' => $this->maintenanceSchedule->id,
                'title' => $this->maintenanceSchedule->title,
                'frequency' => $this->maintenanceSchedule->frequency,
                'next_due_date' => $this->maintenanceSchedule->next_due_date?->toDateString(),
            ] : null),
            'work_order' => $this->whenLoaded('workOrder', fn () => $this->workOrder ? [
                'id' => $this->workOrder->id,
                'uuid' => $this->workOrder->uuid,
                'work_order_number' => $this->workOrder->work_order_number,
                'title' => $this->workOrder->title,
            ] : null),
            'staff' => $this->whenLoaded('staffProfile', fn () => $this->staffProfile ? [
                'id' => $this->staffProfile->id,
                'employee_code' => $this->staffProfile->employee_code,
                'user' => $this->staffProfile->relationLoaded('user') && $this->staffProfile->user ? [
                    'id' => $this->staffProfile->user->id,
                    'uuid' => $this->staffProfile->user->uuid,
                    'name' => $this->staffProfile->user->name,
                ] : null,
            ] : null),
            'completed_by' => $this->whenLoaded('completedBy', fn () => $this->completedBy ? [
                'id' => $this->completedBy->id,
                'uuid' => $this->completedBy->uuid,
                'name' => $this->completedBy->name,
            ] : null),
            'completion_date' => $this->completion_date?->toDateString(),
            'maintenance_date' => $this->maintenance_date?->toDateString(),
            'performed_by' => $this->performed_by,
            'findings' => $this->findings,
            'actions_taken' => $this->actions_taken,
            'remarks' => $this->remarks,
            'labor_cost' => $this->labor_cost,
            'total_cost' => $this->total_cost,
            'next_maintenance_date' => $this->next_maintenance_date?->toDateString(),
            'review_status' => $this->review_status,
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'reviewed_at' => $this->reviewed_at,
            'review_notes' => $this->review_notes,
            'correction_requester' => new UserResource($this->whenLoaded('correctionRequester')),
            'correction_requested_at' => $this->correction_requested_at,
            'correction_reason' => $this->correction_reason,
            'corrector' => new UserResource($this->whenLoaded('corrector')),
            'corrected_at' => $this->corrected_at,
            'rejection_reason' => $this->rejection_reason,
            'locked_at' => $this->locked_at,
            'review_actions' => AssetMaintenanceReviewActionResource::collection($this->whenLoaded('reviewActions')),
            'correction_count' => $this->when(isset($this->correction_count), $this->correction_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
