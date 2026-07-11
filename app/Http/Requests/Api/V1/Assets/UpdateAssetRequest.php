<?php

namespace App\Http\Requests\Api\V1\Assets;

use App\Models\Asset;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends StoreAssetRequest
{
    public function rules(): array
    {
        $asset = $this->route('asset');

        $rules = [
            'asset_tag' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('assets', 'asset_tag')->ignore($asset)],
            'asset_code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('assets', 'asset_tag')->ignore($asset)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'asset_category_id' => ['nullable', Rule::exists('asset_categories', 'id')->whereNull('deleted_at')],
            'building_id' => ['nullable', Rule::exists('buildings', 'id')->whereNull('deleted_at')],
            'floor_id' => ['nullable', Rule::exists('floors', 'id')->whereNull('deleted_at')],
            'room_id' => ['nullable', Rule::exists('rooms', 'id')->whereNull('deleted_at')],
            'location' => ['nullable', 'string', 'max:255'],
            'exact_location' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_until' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'status' => ['sometimes', 'required', Rule::in(Asset::STATUSES)],
            'remarks' => ['nullable', 'string'],
        ];

        return $rules;
    }
}
