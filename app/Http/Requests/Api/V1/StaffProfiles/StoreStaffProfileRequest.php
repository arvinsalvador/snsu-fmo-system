<?php

namespace App\Http\Requests\Api\V1\StaffProfiles;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStaffProfileRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id', 'unique:staff_profiles,user_id'],
            'employee_code' => ['required', 'string', 'max:255', 'unique:staff_profiles,employee_code'],
            'position' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'employment_status' => ['required', 'string', 'max:255'],
            'availability_status' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'skill_ids' => ['sometimes', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
        ];
    }
}
