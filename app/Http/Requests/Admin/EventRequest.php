<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Creating or editing an event.
 *
 * `date_status`, `status`, `slug`, `is_flagship` and `published_at` are absent
 * on purpose. Each is moved by a deliberate, separately permissioned action —
 * announcing a date and publishing an event are not things that should happen
 * because a field was present in a form post.
 *
 * @see docs/17-golden-jubilee.md section 2
 */
class EventRequest extends FormRequest
{
    /**
     * Authorization is the controller's, via EventPolicy.
     */
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
            'type' => ['required', new Enum(EventType::class)],
            'title' => ['required', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:50000'],

            // A draft date may be set at any time. It does not become public
            // until it is announced.
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],

            'venue' => ['nullable', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:1000'],
            'map_url' => ['nullable', 'url', 'max:500'],

            'registration_required' => ['required', 'boolean'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at'],
            // null means unlimited, which is different from zero.
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'registration_fee' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'currency' => ['required', 'string', 'size:3'],

            'organizer_name' => ['nullable', 'string', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'contact_email' => ['nullable', 'email', 'max:191'],

            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // An unchecked checkbox posts nothing; treating absent as false is what
        // lets registration be switched OFF.
        $this->merge([
            'registration_required' => $this->boolean('registration_required'),
            'currency' => $this->input('currency') ?? setting('event.default_currency', 'BDT'),
        ]);
    }
}
