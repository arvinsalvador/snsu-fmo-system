<?php

namespace App\Http\Requests\Api\V1\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemId = $this->route('inventoryItem')?->id;

        return [
            'item_code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('inventory_items', 'item_code')->ignore($itemId)],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:inventory_categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'unit' => ['sometimes', 'required', 'string', 'max:50'],
            'minimum_stock' => ['sometimes', 'required', 'numeric', 'min:0'],
            'current_stock' => ['prohibited'],
            'remarks' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
        ];
    }
}
