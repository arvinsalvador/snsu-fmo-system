<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Api\V1\Users\UpdateUserRequest;
use Illuminate\Validation\Validator;

class UpdateAdminUserRequest extends UpdateUserRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->boolean('roles_present') && ! $this->has('roles')) {
            $this->merge(['roles' => []]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $target = $this->route('user');
            if ($this->user()?->is($target) && $target->hasRole('Super Admin') && ! in_array('Super Admin', $this->input('roles', []), true)) {
                $validator->errors()->add('roles', 'You cannot remove your own Super Admin role.');
            }
        });
    }
}
