<?php

namespace App\Policies;

use App\Models\ReportDeliveryLog;
use App\Models\User;

class ReportDeliveryLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_report_delivery_logs');
    }

    public function view(User $user, ReportDeliveryLog $log): bool
    {
        return $this->viewAny($user);
    }
}
