<?php

namespace App\Http\Requests\Api\V1\StaffProfiles;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncStaffSkillsRequest extends FormRequest
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
            'skill_ids' => ['required', 'array'],
            'skill_ids.*' => ['integer', 'distinct', 'exists:skills,id'],
        ];
    }
}
