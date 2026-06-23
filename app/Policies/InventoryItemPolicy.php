<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

class InventoryItemPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Super Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_inventory') || $user->can('manage_inventory');
    }

    public function view(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->can('view_inventory') || $user->can('manage_inventory');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_inventory');
    }

    public function update(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->can('manage_inventory');
    }

    public function adjust(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->can('adjust_inventory') || $user->can('manage_inventory');
    }

    public function export(User $user): bool
    {
        return $user->can('export_inventory') || $user->can('manage_inventory');
    }
}
