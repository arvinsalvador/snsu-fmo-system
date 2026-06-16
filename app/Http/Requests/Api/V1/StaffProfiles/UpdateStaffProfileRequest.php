<?php

namespace App\Http\Requests\Api\V1\StaffProfiles;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffProfileRequest extends FormRequest
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
        $profileId = $this->route('staffProfile')?->id;

        return [
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id', Rule::unique('staff_profiles', 'user_id')->ignore($profileId)],
            'employee_code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('staff_profiles', 'employee_code')->ignore($profileId)],
            'position' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'employment_status' => ['sometimes', 'required', 'string', 'max:255'],
            'availability_status' => ['sometimes', 'required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'skill_ids' => ['sometimes', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
        ];
    }
}
