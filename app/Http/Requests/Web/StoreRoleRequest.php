<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->boolean('permissions_present') && ! $this->has('permissions')) {
            $this->merge(['permissions' => []]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manage_roles') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->where('guard_name', 'web')],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ];
    }
}
