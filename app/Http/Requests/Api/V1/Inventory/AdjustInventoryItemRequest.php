<?php

namespace App\Http\Requests\Api\V1\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['quantity' => ['required', 'numeric', 'not_in:0'], 'remarks' => ['required', 'string'], 'direction' => ['nullable', Rule::in(['increase', 'decrease'])]];
    }

    protected function passedValidation(): void
    {
        if ($this->input('direction') === 'decrease' && (float) $this->input('quantity') > 0) {
            $this->merge(['quantity' => -1 * (float) $this->input('quantity')]);
        }
    }
}
