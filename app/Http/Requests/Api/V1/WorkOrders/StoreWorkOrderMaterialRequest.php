<?php

namespace App\Http\Requests\Api\V1\WorkOrders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkOrderMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_item_id' => ['required', Rule::exists('inventory_items', 'id')->whereNull('deleted_at')],
            'quantity_requested' => ['required', 'numeric', 'gte:0'],
            'quantity_issued' => ['nullable', 'numeric', 'gte:0'],
            'quantity_used' => ['nullable', 'numeric', 'gte:0'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
