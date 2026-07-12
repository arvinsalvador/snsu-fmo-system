<?php

namespace App\Http\Requests\Api\V1\AssetMaintenance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAssetMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
            'maintenance_schedule_id' => ['nullable', 'integer', Rule::exists('maintenance_schedules', 'id')],
            'maintenance_type_id' => ['nullable', 'integer', Rule::exists('maintenance_types', 'id')->whereNull('deleted_at')],
            'review_status' => ['nullable', Rule::in(['pending_review', 'approved', 'correction_requested', 'corrected', 'rejected'])],
            'work_order_id' => ['nullable', 'integer', Rule::exists('work_orders', 'id')->whereNull('deleted_at')],
            'staff_profile_id' => ['nullable', 'integer', Rule::exists('staff_profiles', 'id')->whereNull('deleted_at')],
            'completed_from' => ['nullable', 'date'],
            'completed_to' => ['nullable', 'date', 'after_or_equal:completed_from'],
            'sort' => ['nullable', Rule::in(['completion_date', 'created_at', 'labor_cost'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }
}
