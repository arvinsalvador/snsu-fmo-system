<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['first_name' => ['sometimes', 'required', 'string', 'max:100'], 'middle_name' => ['nullable', 'string', 'max:100'], 'last_name' => ['sometimes', 'required', 'string', 'max:100'], 'suffix' => ['nullable', 'string', 'max:20'], 'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($this->user()->id)], 'mobile_number' => ['nullable', 'string', 'max:30']];
    }
}
