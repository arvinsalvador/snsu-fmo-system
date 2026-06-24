<?php

namespace App\Http\Requests\Api\V1\Assets;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_tag' => ['required_without:asset_code', 'string', 'max:255', 'unique:assets,asset_tag'],
            'asset_code' => ['required_without:asset_tag', 'string', 'max:255', 'unique:assets,asset_tag'],
            'name' => ['required', 'string', 'max:255'],
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
            'status' => ['required', Rule::in(Asset::STATUSES)],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
