<?php

namespace App\Http\Requests\Api\V1\MasterData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCodeNameRequest extends FormRequest
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
        $table = $this->route()->getController()->modelTable();
        $id = $this->route()->parameter($this->route()->getController()->routeParameter())->id;

        return [
            'code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique($table, 'code')->ignore($id)],
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique($table, 'name')->ignore($id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
