<?php

namespace App\Http\Requests\Api\V1\WorkOrders;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTeamWorkOrderRequest extends FormRequest
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
            'staff_ids' => ['required', 'array', 'min:2', 'max:20'],
            'staff_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('staff_profiles', 'id')->whereNull('deleted_at'),
            ],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
