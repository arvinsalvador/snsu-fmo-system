<?php

namespace App\Http\Requests\Api\V1\Assets;

use App\Models\Asset;
use App\Models\Floor;
use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function after(): array
    {
        return [function (Validator $validator): void {
            $asset = $this->route('asset');
            $buildingId = $this->has('building_id') ? ($this->integer('building_id') ?: null) : $asset?->building_id;
            $floorId = $this->has('floor_id') ? ($this->integer('floor_id') ?: null) : $asset?->floor_id;
            $roomId = $this->has('room_id') ? ($this->integer('room_id') ?: null) : $asset?->room_id;

            if ($floorId && $buildingId && ! Floor::query()->whereKey($floorId)->where('building_id', $buildingId)->exists()) {
                $validator->errors()->add('floor_id', 'The selected floor does not belong to the selected building.');
            }

            if ($roomId) {
                $room = Room::query()->with('floor')->find($roomId);
                if ($floorId && $room?->floor_id !== $floorId) {
                    $validator->errors()->add('room_id', 'The selected room does not belong to the selected floor.');
                }
                if ($buildingId && $room?->floor?->building_id !== $buildingId) {
                    $validator->errors()->add('room_id', 'The selected room does not belong to the selected building.');
                }
            }
        }];
    }
}
