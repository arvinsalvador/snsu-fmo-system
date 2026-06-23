<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_notifications') || $user->can('manage_notifications');
    }

    public function update(User $user, DatabaseNotification $notification): bool
    {
        return $this->viewAny($user) && $notification->notifiable_type === User::class && (int) $notification->notifiable_id === $user->id;
    }
}
