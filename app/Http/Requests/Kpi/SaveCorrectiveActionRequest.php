<?php

namespace App\Http\Requests\Kpi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCorrectiveActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_kpi_corrective_actions');
    }

    public function rules(): array
    {
        return ['kpi_target_id' => ['required', 'exists:kpi_targets,id'], 'kpi_evaluation_id' => ['nullable', 'exists:kpi_evaluations,id'], 'title' => ['required', 'string', 'max:160'], 'description' => ['required', 'string', 'max:3000'], 'root_cause' => ['nullable', 'string', 'max:3000'], 'planned_action' => ['required', 'string', 'max:3000'], 'assigned_to' => ['nullable', 'exists:users,id'], 'due_date' => ['nullable', 'date'], 'priority' => ['required', Rule::in(['low', 'normal', 'high', 'critical'])], 'status' => ['nullable', Rule::in(['open', 'in_progress', 'completed', 'cancelled'])], 'completion_notes' => ['nullable', 'string', 'max:3000']];
    }
}
