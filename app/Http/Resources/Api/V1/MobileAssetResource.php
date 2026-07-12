<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileAssetResource extends JsonResource
{
    public function toArray(Request $r): array
    {
        return ['uuid' => $this->uuid, 'qr_identifier' => $this->qr_token, 'asset_code' => $this->asset_tag, 'name' => $this->name, 'category' => $this->category?->name, 'status' => $this->status, 'location' => ['building' => $this->building?->name, 'floor' => $this->floor?->floor_name, 'room' => $this->room?->room_code, 'exact' => $this->location], 'maintenance' => ['next_due_date' => $this->maintenanceSchedules?->where('is_active', true)->min('next_due_date')?->toDateString(), 'active_work_order_count' => $this->active_work_orders_count ?? 0], 'authorized_actions' => array_values(array_filter(['view', $r->user()?->can('manage_assets') ? 'manage' : null, $r->user()?->can('manage_maintenance_records') ? 'record_maintenance' : null])), 'updated_at' => $this->updated_at?->utc()->toIso8601String()];
    }
}
