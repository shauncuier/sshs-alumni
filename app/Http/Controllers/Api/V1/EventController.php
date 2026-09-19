<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\EventRegistrationRequest;
use App\Http\Resources\RegistrationResource;
use App\Models\Event;
use App\Models\EventTicketType;
use App\Services\Events\EventRegistrar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Events API.
 *
 * @see docs/10-api.md section 3
 */
class EventController extends Controller
{
    public function __construct(
        private readonly EventRegistrar $registrar,
    ) {}

    /**
     * List all published events.
     */
    public function index(Request $request): JsonResponse
    {
        $events = Event::query()
            ->whereIn('status', [EventStatus::Published, EventStatus::RegistrationClosed])
            ->orderBy('starts_at')
            ->paginate(min(50, max(1, (int) $request->query('per_page', 20))));

        return response()->json([
            'events' => $events->through(fn (Event $event): array => [
                'id' => $event->id,
                'slug' => $event->slug,
                'title' => $event->title,
                'subtitle' => $event->subtitle,
                'status' => $event->status->value,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'ends_at' => $event->ends_at?->toIso8601String(),
                'venue_name' => $event->venue_name,
                'is_free' => $event->is_free,
                'capacity' => $event->capacity,
                'confirmed_count' => $event->confirmed_count,
                'cover_url' => $event->cover_path ? asset('storage/'.$event->cover_path) : null,
            ]),
        ]);
    }

    /**
     * Event details with available ticket types.
     */
    public function show(Event $event): JsonResponse
    {
        abort_unless(in_array($event->status, [EventStatus::Published, EventStatus::RegistrationClosed], true), 404);

        $event->load(['ticketTypes' => fn ($q) => $q->where('is_active', true)->orderBy('display_order')]);

        return response()->json([
            'event' => [
                'id' => $event->id,
                'slug' => $event->slug,
                'title' => $event->title,
                'subtitle' => $event->subtitle,
                'description' => $event->description,
                'status' => $event->status->value,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'ends_at' => $event->ends_at?->toIso8601String(),
                'venue_name' => $event->venue_name,
                'venue_address' => $event->venue_address,
                'venue_map_url' => $event->venue_map_url,
                'is_free' => $event->is_free,
                'base_ticket_price' => $event->base_ticket_price,
                'currency' => $event->currency,
                'capacity' => $event->capacity,
                'confirmed_count' => $event->confirmed_count,
                'cover_url' => $event->cover_path ? asset('storage/'.$event->cover_path) : null,
                'tickets' => $event->ticketTypes->map(fn (EventTicketType $t): array => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'description' => $t->description,
                    'price' => (float) $t->price,
                    'is_free' => (float) $t->price == 0,
                    'available' => $t->is_active && ($t->quantity === null || $t->sold_count < $t->quantity),
                ]),
            ],
        ]);
    }

    /**
     * Register the authenticated member for an event.
     */
    public function register(EventRegistrationRequest $request, Event $event): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $member = $user->member;
        abort_if($member === null, 403, 'A verified member profile is required to register for events.');

        $validated = $request->validated();

        $ticket = ! empty($validated['ticket_type_id'])
            ? EventTicketType::query()
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->whereKey($validated['ticket_type_id'])
                ->first()
            : null;

        $registration = $this->registrar->registerMember(
            event: $event,
            member: $member,
            ticket: $ticket,
            guests: $validated['guests'] ?? [],
            notes: $validated['notes'] ?? null,
        );

        $registration->load(['event', 'ticketType']);

        return response()->json([
            'status' => $registration->status->value,
            'message' => 'Registration recorded successfully.',
            'registration' => RegistrationResource::make($registration)->resolve(),
        ], 201);
    }
}
