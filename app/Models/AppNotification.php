<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationModule;
use Database\Factories\AppNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * In-app notification (stored in `app_notifications`; API path `/notifications`).
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property string $module
 * @property array<string, mixed>|null $data
 * @property bool $is_read
 * @property Carbon|null $read_at
 * @property int|null $sender_id
 * @property int|null $entity_id
 * @property string|null $entity_type
 * @property string|null $action
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read User|null $sender
 */
#[Fillable([
    'user_id',
    'type',
    'title',
    'message',
    'module',
    'data',
    'is_read',
    'read_at',
    'sender_id',
    'entity_id',
    'entity_type',
    'action',
])]
final class AppNotification extends Model
{
    /** @use HasFactory<AppNotificationFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function moduleEnum(): NotificationModule
    {
        return NotificationModule::from($this->module);
    }

    /**
     * @param  Builder<self>  $query
     */
    protected function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * @param  Builder<self>  $query
     */
    protected function scopeUnread(Builder $query): void
    {
        $query->where('is_read', false);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }
}
