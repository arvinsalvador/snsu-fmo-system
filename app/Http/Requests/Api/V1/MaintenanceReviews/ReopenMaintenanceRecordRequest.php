<?php

namespace App\Http\Requests\Api\V1\MaintenanceReviews;

use Illuminate\Foundation\Http\FormRequest;

class ReopenMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reopen', $this->route('record')) ?? false;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:5000']];
    }
}
