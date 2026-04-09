<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class WarehouseLocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_warehouse_location');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('view_warehouse_location');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_warehouse_location');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('update_warehouse_location');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('delete_warehouse_location');
    }

    public function restore(User $user): bool
    {
        return $user->hasPermissionTo('restore_warehouse_location');
    }

    public function forceDelete(User $user): bool
    {
        return $user->hasPermissionTo('force_delete_warehouse_location');
    }
}
