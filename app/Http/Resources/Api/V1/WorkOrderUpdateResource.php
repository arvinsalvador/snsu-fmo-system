<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderUpdateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => new WorkOrderStatusResource($this->whenLoaded('status')),
            'notes' => $this->notes,
            'estimated_remaining_days' => $this->estimated_remaining_days,
            'staff' => $this->whenLoaded('staff', fn () => [
                'id' => $this->staff->id,
                'employee_code' => $this->staff->employee_code,
                'availability_status' => $this->staff->availability_status,
                'user' => $this->staff->relationLoaded('user') ? [
                    'id' => $this->staff->user->id,
                    'uuid' => $this->staff->user->uuid,
                    'name' => $this->staff->user->name,
                ] : null,
            ]),
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'uuid' => $this->creator->uuid,
                'name' => $this->creator->name,
            ]),
            'photos' => WorkOrderUpdatePhotoResource::collection($this->whenLoaded('photos')),
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
