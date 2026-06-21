<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Api\V1\StaffProfiles\UpdateStaffProfileRequest;

class UpdateAdminStaffProfileRequest extends UpdateStaffProfileRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->boolean('skills_present') && ! $this->has('skill_ids')) {
            $this->merge(['skill_ids' => []]);
        }
    }
}
