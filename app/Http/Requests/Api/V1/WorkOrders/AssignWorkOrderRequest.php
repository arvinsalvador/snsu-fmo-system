<?php

namespace App\Http\Requests\Api\V1\WorkOrders;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignWorkOrderRequest extends FormRequest
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
            'assigned_staff_id' => [
                'required',
                'integer',
                Rule::exists('staff_profiles', 'id')->whereNull('deleted_at'),
            ],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
