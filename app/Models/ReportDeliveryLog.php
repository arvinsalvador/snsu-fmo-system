<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['generated_report_id', 'report_schedule_id', 'recipient_type', 'recipient_identifier', 'delivery_channel', 'delivery_status', 'delivered_at', 'failure_reason', 'metadata'])]
class ReportDeliveryLog extends Model
{
    protected function casts(): array
    {
        return ['delivered_at' => 'datetime', 'metadata' => 'array'];
    }

    public function generatedReport(): BelongsTo
    {
        return $this->belongsTo(GeneratedReport::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ReportSchedule::class, 'report_schedule_id');
    }
}
