<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['uuid', 'report_template_id', 'name', 'frequency', 'timezone', 'day_of_week', 'day_of_month', 'run_time', 'date_range_mode', 'custom_filters', 'output_format', 'delivery_method', 'is_active', 'last_run_at', 'next_run_at', 'processing_key', 'consecutive_failures', 'created_by', 'updated_by'])]
class ReportSchedule extends Model
{
    use HasUuid, SoftDeletes;

    public const FREQUENCIES = ['daily', 'weekly', 'monthly', 'quarterly', 'annually'];

    public const DATE_MODES = ['previous_day', 'previous_week', 'previous_month', 'previous_quarter', 'previous_year', 'current_month_to_date', 'current_year_to_date', 'custom'];

    protected function casts(): array
    {
        return ['custom_filters' => 'array', 'is_active' => 'boolean', 'last_run_at' => 'datetime', 'next_run_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(ReportScheduleRecipient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
