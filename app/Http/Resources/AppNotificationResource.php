<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AppNotification
 */
final class AppNotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AppNotification $n */
        $n = $this->resource;

        return [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'message' => $n->message,
            'module' => $n->module,
            'payload' => $n->data,
            'is_read' => $n->is_read,
            'read_at' => $n->read_at?->toISOString(),
            'sender_id' => $n->sender_id,
            'sender' => $n->relationLoaded('sender') && $n->sender
                ? [
                    'id' => $n->sender->id,
                    'name' => $n->sender->name,
                ]
                : null,
            'entity_id' => $n->entity_id,
            'entity_type' => $n->entity_type,
            'action' => $n->action,
            'created_at' => $n->created_at->toISOString(),
            'updated_at' => $n->updated_at->toISOString(),
        ];
    }
}
