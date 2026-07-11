<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'asset' => new AssetResource($this->whenLoaded('asset')),
            'title' => $this->title,
            'description' => $this->description,
            'frequency' => $this->frequency,
            'frequency_label' => $this->frequency_label,
            'next_due_date' => $this->next_due_date?->toDateString(),
            'last_completed_date' => $this->last_completed_date?->toDateString(),
            'due_status' => $this->due_status,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
