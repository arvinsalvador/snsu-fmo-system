<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetMaintenanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'asset_id' => $this->asset_id,
            'asset' => new AssetResource($this->whenLoaded('asset')),
            'maintenance_type_id' => $this->maintenance_type_id,
            'maintenance_type' => new MasterDataResource($this->whenLoaded('maintenanceType')),
            'maintenance_date' => $this->maintenance_date,
            'performed_by' => $this->performed_by,
            'remarks' => $this->remarks,
            'findings' => $this->findings,
            'actions_taken' => $this->actions_taken,
            'cost' => $this->cost,
            'next_maintenance_date' => $this->next_maintenance_date,
            'recorded_by' => new UserResource($this->whenLoaded('recorder')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
