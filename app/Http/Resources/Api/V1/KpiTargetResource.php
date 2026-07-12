<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiTargetResource extends JsonResource
{
    public function toArray(Request $r): array
    {
        return ['uuid' => $this->uuid, 'definition' => new KpiDefinitionResource($this->whenLoaded('definition')), 'scope' => ['type' => $this->scope_type, 'id' => $this->scope_id], 'period' => ['start' => $this->period_start?->toDateString(), 'end' => $this->period_end?->toDateString()], 'target_value' => $this->target_value, 'minimum_value' => $this->minimum_value, 'maximum_value' => $this->maximum_value, 'warning_threshold' => $this->warning_threshold, 'critical_threshold' => $this->critical_threshold, 'owner' => $this->whenLoaded('owner', fn () => ['id' => $this->owner?->id, 'name' => $this->owner?->name]), 'status' => $this->status, 'evaluation_status' => $this->current_evaluation_status, 'notes' => $this->notes, 'latest_evaluation' => $this->whenLoaded('evaluations', fn () => new KpiEvaluationResource($this->evaluations->first()))];
    }
}
