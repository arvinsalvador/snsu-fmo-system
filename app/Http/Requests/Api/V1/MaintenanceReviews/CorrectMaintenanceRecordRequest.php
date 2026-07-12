<?php

namespace App\Http\Requests\Api\V1\MaintenanceReviews;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CorrectMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('correct', $this->route('record')) ?? false;
    }

    public function rules(): array
    {
        return [
            'maintenance_type_id' => ['sometimes', 'nullable', Rule::exists('maintenance_types', 'id')->whereNull('deleted_at')],
            'maintenance_date' => ['sometimes', 'required', 'date'],
            'performed_by' => ['sometimes', 'nullable', 'string', 'max:255'],
            'staff_profile_id' => ['sometimes', 'nullable', Rule::exists('staff_profiles', 'id')->whereNull('deleted_at')],
            'findings' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'actions_taken' => ['sometimes', 'required', 'string', 'max:10000'],
            'remarks' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'labor_cost' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'total_cost' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'next_maintenance_date' => ['sometimes', 'nullable', 'date', 'after:maintenance_date'],
            'correction_notes' => ['nullable', 'string', 'max:5000'],
            'evidence' => ['nullable', 'array', 'max:20'],
            'evidence.*' => ['string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $fields = collect(array_keys($this->rules()))->reject(fn (string $field) => in_array($field, ['correction_notes', 'evidence', 'evidence.*'], true));
            if (! $fields->contains(fn (string $field): bool => array_key_exists($field, $this->all()))) {
                $validator->errors()->add('correction', 'At least one maintenance field must be corrected.');
            }
        }];
    }
}
