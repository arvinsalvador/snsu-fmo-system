<?php

namespace App\Http\Requests\Reports;

use App\Http\Requests\Api\V1\Reports\ReportFilterRequest;
use Illuminate\Validation\Rule;

class GenerateReportRequest extends ReportFilterRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('generate_reports');
    }

    public function rules(): array
    {
        return [...parent::rules(), 'report_template_id' => ['required', Rule::exists('report_templates', 'id')->where(fn ($q) => $q->where('is_active', true)->whereNull('deleted_at'))], 'output_format' => ['nullable', Rule::in(['pdf', 'csv', 'print_view'])]];
    }
}
