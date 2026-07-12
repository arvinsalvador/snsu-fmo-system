<?php

namespace App\Http\Requests\Api\V1\MaintenanceReviews;

use Illuminate\Foundation\Http\FormRequest;

class RequestMaintenanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('requestCorrection', $this->route('record')) ?? false;
    }

    public function rules(): array
    {
        return [
            'correction_reason' => ['required', 'string', 'max:5000'],
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
