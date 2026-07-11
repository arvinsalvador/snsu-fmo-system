<?php

namespace App\Http\Requests\Api\V1\MaintenanceSchedules;

use App\Models\MaintenanceSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteMaintenanceScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $schedule = $this->route('maintenanceSchedule');

        return $schedule instanceof MaintenanceSchedule && ($this->user()?->can('complete', $schedule) ?? false);
    }

    public function rules(): array
    {
        return [
            'completion_date' => ['required', 'date'],
            'maintenance_type_id' => ['nullable', Rule::exists('maintenance_types', 'id')->whereNull('deleted_at')],
            'work_order_id' => ['nullable', Rule::exists('work_orders', 'id')->whereNull('deleted_at')],
            'staff_profile_id' => ['nullable', Rule::exists('staff_profiles', 'id')->whereNull('deleted_at')],
            'performed_by' => ['nullable', 'string', 'max:255'],
            'findings' => ['nullable', 'string', 'max:10000'],
            'actions_taken' => ['required', 'string', 'max:10000'],
            'remarks' => ['nullable', 'string', 'max:10000'],
            'labor_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'total_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'next_maintenance_date' => ['nullable', 'date', 'after:completion_date'],
        ];
    }
}
