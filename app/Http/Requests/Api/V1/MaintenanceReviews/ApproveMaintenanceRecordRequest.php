<?php

namespace App\Http\Requests\Api\V1\MaintenanceReviews;

use Illuminate\Foundation\Http\FormRequest;

class ApproveMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approve', $this->route('record')) ?? false;
    }

    public function rules(): array
    {
        return ['review_notes' => ['nullable', 'string', 'max:5000']];
    }
}
