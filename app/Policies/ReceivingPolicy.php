<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class ReceivingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_receiving');
    }

    public function view(User $user): bool
    {
        return $user->can('view_receiving');
    }

    public function create(User $user): bool
    {
        return $user->can('create_receiving');
    }

    public function update(User $user): bool
    {
        return $user->can('update_receiving');
    }

    public function delete(User $user): bool
    {
        return $user->can('delete_receiving');
    }

    public function restore(User $user): bool
    {
        return $user->can('restore_receiving');
    }

    public function forceDelete(User $user): bool
    {
        return $user->can('force_delete_receiving');
    }
}
