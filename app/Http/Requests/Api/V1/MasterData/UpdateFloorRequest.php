<?php

namespace App\Http\Requests\Api\V1\MasterData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFloorRequest extends FormRequest
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
        $floor = $this->route('floor');
        $buildingId = $this->integer('building_id') ?: $floor->building_id;

        return [
            'building_id' => ['sometimes', 'required', 'integer', 'exists:buildings,id'],
            'floor_name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('floors', 'floor_name')->where('building_id', $buildingId)->ignore($floor->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
