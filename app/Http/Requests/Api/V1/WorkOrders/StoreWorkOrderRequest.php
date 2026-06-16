<?php

namespace App\Http\Requests\Api\V1\WorkOrders;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderRequest extends FormRequest
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
            'building_id' => ['required', 'integer', 'exists:buildings,id'],
            'floor_id' => ['nullable', 'integer', 'exists:floors,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'category_id' => ['required', 'integer', 'exists:work_order_categories,id'],
            'priority_id' => ['required', 'integer', 'exists:priorities,id'],
            'preferred_staff_id' => ['nullable', 'integer', 'exists:staff_profiles,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'target_completion_date' => ['nullable', 'date', 'after_or_equal:today'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*.file_path' => ['required_with:attachments', 'string', 'max:2048'],
            'attachments.*.original_name' => ['nullable', 'string', 'max:255'],
            'attachments.*.mime_type' => ['nullable', 'string', 'max:255'],
            'attachments.*.file_size' => ['nullable', 'integer', 'min:0'],
            'attachments.*.caption' => ['nullable', 'string', 'max:255'],
        ];
    }
}
