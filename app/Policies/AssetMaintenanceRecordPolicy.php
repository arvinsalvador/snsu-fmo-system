<?php

namespace App\Policies;

use App\Models\AssetMaintenanceRecord;
use App\Models\User;

class AssetMaintenanceRecordPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Super Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_maintenance_records') || $user->can('manage_maintenance_records');
    }

    public function viewReviews(User $user): bool
    {
        return $user->can('view_maintenance_reviews');
    }

    public function view(User $user, AssetMaintenanceRecord $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('manage_maintenance_records');
    }

    public function update(User $user, AssetMaintenanceRecord $record): bool
    {
        return false;
    }

    public function export(User $user): bool
    {
        return $user->can('export_maintenance_records') || $user->can('manage_maintenance_records');
    }

    public function review(User $user, AssetMaintenanceRecord $record): bool
    {
        if ($user->can('review_maintenance_records')) {
            return true;
        }

        return $user->can('view_maintenance_records')
            && ($record->completed_by === $user->id || $record->staffProfile?->user_id === $user->id);
    }

    public function approve(User $user, AssetMaintenanceRecord $record): bool
    {
        return $user->can('approve_maintenance_records') && $record->completed_by !== $user->id;
    }

    public function requestCorrection(User $user, AssetMaintenanceRecord $record): bool
    {
        return $user->can('request_maintenance_corrections') && $record->completed_by !== $user->id;
    }

    public function correct(User $user, AssetMaintenanceRecord $record): bool
    {
        if (! $user->can('correct_maintenance_records') || $record->review_status !== 'correction_requested' || $record->locked_at) {
            return false;
        }

        return $user->can('manage_maintenance_records')
            || $record->completed_by === $user->id
            || $record->staffProfile?->user_id === $user->id;
    }

    public function resubmit(User $user, AssetMaintenanceRecord $record): bool
    {
        return $user->can('correct_maintenance_records')
            && $record->review_status === 'corrected'
            && ($user->can('manage_maintenance_records') || $record->corrected_by === $user->id || $record->completed_by === $user->id);
    }

    public function reject(User $user, AssetMaintenanceRecord $record): bool
    {
        return $user->can('reject_maintenance_records') && $record->completed_by !== $user->id;
    }

    public function reopen(User $user, AssetMaintenanceRecord $record): bool
    {
        return $user->can('reopen_maintenance_records');
    }
}
