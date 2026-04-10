<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AppNotification;
use App\Models\User;

final class AppNotificationPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, AppNotification $appNotification): bool
    {
        return $user->id === $appNotification->user_id;
    }

    public function update(User $user, AppNotification $appNotification): bool
    {
        return $user->id === $appNotification->user_id;
    }

    public function delete(User $user, AppNotification $appNotification): bool
    {
        return $user->id === $appNotification->user_id;
    }

    public function create(): bool
    {
        return true;
    }
}
