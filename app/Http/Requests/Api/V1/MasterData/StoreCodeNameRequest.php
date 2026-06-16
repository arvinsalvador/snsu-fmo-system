<?php

namespace App\Http\Requests\Api\V1\MasterData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCodeNameRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:255', 'unique:'.$this->route()->getController()->modelTable().',code'],
            'name' => ['required', 'string', 'max:255', 'unique:'.$this->route()->getController()->modelTable().',name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
