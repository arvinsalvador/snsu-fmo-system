<?php

namespace App\Http\Requests\Api\V1\WorkOrders;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkOrderMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity_requested' => ['sometimes', 'required', 'numeric', 'gte:0'],
            'quantity_issued' => ['sometimes', 'required', 'numeric', 'gte:0'],
            'quantity_used' => ['sometimes', 'required', 'numeric', 'gte:0'],
            'remarks' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
