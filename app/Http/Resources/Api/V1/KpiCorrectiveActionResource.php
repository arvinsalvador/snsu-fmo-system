<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiCorrectiveActionResource extends JsonResource
{
    public function toArray(Request $r): array
    {
        return ['uuid' => $this->uuid, 'target_uuid' => $this->target?->uuid, 'title' => $this->title, 'description' => $this->description, 'root_cause' => $this->root_cause, 'planned_action' => $this->planned_action, 'assignee' => $this->whenLoaded('assignee', fn () => ['id' => $this->assignee?->id, 'name' => $this->assignee?->name]), 'due_date' => $this->due_date?->toDateString(), 'priority' => $this->priority, 'status' => $this->effective_status, 'completed_at' => $this->completed_at?->toIso8601String(), 'completion_notes' => $this->completion_notes];
    }
}
