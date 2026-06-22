<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DatabaseNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->data['event'] ?? class_basename($this->type),
            'data' => $this->data,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
