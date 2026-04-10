<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationModule;
use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppNotification>
 */
final class AppNotificationFactory extends Factory
{
    protected $model = AppNotification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => NotificationType::Custom->value,
            'title' => fake()->sentence(3),
            'message' => fake()->sentence(),
            'module' => NotificationModule::Chat->value,
            'data' => null,
            'is_read' => false,
            'read_at' => null,
            'sender_id' => null,
            'entity_id' => null,
            'entity_type' => null,
            'action' => null,
        ];
    }
}
