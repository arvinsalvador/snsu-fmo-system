<?php

namespace App\Observers;

use App\Models\KpiCorrectiveAction;
use App\Models\KpiTarget;
use App\Notifications\KpiAssignmentNotification;

class KpiAssignmentObserver
{
    public function created(object $m): void
    {
        $this->notify($m);
    }

    public function updated(object $m): void
    {
        if ($m->wasChanged(['owner_user_id', 'assigned_to'])) {
            $this->notify($m);
        }
    }

    private function notify(object $m): void
    {
        if ($m instanceof KpiTarget && $m->owner) {
            $m->owner->notify(new KpiAssignmentNotification('target', $m->definition->name, route('admin.kpi-targets.show', $m)));
        }if ($m instanceof KpiCorrectiveAction && $m->assignee) {
            $m->assignee->notify(new KpiAssignmentNotification('corrective_action', $m->title, route('admin.kpi-corrective-actions.index')));
        }
    }
}
