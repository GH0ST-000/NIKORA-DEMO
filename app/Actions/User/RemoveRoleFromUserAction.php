<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Role;

final readonly class RemoveRoleFromUserAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    public function execute(User $user, string $roleName): User
    {
        try {
            $role = Role::findByName($roleName, 'web');
            $user->removeRole($role);

            $this->actionLogService->log(
                actionType: 'update',
                entityType: 'user',
                entityId: $user->id,
                module: 'users',
                description: sprintf("Role '%s' removed from User #%d", $roleName, $user->id),
                metadata: ['role' => $roleName],
            );

            $this->notificationService->notifyUserRoleRemoved($user, $roleName, ApiActor::id());
        } catch (RoleDoesNotExist) {
        }

        $user->load('roles');

        return $user;
    }
}
