<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\NotificationModule;
use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAppNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'module' => ['required', 'string', Rule::in(NotificationModule::values())],
            'type' => ['required', 'string', Rule::in(NotificationType::values())],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'data' => ['nullable', 'array'],
            'sender_id' => ['nullable', 'integer', 'exists:users,id'],
            'entity_id' => ['nullable', 'integer'],
            'entity_type' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function module(): NotificationModule
    {
        return NotificationModule::from($this->string('module')->toString());
    }

    public function type(): NotificationType
    {
        return NotificationType::from($this->string('type')->toString());
    }
}
