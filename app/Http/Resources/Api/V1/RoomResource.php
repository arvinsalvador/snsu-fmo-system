<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'floor_id' => $this->floor_id,
            'room_name' => $this->room_name,
            'room_code' => $this->room_code,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'floor' => new FloorResource($this->whenLoaded('floor')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
