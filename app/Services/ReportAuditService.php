<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReportAuditService
{
    public function record(string $event, Model $model, array $old = [], array $new = [], array $metadata = []): void
    {
        DB::table('report_audit_logs')->insert(['actor_id' => auth()->id(), 'event' => $event, 'auditable_type' => $model::class, 'auditable_id' => $model->getKey(), 'old_values' => $old ? json_encode($old) : null, 'new_values' => $new ? json_encode($new) : null, 'metadata' => $metadata ? json_encode($metadata) : null, 'created_at' => now(), 'updated_at' => now()]);
    }
}
