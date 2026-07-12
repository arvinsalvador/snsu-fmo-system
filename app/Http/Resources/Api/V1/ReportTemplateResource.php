<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['uuid' => $this->uuid, 'name' => $this->name, 'slug' => $this->slug, 'description' => $this->description, 'report_type' => $this->report_type, 'output_format' => $this->output_format, 'default_filters' => $this->default_filters, 'included_sections' => $this->included_sections, 'orientation' => $this->orientation, 'paper_size' => $this->paper_size, 'is_active' => $this->is_active, 'is_system' => $this->is_system];
    }
}
