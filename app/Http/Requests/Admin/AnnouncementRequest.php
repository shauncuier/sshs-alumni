<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\AnnouncementKind;
use App\Enums\AnnouncementLevel;
use App\Enums\AudienceScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnnouncementRequest extends FormRequest
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
            'kind' => ['required', Rule::in(AnnouncementKind::values())],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'level' => ['required', Rule::in(AnnouncementLevel::values())],
            'audience' => ['required', Rule::in(AudienceScope::values())],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_pinned' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @param  array-key|null  $key
     * @param  mixed  $default
     */
    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if (is_array($validated) && ($validated['audience'] ?? null) !== AudienceScope::Batch->value) {
            $validated['batch_id'] = null;
        }

        return $validated;
    }
}
