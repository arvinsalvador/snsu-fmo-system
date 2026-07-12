<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiEvaluationResource extends JsonResource
{
    public function toArray(Request $r): array
    {
        return ['uuid' => $this->uuid, 'evaluation_date' => $this->evaluation_date?->toDateString(), 'actual_value' => $this->actual_value, 'target_value' => $this->target_value, 'variance' => $this->variance, 'achievement_percentage' => $this->achievement_percentage, 'score' => $this->score, 'status' => $this->status, 'trend_direction' => $this->trend_direction, 'source_summary' => $this->source_summary, 'calculated_at' => $this->calculated_at?->toIso8601String()];
    }
}
