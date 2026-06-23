<?php

namespace App\Http\Requests\Api\V1\Assets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'maintenance_type_id' => ['required', Rule::exists('maintenance_types', 'id')->whereNull('deleted_at')],
            'maintenance_date' => ['required', 'date'],
            'performed_by' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'findings' => ['nullable', 'string'],
            'actions_taken' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'next_maintenance_date' => ['nullable', 'date', 'after_or_equal:maintenance_date'],
        ];
    }
}
