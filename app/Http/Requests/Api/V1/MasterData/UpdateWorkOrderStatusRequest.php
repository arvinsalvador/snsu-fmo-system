<?php

namespace App\Http\Requests\Api\V1\MasterData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkOrderStatusRequest extends FormRequest
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
        $status = $this->route('workOrderStatus');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('work_order_statuses', 'name')->ignore($status->id)],
            'sort_order' => ['sometimes', 'required', 'integer', 'min:1', Rule::unique('work_order_statuses', 'sort_order')->ignore($status->id)],
            'is_terminal' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
