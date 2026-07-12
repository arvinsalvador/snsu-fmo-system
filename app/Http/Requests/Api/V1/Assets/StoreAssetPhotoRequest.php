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
            'photo' => ['nullable', 'required_without:image_path', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
            'image_path' => ['nullable', 'required_without:photo', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'original_name' => ['nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
