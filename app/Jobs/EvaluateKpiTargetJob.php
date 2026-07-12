<?php

namespace App\Jobs;

use App\Models\KpiTarget;
use App\Services\KpiEvaluationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EvaluateKpiTargetJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $targetId) {}

    public function handle(KpiEvaluationService $s): void
    {
        $t = KpiTarget::find($this->targetId);
        if (! $t || $t->status !== 'active' || $t->evaluations()->whereDate('evaluation_date', today())->exists()) {
            return;
        }$s->evaluate($t);
    }
}
