<?php

namespace App\Http\Requests\Api\V1\AssetMaintenance;

use App\Models\AssetMaintenanceRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AssetMaintenanceRecord::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', Rule::exists('assets', 'id')],
            'maintenance_type_id' => ['nullable', 'integer', Rule::exists('maintenance_types', 'id')->whereNull('deleted_at')],
            'maintenance_schedule_id' => ['nullable', 'integer', Rule::exists('maintenance_schedules', 'id')],
            'work_order_id' => ['nullable', 'integer', Rule::exists('work_orders', 'id')->whereNull('deleted_at')],
            'staff_profile_id' => ['nullable', 'integer', Rule::exists('staff_profiles', 'id')->whereNull('deleted_at')],
            'completion_date' => ['required', 'date'],
            'maintenance_date' => ['nullable', 'date'],
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
