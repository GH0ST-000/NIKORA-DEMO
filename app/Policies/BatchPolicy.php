<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class BatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_batch');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('view_batch');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_batch');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('update_batch');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('delete_batch');
    }

    public function restore(User $user): bool
    {
        return $user->hasPermissionTo('restore_batch');
    }

    public function forceDelete(User $user): bool
    {
        return $user->hasPermissionTo('force_delete_batch');
    }
}
