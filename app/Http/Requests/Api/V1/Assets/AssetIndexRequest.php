<?php

namespace App\Http\Requests\Api\V1\Assets;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['sort' => $this->input('sort_by', $this->input('sort')), 'direction' => $this->input('sort_direction', $this->input('direction'))]);
    }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:255'], 'asset_category_id' => ['nullable', 'exists:asset_categories,id'], 'building_id' => ['nullable', 'exists:buildings,id'], 'floor_id' => ['nullable', 'exists:floors,id'], 'room_id' => ['nullable', 'exists:rooms,id'], 'status' => ['nullable', Rule::in(Asset::STATUSES)], 'updated_after' => ['nullable', 'date'], 'sort' => ['nullable', Rule::in(['asset_tag', 'name', 'status', 'updated_at', 'purchase_date'])], 'direction' => ['nullable', Rule::in(['asc', 'desc'])], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'between:1,100']];
    }
}
