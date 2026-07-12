<?php

namespace App\Policies;

use App\Models\KpiCorrectiveAction;
use App\Models\User;

class KpiCorrectiveActionPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_kpi_corrective_actions');
    }

    public function view(User $u, KpiCorrectiveAction $m): bool
    {
        return $u->can('view_kpi_corrective_actions') && ($u->can('manage_kpi_corrective_actions') || $m->assigned_to === $u->id);
    }

    public function create(User $u): bool
    {
        return $u->can('manage_kpi_corrective_actions');
    }

    public function update(User $u, KpiCorrectiveAction $m): bool
    {
        return $u->can('manage_kpi_corrective_actions') || $m->assigned_to === $u->id;
    }
}
