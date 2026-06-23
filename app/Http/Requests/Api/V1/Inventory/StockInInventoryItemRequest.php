<?php

namespace App\Http\Requests\Api\V1\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StockInInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['quantity' => ['required', 'numeric', 'gt:0'], 'remarks' => ['nullable', 'string']];
    }
}
