<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderMaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'inventory_item' => new InventoryItemResource($this->whenLoaded('inventoryItem')),
            'quantity_requested' => $this->quantity_requested,
            'quantity_issued' => $this->quantity_issued,
            'quantity_used' => $this->quantity_used,
            'remarks' => $this->remarks,
            'issued_by' => $this->whenLoaded('issuer', fn () => [
                'id' => $this->issuer->id,
                'uuid' => $this->issuer->uuid,
                'name' => $this->issuer->name,
            ]),
            'issued_at' => $this->issued_at,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
