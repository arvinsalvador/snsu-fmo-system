<?php

namespace App\Http\Requests\Kpi;

use App\Models\KpiTarget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveKpiTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_kpi_targets');
    }

    public function rules(): array
    {
        return ['kpi_definition_id' => ['required', 'exists:kpi_definitions,id'], 'scope_type' => ['required', Rule::in(['organization', 'building'])], 'scope_id' => ['nullable', 'required_if:scope_type,building', 'exists:buildings,id'], 'period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start'], 'target_value' => ['nullable', 'numeric'], 'minimum_value' => ['nullable', 'numeric'], 'maximum_value' => ['nullable', 'numeric', 'gte:minimum_value'], 'warning_threshold' => ['nullable', 'numeric'], 'critical_threshold' => ['nullable', 'numeric'], 'owner_user_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($q) => $q->where('is_active', true))], 'status' => ['required', Rule::in(['draft', 'active', 'completed', 'cancelled', 'archived'])], 'notes' => ['nullable', 'string', 'max:2000']];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $id = $this->route('kpiTarget')?->id;
            $overlap = KpiTarget::where('kpi_definition_id', $this->integer('kpi_definition_id'))->where('scope_type', $this->input('scope_type'))->where('scope_id', $this->input('scope_id'))->when($id, fn ($q) => $q->whereKeyNot($id))->whereNotIn('status', ['cancelled', 'archived'])->whereDate('period_start', '<=', $this->input('period_end'))->whereDate('period_end', '>=', $this->input('period_start'))->exists();
            if ($overlap) {
                $v->errors()->add('period_start', 'An overlapping target already exists for this KPI and scope.');
            }
        }];
    }
}
