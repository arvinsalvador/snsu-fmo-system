<?php

namespace App\Http\Requests\Api\V1\WorkOrders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkOrderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status_id' => ['nullable', 'integer', Rule::exists('work_order_statuses', 'id')->whereNull('deleted_at')],
            'notes' => ['required', 'string', 'max:10000'],
            'estimated_remaining_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ];
    }
}
