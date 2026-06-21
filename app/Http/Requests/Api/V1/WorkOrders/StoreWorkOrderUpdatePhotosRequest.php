<?php

namespace App\Http\Requests\Api\V1\WorkOrders;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderUpdatePhotosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'captions' => ['sometimes', 'array', 'max:10'],
            'captions.*' => ['nullable', 'string', 'max:500'],
        ];
    }
}
