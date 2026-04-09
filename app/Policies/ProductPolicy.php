<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_product');
    }

    public function view(User $user): bool
    {
        return $user->can('view_product');
    }

    public function create(User $user): bool
    {
        return $user->can('create_product');
    }

    public function update(User $user): bool
    {
        return $user->can('update_product');
    }

    public function delete(User $user): bool
    {
        return $user->can('delete_product');
    }

    public function restore(User $user): bool
    {
        return $user->can('restore_product');
    }

    public function forceDelete(User $user): bool
    {
        return $user->can('force_delete_product');
    }
}
