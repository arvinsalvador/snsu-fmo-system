<?php

namespace App\Policies;

use App\Models\GeneratedReport;
use App\Models\User;

class GeneratedReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_generated_reports');
    }

    public function create(User $user): bool
    {
        return $user->can('generate_reports');
    }

    public function view(User $user, GeneratedReport $report): bool
    {
        return $user->can('view_generated_reports') && ($user->can('manage_report_schedules') || $report->generated_by === $user->id);
    }

    public function download(User $user, GeneratedReport $report): bool
    {
        return $user->can('download_generated_reports') && $this->view($user, $report);
    }

    public function delete(User $user, GeneratedReport $report): bool
    {
        return $user->can('delete_generated_reports') && ! $report->deliveries()->exists();
    }
}
