<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Event;
use App\Models\EventTicketType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An event as an anonymous visitor may see it.
 *
 * THE DATE RULE lives here as well as in the database: `starts_at` is only
 * serialised when `date_status` is `announced`. A page cannot render a date it
 * was never given, so no component can leak an unannounced one by forgetting a
 * conditional.
 *
 * Contact details, capacity and internal notes are absent. Seat counts are
 * absent too: "3 seats left" is a pressure tactic, and the association is not
 * running a ticket scalper.
 *
 * @see docs/17-golden-jubilee.md section 2
 *
 * @mixin Event
 */
class PublicEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $announced = ! $this->dateIsTba();

        return [
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

            // The date rule. An unannounced date is ABSENT, not null-with-a-flag,
            // so a component cannot render it by accident.
            'date_status' => $this->date_status->value,
            ...($announced ? [
                'starts_at' => $this->starts_at?->toIso8601String(),
                'ends_at' => $this->ends_at?->toIso8601String(),
            ] : []),

            'venue' => $this->venue,
            'address' => $this->address,
            'map_url' => $this->map_url,

            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_flagship' => $this->is_flagship,
            'registration_required' => $this->registration_required,
            'registration_fee' => $this->registration_fee === null
                ? null
                : (float) $this->registration_fee,
            'currency' => $this->currency,
            'organizer_name' => $this->organizer_name,

            'ticket_types' => $this->whenLoaded(
                'ticketTypes',
                fn (): array => $this->ticketTypes
                    ->where('is_active', true)
                    ->map(fn (EventTicketType $ticket): array => [
                        'id' => $ticket->id,
                        'name' => $ticket->name,
                        'description' => $ticket->description,
                        'price' => (float) $ticket->price,
                        'currency' => $ticket->currency,
                        'per_person_limit' => $ticket->per_person_limit,
                        // Whether any remain, never how many. A running
                        // countdown manufactures urgency.
                        'available' => $ticket->quantity === null
                            || $ticket->sold_count < $ticket->quantity,
                    ])
                    ->values()
                    ->all(),
                [],
            ),
        ];
    }
}
