<?php

namespace App\Observers;

use App\Services\ReportAuditService;
use Illuminate\Database\Eloquent\Model;

class ReportAuditObserver
{
    public function created(Model $model): void
    {
        app(ReportAuditService::class)->record('created', $model, [], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        app(ReportAuditService::class)->record('updated', $model, $model->getOriginal(), $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        app(ReportAuditService::class)->record('archived', $model, $model->getOriginal());
    }
}
