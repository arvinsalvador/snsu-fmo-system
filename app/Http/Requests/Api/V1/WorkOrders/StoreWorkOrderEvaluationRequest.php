<?php

namespace App\Http\Requests\Api\V1\WorkOrders;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comments' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
