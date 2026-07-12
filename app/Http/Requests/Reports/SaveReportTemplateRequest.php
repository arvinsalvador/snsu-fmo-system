<?php

namespace App\Http\Requests\Reports;

use App\Models\ReportTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveReportTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_report_templates');
    }

    public function rules(): array
    {
        $id = $this->route('reportTemplate')?->id;

        return ['name' => ['required', 'string', 'max:150'], 'slug' => ['required', 'alpha_dash', 'max:150', Rule::unique('report_templates')->ignore($id)], 'description' => ['nullable', 'string', 'max:1000'], 'report_type' => ['required', Rule::in(ReportTemplate::TYPES)], 'output_format' => ['required', Rule::in(ReportTemplate::FORMATS)], 'included_sections' => ['required', 'array', 'min:1'], 'included_sections.*' => [Rule::in(ReportTemplate::SECTIONS)], 'orientation' => ['required', Rule::in(['portrait', 'landscape'])], 'paper_size' => ['required', Rule::in(['a4', 'letter', 'legal'])], 'is_active' => ['boolean']];
    }
}
