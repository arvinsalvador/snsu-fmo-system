<?php

namespace App\Http\Requests\Api\V1\WorkOrders;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderFollowupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['message' => ['required', 'string', 'max:5000']];
    }
}
