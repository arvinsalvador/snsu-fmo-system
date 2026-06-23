<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'item_code' => $this->item_code,
            'category_id' => $this->category_id,
            'category' => new MasterDataResource($this->whenLoaded('category')),
            'name' => $this->name,
            'brand' => $this->brand,
            'unit' => $this->unit,
            'minimum_stock' => $this->minimum_stock,
            'current_stock' => $this->current_stock,
            'is_low_stock' => $this->is_low_stock,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
