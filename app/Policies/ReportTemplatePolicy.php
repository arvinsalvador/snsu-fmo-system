<?php

namespace App\Policies;

use App\Models\ReportTemplate;
use App\Models\User;

class ReportTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_report_templates');
    }

    public function view(User $user, ReportTemplate $template): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('manage_report_templates');
    }

    public function update(User $user, ReportTemplate $template): bool
    {
        return $user->can('manage_report_templates');
    }

    public function delete(User $user, ReportTemplate $template): bool
    {
        return $user->can('manage_report_templates') && ! $template->is_system;
    }
}
