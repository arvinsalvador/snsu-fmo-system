<?php

namespace App\Http\Requests\Api\V1\WorkOrders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkOrderIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:255'], 'status_id' => ['nullable', 'integer', 'exists:work_order_statuses,id'], 'approval_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])], 'priority_id' => ['nullable', 'integer', 'exists:priorities,id'], 'category_id' => ['nullable', 'integer', 'exists:work_order_categories,id'], 'department_id' => ['nullable', 'integer', 'exists:departments,id'], 'building_id' => ['nullable', 'integer', 'exists:buildings,id'], 'requestor_id' => ['nullable', 'integer', 'exists:users,id'], 'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'], 'updated_after' => ['nullable', 'date'], 'sort_by' => ['nullable', Rule::in(['requested_at', 'updated_at', 'target_completion_date', 'work_order_number'])], 'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'between:1,100']];
    }
}
