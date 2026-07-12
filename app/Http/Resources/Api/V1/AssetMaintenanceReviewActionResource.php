<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetMaintenanceReviewActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'action' => $this->action,
            'previous_status' => $this->previous_status,
            'new_status' => $this->new_status,
            'comments' => $this->comments,
            'actor' => new UserResource($this->whenLoaded('actor')),
            'acted_at' => $this->acted_at,
            'metadata' => $this->metadata,
        ];
    }
}
