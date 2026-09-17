<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Event;
use App\Models\EventTicketType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An event as the committee sees it.
 *
 * Unlike PublicEventResource this DOES serialise an unannounced `starts_at` —
 * the admin panel is where a draft date is set and reviewed before it is
 * published, so hiding it there would make the feature unusable.
 *
 * @mixin Event
 */
class AdminEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'title' => $this->title,
            'summary' => $this->summary,
            'description' => $this->description,
            'cover_url' => $this->cover_path === null
                ? null
                : asset('storage/'.$this->cover_path),

            'date_status' => $this->date_status->value,
            'date_is_tba' => $this->dateIsTba(),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),

            'venue' => $this->venue,
            'address' => $this->address,
            'map_url' => $this->map_url,

            'registration_required' => $this->registration_required,
            'registration_opens_at' => $this->registration_opens_at?->toIso8601String(),
            'registration_closes_at' => $this->registration_closes_at?->toIso8601String(),
            'capacity' => $this->capacity,
            'registration_fee' => $this->registration_fee === null
                ? null
                : (float) $this->registration_fee,
            'currency' => $this->currency,

            'organizer_name' => $this->organizer_name,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,

            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_flagship' => $this->is_flagship,
            'published_at' => $this->published_at?->toIso8601String(),

            'registrations_count' => $this->whenCounted('registrations'),
            'checkins_count' => $this->whenCounted('checkins'),

            'ticket_types' => $this->whenLoaded(
                'ticketTypes',
                fn (): array => $this->ticketTypes
                    ->map(fn (EventTicketType $ticket): array => [
                        'id' => $ticket->id,
                        'name' => $ticket->name,
                        'description' => $ticket->description,
                        'price' => (float) $ticket->price,
                        'currency' => $ticket->currency,
                        'quantity' => $ticket->quantity,
                        'sold_count' => $ticket->sold_count,
                        'per_person_limit' => $ticket->per_person_limit,
                        'is_active' => $ticket->is_active,
                        'display_order' => $ticket->display_order,
                    ])
                    ->values()
                    ->all(),
                [],
            ),
        ];
    }
}
