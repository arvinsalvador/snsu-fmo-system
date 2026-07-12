<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['uuid', 'report_template_id', 'report_type', 'title', 'reporting_period_start', 'reporting_period_end', 'filters', 'summary_data', 'file_path', 'file_name', 'file_format', 'generation_status', 'generated_by', 'generated_at', 'failure_reason', 'checksum', 'metadata'])]
class GeneratedReport extends Model
{
    use HasUuid, SoftDeletes;

    protected $hidden = ['file_path'];

    protected function casts(): array
    {
        return ['filters' => 'array', 'summary_data' => 'array', 'metadata' => 'array', 'reporting_period_start' => 'date', 'reporting_period_end' => 'date', 'generated_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ReportDeliveryLog::class);
    }
}
