<?php

declare(strict_types=1);

use App\Enums\NotificationModule;
use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\User;
use App\Policies\AppNotificationPolicy;
use App\Services\NotificationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Middleware\Authenticate;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);

    $this->user = User::factory()->create();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Recall Admin');
});

describe('GET /api/notifications', function (): void {
    test('requires authentication', function (): void {
        $this->getJson('/api/notifications')->assertUnauthorized();
    });

    test('returns paginated notifications for authenticated user', function (): void {
        AppNotification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'module' => NotificationModule::Chat->value,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/notifications?per_page=10');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    test('filters by module', function (): void {
        AppNotification::factory()->create([
            'user_id' => $this->user->id,
            'module' => NotificationModule::Chat->value,
        ]);
        AppNotification::factory()->create([
            'user_id' => $this->user->id,
            'module' => NotificationModule::Products->value,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/notifications?module=chat');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.module', 'chat');
    });
});

describe('GET /api/notifications/unread-count', function (): void {
    test('returns unread count', function (): void {
        AppNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
        AppNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
    });
});

describe('PATCH /api/notifications/{notification}/read', function (): void {
    test('marks notification as read', function (): void {
        $notification = AppNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $this->actingAs($this->user, 'api')
            ->patchJson(sprintf('/api/notifications/%d/read', $notification->id))
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        expect($notification->fresh()->is_read)->toBeTrue();
    });

    test('returns 404 for another users notification', function (): void {
        $other = User::factory()->create();
        $notification = AppNotification::factory()->create(['user_id' => $other->id]);

        $this->actingAs($this->user, 'api')
            ->patchJson(sprintf('/api/notifications/%d/read', $notification->id))
            ->assertNotFound();
    });
});

describe('PATCH /api/notifications/read-all', function (): void {
    test('marks all as read', function (): void {
        AppNotification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $this->actingAs($this->user, 'api')
            ->patchJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('updated_count', 3);

        expect(AppNotification::query()->where('user_id', $this->user->id)->where('is_read', false)->count())->toBe(0);
    });
});

describe('DELETE /api/notifications/{notification}', function (): void {
    test('deletes own notification', function (): void {
        $notification = AppNotification::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user, 'api')
            ->deleteJson('/api/notifications/'.$notification->id)
            ->assertOk();

        expect(AppNotification::query()->find($notification->id))->toBeNull();
    });
});

describe('POST /api/notifications', function (): void {
    test('non-admin cannot create non-chat notification', function (): void {
        $target = User::factory()->create();

        $this->actingAs($this->user, 'api')
            ->postJson('/api/notifications', [
                'user_id' => $target->id,
                'module' => NotificationModule::Products->value,
                'type' => NotificationType::ProductCreated->value,
                'title' => 'Test',
                'message' => 'Body',
            ])
            ->assertForbidden();
    });

    test('admin cannot target non-admin with non-chat module', function (): void {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/notifications', [
                'user_id' => $this->user->id,
                'module' => NotificationModule::Products->value,
                'type' => NotificationType::ProductCreated->value,
                'title' => 'Test',
                'message' => 'Body',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['module']);
    });

    test('allows chat notification for non-admin recipient', function (): void {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/notifications', [
                'user_id' => $this->user->id,
                'module' => NotificationModule::Chat->value,
                'type' => NotificationType::ChatNewMessage->value,
                'title' => 'Hello',
                'message' => 'Test message',
            ])
            ->assertCreated();

        expect(AppNotification::query()->where('user_id', $this->user->id)->count())->toBe(1);
    });

    test('store uses explicit sender_id and normalizes data keys', function (): void {
        $sender = User::factory()->create();

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/notifications', [
                'user_id' => $this->user->id,
                'module' => NotificationModule::Chat->value,
                'type' => NotificationType::ChatNewMessage->value,
                'title' => 'Hello',
                'message' => 'Test message',
                'sender_id' => $sender->id,
                'data' => [0 => 'indexed', 'label' => 'ok'],
            ])
            ->assertCreated();

        $row = AppNotification::query()->where('user_id', $this->user->id)->latest('id')->first();
        expect($row)->not->toBeNull()
            ->and($row->sender_id)->toBe($sender->id)
            ->and($row->data)->toMatchArray(['0' => 'indexed', 'label' => 'ok']);
    });
});

describe('notification coverage helpers', function (): void {
    test('list filters by is_read and type', function (): void {
        AppNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
            'type' => NotificationType::ChatNewMessage->value,
        ]);
        AppNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => true,
            'type' => NotificationType::Custom->value,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson('/api/notifications?is_read=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($this->user, 'api')
            ->getJson('/api/notifications?type='.NotificationType::Custom->value)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($this->user, 'api')
            ->getJson('/api/notifications?is_read=')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    test('mark read is idempotent when already read', function (): void {
        $notification = AppNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => true,
            'read_at' => now(),
        ]);

        $this->actingAs($this->user, 'api')
            ->patchJson(sprintf('/api/notifications/%d/read', $notification->id))
            ->assertOk();
    });

    test('route binding aborts when api user is missing', function (): void {
        $notification = AppNotification::factory()->create(['user_id' => $this->user->id]);

        $this->withoutMiddleware(Authenticate::class);
        $this->patchJson(sprintf('/api/notifications/%d/read', $notification->id))
            ->assertStatus(401);
    });

    test('app notification model relations scopes and user relation', function (): void {
        $sender = User::factory()->create();
        $notification = AppNotification::factory()->create([
            'user_id' => $this->user->id,
            'sender_id' => $sender->id,
            'module' => NotificationModule::Chat->value,
            'is_read' => false,
        ]);

        $notification->load(['user', 'sender']);
        expect($notification->user)->toBeInstanceOf(User::class)
            ->and($notification->sender)->toBeInstanceOf(User::class)
            ->and($notification->moduleEnum())->toBe(NotificationModule::Chat);

        expect(AppNotification::query()->forUser($this->user->id)->unread()->count())->toBeGreaterThanOrEqual(1);

        expect($this->user->inAppNotifications()->count())->toBeGreaterThanOrEqual(1);
    });

    test('app notification policy view differentiates owner', function (): void {
        $policy = new AppNotificationPolicy;
        $notification = AppNotification::factory()->create(['user_id' => $this->user->id]);

        expect($policy->view($this->user, $notification))->toBeTrue();

        $other = User::factory()->create();
        expect($policy->view($other, $notification))->toBeFalse();
    });

    test('notification service action log and authorization guards', function (): void {
        $service = app(NotificationService::class);
        $service->notifyAdministratorsActionLog(
            title: 'Audit',
            message: 'Important',
            senderId: $this->admin->id,
            entityType: 'action_log',
            entityId: 1,
            action: 'recorded',
            data: ['key' => 'value'],
        );

        expect(AppNotification::query()->where('module', NotificationModule::ActionLogs->value)->count())->toBeGreaterThan(0);

        $intruder = User::factory()->create();
        $owned = AppNotification::factory()->create(['user_id' => $this->user->id]);

        expect(fn () => $service->markAsRead($owned, $intruder))->toThrow(HttpException::class);
        expect(fn () => $service->deleteForUser($owned, $intruder))->toThrow(HttpException::class);
        expect(fn () => $service->assertCallerMayCreateNonChatNotification(null))->toThrow(HttpException::class);
    });
});
