<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\EventRegistration;
use App\Models\EventRegistrationGuest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One registration — the member's own ticket, and the committee's list row.
 *
 * `qr_token` is NEVER serialised. It is the credential that turns a ULID into
 * an admissible pass; the QR image is rendered server-side from it, so the
 * token itself has no reason to reach the browser.
 *
 * @mixin EventRegistration
 */
class RegistrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'registrant_name' => $this->registrant_name,
            'registrant_email' => $this->registrant_email,
            'registrant_phone' => $this->registrant_phone,
            'guests_count' => $this->guests_count,
            'seats' => 1 + $this->guests_count,
            'amount_due' => (float) $this->amount_due,
            'currency' => $this->currency,
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'registered_at' => $this->registered_at?->toIso8601String(),

            'member_ulid' => $this->whenLoaded(
                'member',
                fn (): ?string => $this->member?->ulid,
            ),

            'ticket_type' => $this->whenLoaded(
                'ticketType',
                fn (): ?string => $this->ticketType?->name,
            ),

            'event' => $this->whenLoaded(
                'event',
                fn (): array => PublicEventResource::make($this->event)->resolve(),
            ),

            'guests' => $this->whenLoaded(
                'guests',
                fn (): array => $this->guests
                    ->map(fn (EventRegistrationGuest $guest): array => [
                        'name' => $guest->name,
                        'relation' => $guest->relation,
                        'age_group' => $guest->age_group,
                    ])
                    ->values()
                    ->all(),
                [],
            ),

            // Present once the pass has been through the gate. The operator's
            // name is included because the first thing anyone asks about a
            // duplicate scan is who let them in.
            'checkin' => $this->whenLoaded(
                'checkin',
                fn (): ?array => $this->checkin === null ? null : [
                    'checked_in_at' => $this->checkin->checked_in_at->toIso8601String(),
                    'gate' => $this->checkin->gate,
                    'operator' => $this->checkin->relationLoaded('operator')
                        ? $this->checkin->operator?->name
                        : null,
                ],
            ),
        ];
    }
}
