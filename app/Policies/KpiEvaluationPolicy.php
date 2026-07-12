<?php

namespace App\Policies;

use App\Models\KpiEvaluation;
use App\Models\User;

class KpiEvaluationPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_kpi_evaluations');
    }

    public function view(User $u, KpiEvaluation $m): bool
    {
        return $u->can('view_kpi_evaluations');
    }
}
