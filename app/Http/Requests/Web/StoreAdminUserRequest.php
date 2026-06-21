<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Api\V1\Users\StoreUserRequest;

class StoreAdminUserRequest extends StoreUserRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->boolean('roles_present') && ! $this->has('roles')) {
            $this->merge(['roles' => []]);
        }
    }
}
