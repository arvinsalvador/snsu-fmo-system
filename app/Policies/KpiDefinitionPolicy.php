<?php

namespace App\Policies;

use App\Models\KpiDefinition;
use App\Models\User;

class KpiDefinitionPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_kpi_definitions');
    }

    public function view(User $u, KpiDefinition $m): bool
    {
        return $this->viewAny($u);
    }

    public function update(User $u, KpiDefinition $m): bool
    {
        return $u->can('manage_kpi_definitions');
    }

    public function delete(User $u, KpiDefinition $m): bool
    {
        return $u->can('manage_kpi_definitions') && ! $m->is_system;
    }
}
