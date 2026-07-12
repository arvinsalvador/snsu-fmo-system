<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneratedReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['uuid' => $this->uuid, 'title' => $this->title, 'report_type' => $this->report_type, 'period' => ['start' => $this->reporting_period_start?->toDateString(), 'end' => $this->reporting_period_end?->toDateString()], 'filters' => $this->filters, 'summary_data' => $this->summary_data, 'file_name' => $this->file_name, 'file_format' => $this->file_format, 'generation_status' => $this->generation_status, 'generated_at' => $this->generated_at?->toIso8601String(), 'failure_reason' => $this->failure_reason, 'checksum' => $this->checksum, 'integrity_url' => $this->when($this->generation_status === 'completed', route('api.v1.generated-reports.verify', $this->resource)), 'download_url' => $this->when($this->generation_status === 'completed', route('api.v1.generated-reports.download', $this->resource))];
    }
}
