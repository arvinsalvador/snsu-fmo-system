<?php

namespace App\Http\Requests\Api\V1\WorkOrders;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReassignWorkOrderRequest extends FormRequest
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
            'assignment_type' => ['required', 'string', Rule::in(['individual', 'team'])],
            'assigned_staff_id' => [
                'required_if:assignment_type,individual',
                'prohibited_if:assignment_type,team',
                'nullable',
                'integer',
                Rule::exists('staff_profiles', 'id')->whereNull('deleted_at'),
            ],
            'staff_ids' => [
                'required_if:assignment_type,team',
                'prohibited_if:assignment_type,individual',
                'nullable',
                'array',
                'min:2',
                'max:20',
            ],
            'staff_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('staff_profiles', 'id')->whereNull('deleted_at'),
            ],
            'remarks' => ['required', 'string', 'max:2000'],
        ];
    }
}
