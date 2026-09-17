<?php

declare(strict_types=1);

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Registering for an event.
 *
 * `amount_due`, `status` and `payment_status` are absent on purpose: the price
 * comes from the ticket type or the event, and the status comes from whether
 * the seats fit. Neither is something an applicant should be able to post.
 */
class EventRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->member !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ticket_type_id' => ['nullable', 'integer', 'exists:event_ticket_types,id'],

            // A cap on guests, because the column is a tinyint and because
            // somebody will eventually paste a list of two hundred names.
            'guests' => ['nullable', 'array', 'max:10'],
            'guests.*.name' => ['required', 'string', 'max:120'],
            'guests.*.relation' => ['nullable', 'string', 'max:60'],
            'guests.*.age_group' => ['nullable', 'string', 'max:20'],

            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'guests.*.name' => __('member.events.guest_name'),
        ];
    }
}
