<?php

namespace App\Http\Requests\Api\V1\MasterData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
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
            'floor_id' => ['required', 'integer', 'exists:floors,id'],
            'room_name' => ['required', 'string', 'max:255'],
            'room_code' => ['required', 'string', 'max:255', Rule::unique('rooms', 'room_code')->where('floor_id', $this->integer('floor_id'))],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
