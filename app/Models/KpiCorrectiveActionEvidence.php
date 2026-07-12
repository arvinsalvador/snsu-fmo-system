<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kpi_corrective_action_id', 'file_path', 'file_name', 'mime_type', 'file_size', 'uploaded_by', 'uploaded_at', 'description'])]
class KpiCorrectiveActionEvidence extends Model
{
    protected $hidden = ['file_path'];

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime'];
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(KpiCorrectiveAction::class, 'kpi_corrective_action_id');
    }
}
