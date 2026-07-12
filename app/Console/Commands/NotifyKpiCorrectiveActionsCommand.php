<?php

namespace App\Console\Commands;

use App\Models\KpiCorrectiveAction;
use App\Notifications\KpiAssignmentNotification;
use Illuminate\Console\Command;

class NotifyKpiCorrectiveActionsCommand extends Command
{
    protected $signature = 'kpis:notify-corrective-actions';

    protected $description = 'Send deduplicated due and overdue KPI corrective action alerts';

    public function handle(): int
    {
        KpiCorrectiveAction::with('assignee')->whereIn('status', ['open', 'in_progress'])->whereNotNull('assigned_to')->whereBetween('due_date', [today(), today()->addDays(3)])->whereNull('due_soon_notified_at')->each(function ($a) {
            $a->assignee->notify(new KpiAssignmentNotification('corrective_action_due', $a->title, route('admin.kpi-corrective-actions.index')));
            $a->updateQuietly(['due_soon_notified_at' => now()]);
        });
        KpiCorrectiveAction::with('assignee')->whereIn('status', ['open', 'in_progress'])->whereDate('due_date', '<', today())->whereNull('overdue_notified_at')->each(function ($a) {
            $a->assignee?->notify(new KpiAssignmentNotification('corrective_action_overdue', $a->title, route('admin.kpi-corrective-actions.index')));
            $a->updateQuietly(['overdue_notified_at' => now()]);
        });

        return self::SUCCESS;
    }
}
