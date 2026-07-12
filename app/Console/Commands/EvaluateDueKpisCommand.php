<?php

namespace App\Console\Commands;

use App\Jobs\EvaluateKpiTargetJob;
use App\Models\KpiTarget;
use Illuminate\Console\Command;

class EvaluateDueKpisCommand extends Command
{
    protected $signature = 'kpis:evaluate-due';

    protected $description = 'Queue active KPI targets for deterministic daily evaluation';

    public function handle(): int
    {
        KpiTarget::where('status', 'active')->whereDate('period_start', '<=', today())->whereDoesntHave('evaluations', fn ($q) => $q->whereDate('evaluation_date', today()))->pluck('id')->each(fn ($id) => EvaluateKpiTargetJob::dispatch($id));

        return self::SUCCESS;
    }
}
