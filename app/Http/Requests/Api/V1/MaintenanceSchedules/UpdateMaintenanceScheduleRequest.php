<?php

namespace App\Http\Requests\Api\V1\MaintenanceSchedules;

class UpdateMaintenanceScheduleRequest extends StoreMaintenanceScheduleRequest
{
    public function authorize(): bool
    {
        $schedule = $this->route('maintenanceSchedule');

        return $schedule && ($this->user()?->can('update', $schedule) ?? false);
    }

    public function rules(): array
    {
        return collect(parent::rules())
            ->map(fn (array $rules): array => ['sometimes', ...array_values(array_filter($rules, fn (mixed $rule): bool => $rule !== 'required'))])
            ->all();
    }
}
