<?php

namespace App\Policies;

use App\Models\KpiTarget;
use App\Models\User;

class KpiTargetPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_kpi_targets');
    }

    public function view(User $u, KpiTarget $m): bool
    {
        return $u->can('view_kpi_targets') && ($u->can('manage_kpi_targets') || $m->owner_user_id === $u->id);
    }

    public function create(User $u): bool
    {
        return $u->can('manage_kpi_targets');
    }

    public function update(User $u, KpiTarget $m): bool
    {
        return $u->can('manage_kpi_targets') && ! in_array($m->status, ['completed', 'archived'], true);
    }

    public function delete(User $u, KpiTarget $m): bool
    {
        return $u->can('manage_kpi_targets') && $m->evaluations()->doesntExist();
    }

    public function evaluate(User $u, KpiTarget $m): bool
    {
        return $u->can('evaluate_kpis');
    }
}
