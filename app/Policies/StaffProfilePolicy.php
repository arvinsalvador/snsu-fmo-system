<?php

namespace App\Policies;

use App\Models\StaffProfile;
use App\Models\User;

class StaffProfilePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Super Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('manage_staff_profiles');
    }

    public function view(User $user, StaffProfile $staffProfile): bool
    {
        return $user->can('manage_staff_profiles') || $staffProfile->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('manage_staff_profiles');
    }

    public function update(User $user, StaffProfile $staffProfile): bool
    {
        return $user->can('manage_staff_profiles');
    }
}
