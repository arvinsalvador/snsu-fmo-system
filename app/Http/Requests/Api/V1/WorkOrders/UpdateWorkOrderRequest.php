<?php

namespace App\Http\Requests\Api\V1\WorkOrders;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'building_id' => ['sometimes', 'required', 'integer', 'exists:buildings,id'],
            'floor_id' => ['nullable', 'integer', 'exists:floors,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:work_order_categories,id'],
            'priority_id' => ['sometimes', 'required', 'integer', 'exists:priorities,id'],
            'status_id' => ['sometimes', 'required', 'integer', 'exists:work_order_statuses,id'],
            'preferred_staff_id' => ['nullable', 'integer', 'exists:staff_profiles,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'requested_at' => ['sometimes', 'date'],
            'target_completion_date' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
        ];
    }
}
