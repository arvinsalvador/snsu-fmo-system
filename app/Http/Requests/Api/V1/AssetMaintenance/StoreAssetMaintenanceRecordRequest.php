<?php

namespace App\Http\Requests\Api\V1\AssetMaintenance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', Rule::exists('assets', 'id')],
            'maintenance_schedule_id' => ['nullable', 'integer', Rule::exists('maintenance_schedules', 'id')],
            'work_order_id' => ['nullable', 'integer', Rule::exists('work_orders', 'id')->whereNull('deleted_at')],
            'staff_profile_id' => ['nullable', 'integer', Rule::exists('staff_profiles', 'id')->whereNull('deleted_at')],
            'completion_date' => ['required', 'date'],
            'findings' => ['nullable', 'string', 'max:10000'],
            'actions_taken' => ['required', 'string', 'max:10000'],
            'remarks' => ['nullable', 'string', 'max:10000'],
            'labor_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ];
    }
}
