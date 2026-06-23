<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderEvaluationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'work_order_uuid' => $this->whenLoaded('workOrder', fn () => $this->workOrder->uuid),
            'evaluator' => $this->whenLoaded('evaluator', fn () => [
                'uuid' => $this->evaluator->uuid,
                'name' => $this->evaluator->name,
            ]),
            'rating' => $this->rating,
            'comments' => $this->comments,
            'evaluated_at' => $this->evaluated_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
