<?php

namespace App\Http\Requests\Api\V1\MaintenanceReviews;

use Illuminate\Foundation\Http\FormRequest;

class RejectMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reject', $this->route('record')) ?? false;
    }

    public function rules(): array
    {
        return ['rejection_reason' => ['required', 'string', 'max:5000']];
    }
}
