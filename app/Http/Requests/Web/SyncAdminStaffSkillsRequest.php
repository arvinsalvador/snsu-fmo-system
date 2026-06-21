<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Api\V1\StaffProfiles\SyncStaffSkillsRequest;

class SyncAdminStaffSkillsRequest extends SyncStaffSkillsRequest
{
    public function rules(): array
    {
        return [
            'skill_ids' => ['present', 'array'],
            'skill_ids.*' => ['integer', 'distinct', 'exists:skills,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('skills_present') && ! $this->has('skill_ids')) {
            $this->merge(['skill_ids' => []]);
        }
    }
}
