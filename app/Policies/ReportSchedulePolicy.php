<?php

namespace App\Policies;

use App\Models\ReportSchedule;
use App\Models\User;

class ReportSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_report_schedules');
    }

    public function view(User $user, ReportSchedule $schedule): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('manage_report_schedules');
    }

    public function update(User $user, ReportSchedule $schedule): bool
    {
        return $user->can('manage_report_schedules');
    }

    public function run(User $user, ReportSchedule $schedule): bool
    {
        return $user->can('run_report_schedules');
    }

    public function delete(User $user, ReportSchedule $schedule): bool
    {
        return $user->can('manage_report_schedules');
    }
}
