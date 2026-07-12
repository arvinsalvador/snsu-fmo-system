<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['uuid' => $this->uuid, 'name' => $this->name, 'template' => new ReportTemplateResource($this->whenLoaded('template')), 'frequency' => $this->frequency, 'timezone' => $this->timezone, 'day_of_week' => $this->day_of_week, 'day_of_month' => $this->day_of_month, 'run_time' => $this->run_time, 'date_range_mode' => $this->date_range_mode, 'custom_filters' => $this->custom_filters, 'output_format' => $this->output_format, 'delivery_method' => $this->delivery_method, 'is_active' => $this->is_active, 'last_run_at' => $this->last_run_at?->toIso8601String(), 'next_run_at' => $this->next_run_at?->toIso8601String(), 'consecutive_failures' => $this->consecutive_failures, 'recipients' => $this->whenLoaded('recipients', fn () => $this->recipients->map(fn ($r) => ['user_id' => $r->user_id, 'name' => $r->user?->name, 'channel' => $r->delivery_channel]))];
    }
}
