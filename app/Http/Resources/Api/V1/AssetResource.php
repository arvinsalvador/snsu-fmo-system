<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'asset_code' => $this->asset_code,
            'asset_category_id' => $this->asset_category_id,
            'category' => new MasterDataResource($this->whenLoaded('category')),
            'building_id' => $this->building_id,
            'building' => new BuildingResource($this->whenLoaded('building')),
            'floor_id' => $this->floor_id,
            'floor' => new FloorResource($this->whenLoaded('floor')),
            'room_id' => $this->room_id,
            'room' => new RoomResource($this->whenLoaded('room')),
            'exact_location' => $this->exact_location,
            'brand' => $this->brand,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'purchase_date' => $this->purchase_date,
            'warranty_until' => $this->warranty_until,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'photos' => AssetPhotoResource::collection($this->whenLoaded('photos')),
            'maintenance_records' => AssetMaintenanceRecordResource::collection($this->whenLoaded('maintenanceRecords')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
