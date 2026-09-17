<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicEventResource;
use App\Models\Event;
use App\Services\Events\EventRegistrar;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public event listing and detail.
 *
 * Draft and cancelled events never appear here. An event whose date is still
 * TBA does appear — that is the point of the date rule: the Jubilee is
 * announced and open for registration long before the committee fixes a day.
 *
 * Ordering puts announced upcoming events first, then TBA events, then what
 * has already happened. Sorting purely by `starts_at` would bury every TBA
 * event at one end of the list.
 *
 * @see docs/05-modules.md section 4
 */
class EventController extends Controller
{
    public function __construct(
        private readonly EventRegistrar $registrar,
    ) {}

    public function index(): Response
    {
        $events = Event::query()
            ->published()
            ->with('ticketTypes')
            ->orderByDesc('is_flagship')
            ->orderByRaw('case when starts_at is null then 1 else 0 end')
            ->orderBy('starts_at')
            ->paginate(12);

        return Inertia::render('public/events', [
            'events' => PublicEventResource::collection($events),
            'flagship' => fn (): ?array => $this->flagship(),
        ]);
    }

    public function show(Event $event): Response
    {
        // A draft is not "forbidden", it does not exist yet as far as the
        // public is concerned.
        abort_if(
            in_array($event->status, [EventStatus::Draft, EventStatus::Cancelled], true),
            404,
        );

        $event->load('ticketTypes');

        return Inertia::render('public/event-show', [
            'event' => PublicEventResource::make($event),
            'registration' => [
                'open' => $this->registrar->isOpen($event),
                // Whether seats remain, never how many.
                'full' => $this->registrar->seatsLeft($event) === 0,
            ],
        ]);
    }

    /**
     * The flagship event, for the banner on the listing page.
     *
     * @return array<string, mixed>|null
     */
    private function flagship(): ?array
    {
        $event = Event::query()
            ->published()
            ->where('is_flagship', true)
            ->first();

        return $event === null
            ? null
            : PublicEventResource::make($event)->resolve();
    }
}
