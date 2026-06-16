<?php

namespace App\Policies;

use App\Models\Skill;
use App\Models\User;

class SkillPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Super Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('manage_skills') || $user->can('manage_staff_profiles');
    }

    public function view(User $user, Skill $skill): bool
    {
        return $user->can('manage_skills') || $user->can('manage_staff_profiles');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_skills');
    }

    public function update(User $user, Skill $skill): bool
    {
        return $user->can('manage_skills');
    }
}
