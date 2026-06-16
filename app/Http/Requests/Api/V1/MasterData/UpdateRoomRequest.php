<?php

namespace App\Http\Requests\Api\V1\MasterData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
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
        $room = $this->route('room');
        $floorId = $this->integer('floor_id') ?: $room->floor_id;

        return [
            'floor_id' => ['sometimes', 'required', 'integer', 'exists:floors,id'],
            'room_name' => ['sometimes', 'required', 'string', 'max:255'],
            'room_code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('rooms', 'room_code')->where('floor_id', $floorId)->ignore($room->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
