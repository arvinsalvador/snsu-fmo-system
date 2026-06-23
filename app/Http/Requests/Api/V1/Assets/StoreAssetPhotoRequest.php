<?php

namespace App\Http\Requests\Api\V1\Assets;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image_path' => ['required', 'string', 'max:2048'],
            'caption' => ['nullable', 'string', 'max:255'],
            'original_name' => ['nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
