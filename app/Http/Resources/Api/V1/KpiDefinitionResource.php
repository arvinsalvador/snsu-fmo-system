<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiDefinitionResource extends JsonResource
{
    public function toArray(Request $r): array
    {
        return ['uuid' => $this->uuid, 'name' => $this->name, 'code' => $this->code, 'description' => $this->description, 'category' => $this->category, 'metric_key' => $this->metric_key, 'calculation_type' => $this->calculation_type, 'unit' => $this->unit, 'direction' => $this->direction, 'aggregation_period' => $this->aggregation_period, 'data_source' => $this->data_source, 'scope_type' => $this->scope_type, 'is_active' => $this->is_active, 'is_system' => $this->is_system];
    }
}
