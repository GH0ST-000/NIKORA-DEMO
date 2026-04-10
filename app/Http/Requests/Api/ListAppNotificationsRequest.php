<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\NotificationModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListAppNotificationsRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'module' => ['sometimes', 'nullable', 'string', Rule::in(NotificationModule::values())],
            'is_read' => ['sometimes', 'nullable', Rule::in(['0', '1', 'true', 'false'])],
            'type' => ['sometimes', 'nullable', 'string', 'max:120'],
        ];
    }

    public function perPage(): int
    {
        return max(1, min($this->integer('per_page', 25), 100));
    }

    public function moduleFilter(): ?NotificationModule
    {
        $raw = $this->input('module');
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        return NotificationModule::tryFrom($raw);
    }

    public function isReadFilter(): ?bool
    {
        if (! $this->has('is_read')) {
            return null;
        }

        $v = $this->input('is_read');
        if ($v === null || $v === '') {
            return null;
        }

        return filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    public function typeFilter(): ?string
    {
        $t = $this->input('type');

        return is_string($t) && $t !== '' ? $t : null;
    }
}
