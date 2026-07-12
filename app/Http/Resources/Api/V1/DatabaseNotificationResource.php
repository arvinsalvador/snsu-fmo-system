<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DatabaseNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->id,
            'id' => $this->id,
            'type' => $this->data['event'] ?? class_basename($this->type),
            'title' => $this->data['title'] ?? $this->data['message'] ?? 'Notification', 'message' => $this->data['message'] ?? null,
            'action_url' => $this->data['url'] ?? null, 'mobile_route' => $this->data['mobile_route'] ?? null, 'metadata' => $this->data,
            'is_read' => $this->read_at !== null, 'read_at' => $this->read_at?->utc()->toIso8601String(),
            'created_at' => $this->created_at?->utc()->toIso8601String(),
        ];
    }
}
