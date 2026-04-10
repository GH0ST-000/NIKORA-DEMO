<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use App\Services\ActionLogService;
use App\Services\NotificationService;
use App\Support\ApiActor;

final readonly class AssignRoleToUserAction
{
    public function __construct(
        private ActionLogService $actionLogService,
        private NotificationService $notificationService,
    ) {}

    public function execute(User $user, string $roleName): User
    {
        $user->assignRole($roleName);
        $user->load('roles');

        $this->actionLogService->log(
            actionType: 'update',
            entityType: 'user',
            entityId: $user->id,
            module: 'users',
            description: sprintf("Role '%s' assigned to User #%d", $roleName, $user->id),
            metadata: ['role' => $roleName],
        );

        $this->notificationService->notifyUserRoleAssigned($user, $roleName, ApiActor::id());

        return $user;
    }
}
