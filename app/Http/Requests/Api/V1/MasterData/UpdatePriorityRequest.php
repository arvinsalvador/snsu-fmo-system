<?php

namespace App\Http\Requests\Api\V1\MasterData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePriorityRequest extends FormRequest
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
        $priority = $this->route('priority');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('priorities', 'name')->ignore($priority->id)],
            'level' => ['sometimes', 'required', 'integer', 'min:1', 'max:255', Rule::unique('priorities', 'level')->ignore($priority->id)],
            'color' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
