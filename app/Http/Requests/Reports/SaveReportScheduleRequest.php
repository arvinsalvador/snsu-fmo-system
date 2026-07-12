<?php

namespace App\Http\Requests\Reports;

use App\Models\ReportSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveReportScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_report_schedules');
    }

    public function rules(): array
    {
        return ['report_template_id' => ['required', 'exists:report_templates,id'], 'name' => ['required', 'string', 'max:150'], 'frequency' => ['required', Rule::in(ReportSchedule::FREQUENCIES)], 'timezone' => ['required', 'timezone'], 'day_of_week' => ['nullable', 'integer', 'between:0,6'], 'day_of_month' => ['nullable', 'integer', 'between:1,28'], 'run_time' => ['required', 'date_format:H:i'], 'date_range_mode' => ['required', Rule::in(ReportSchedule::DATE_MODES)], 'custom_filters' => ['nullable', 'array'], 'output_format' => ['required', Rule::in(['pdf', 'csv', 'print_view'])], 'delivery_method' => ['required', Rule::in(['database_notification', 'download_only'])], 'is_active' => ['boolean'], 'recipient_user_ids' => ['nullable', 'array'], 'recipient_user_ids.*' => ['integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('is_active', true))]];
    }
}
