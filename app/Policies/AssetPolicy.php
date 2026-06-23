<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Super Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_assets') || $user->can('manage_assets');
    }

    public function view(User $user, Asset $asset): bool
    {
        return $user->can('view_assets') || $user->can('manage_assets');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_assets');
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->can('manage_assets');
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->can('manage_assets');
    }

    public function export(User $user): bool
    {
        return $user->can('export_assets') || $user->can('manage_assets');
    }
}
