<?php

namespace App\Http\Requests\Api\V1\MaintenanceSchedules;

use App\Models\MaintenanceSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MaintenanceSchedule::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', Rule::exists('assets', 'id')->whereNull('deleted_at')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'frequency' => ['required', Rule::in(array_keys(MaintenanceSchedule::FREQUENCIES))],
            'next_due_date' => ['required', 'date'],
            'last_completed_date' => ['nullable', 'date', 'before_or_equal:next_due_date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
