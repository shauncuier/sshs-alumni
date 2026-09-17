<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\EventDateStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Http\Resources\AdminEventResource;
use App\Http\Resources\RegistrationResource;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventTicketType;
use App\Services\Events\CheckinService;
use App\Services\Events\EventRegistrar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Event administration.
 *
 * THE DATE RULE is enforced on write, not only on read: setting `starts_at`
 * does not announce it. `date_status` moves to `announced` through the
 * publish-date action, which sits behind `events.publish` — so a date can be
 * drafted and reviewed before the public sees it, and reverted afterwards
 * because the committee's plans are allowed to change.
 *
 * @see docs/17-golden-jubilee.md section 2
 */
class EventController extends Controller
{
    public function __construct(
        private readonly EventRegistrar $registrar,
        private readonly CheckinService $checkins,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Event::class);

        $term = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $events = Event::query()
            ->withCount(['registrations', 'checkins'])
            ->when($term !== '', fn ($query) => $query->where('title', 'like', '%'.$term.'%'))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('is_flagship')
            ->orderByRaw('case when starts_at is null then 1 else 0 end')
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/events/index', [
            'events' => AdminEventResource::collection($events),
            'filters' => [
                'q' => $term === '' ? null : $term,
                'status' => $status === '' ? null : $status,
            ],
            'options' => [
                'statuses' => EventStatus::options(),
                'types' => EventType::options(),
            ],
            'can' => [
                'create' => $request->user()?->can('create', Event::class) ?? false,
            ],
        ]);
    }

    public function show(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);

        $event->loadCount(['registrations', 'checkins']);
        $event->load('ticketTypes');

        $user = $request->user();

        return Inertia::render('admin/events/show', [
            'event' => AdminEventResource::make($event),
            'seats' => [
                'capacity' => $event->capacity,
                'taken' => $this->registrar->seatsTaken($event),
                'left' => $this->registrar->seatsLeft($event),
            ],
            'checkins' => $this->checkins->stats($event),
            'options' => [
                'statuses' => EventStatus::options(),
                'types' => EventType::options(),
            ],
            'can' => [
                'update' => $user?->can('update', $event) ?? false,
                'publish' => $user?->can('publish', $event) ?? false,
                'checkin' => $user?->can('checkin', $event) ?? false,
                'delete' => $user?->can('delete', $event) ?? false,
            ],
        ]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $this->authorize('create', Event::class);

        $event = Event::query()->create([
            ...$request->validated(),
            'slug' => $this->uniqueSlug($request->string('title')->value()),
            // A new event is always a draft, whatever the payload says, and
            // its date always starts unannounced.
            'status' => EventStatus::Draft,
            'date_status' => EventDateStatus::Tba,
            'created_by' => $request->user()?->id,
        ]);

        return to_route('admin.events.show', $event)
            ->with('success', __('common.states.saved'));
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $validated = $request->validated();

        // Changing the date of an ALREADY ANNOUNCED event keeps it announced —
        // the committee is correcting a published date, not un-publishing it.
        // Setting a date on a TBA event does not announce it; that is a
        // separate, permissioned act.
        unset($validated['date_status'], $validated['status']);

        $event->update($validated);

        return back()->with('success', __('common.states.saved'));
    }

    /**
     * Move an event through its lifecycle.
     *
     * Publishing is what makes it public, so it sits behind `events.publish`
     * rather than the broader edit permission.
     */
    public function transition(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('publish', $event);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_column(EventStatus::cases(), 'value'))],
        ]);

        $status = EventStatus::from($validated['status']);

        $event->update([
            'status' => $status,
            'published_at' => $event->published_at ?? ($status === EventStatus::Draft ? null : now()),
        ]);

        return back()->with('success', __('admin.events.status_changed', [
            'status' => $status->label(),
        ]));
    }

    /**
     * Announce or retract the date.
     *
     * This is the only place `date_status` moves. Retracting is supported on
     * purpose: a date that has to be pulled back is exactly when the "to be
     * announced" line matters most.
     */
    public function announceDate(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('publish', $event);

        $validated = $request->validate([
            'announce' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'required_if:announce,true', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        if ($validated['announce'] === false) {
            $event->update(['date_status' => EventDateStatus::Tba]);

            return back()->with('warning', __('admin.events.date_retracted'));
        }

        $event->update([
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'] ?? null,
            'date_status' => EventDateStatus::Announced,
        ]);

        return back()->with('success', __('admin.events.date_announced'));
    }

    /**
     * The registration list for one event.
     */
    public function registrations(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);

        $status = (string) $request->query('status', '');
        $term = trim((string) $request->query('q', ''));

        $registrations = EventRegistration::query()
            ->where('event_id', $event->id)
            ->with(['member', 'ticketType', 'checkin'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($term !== '', fn ($query) => $query->where(function ($inner) use ($term): void {
                $inner->where('registrant_name', 'like', '%'.$term.'%')
                    ->orWhere('registrant_email', 'like', '%'.$term.'%')
                    ->orWhere('registrant_phone', 'like', '%'.$term.'%');
            }))
            ->latest('registered_at')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('admin/events/registrations', [
            'event' => AdminEventResource::make($event),
            'registrations' => RegistrationResource::collection($registrations),
            'filters' => [
                'q' => $term === '' ? null : $term,
                'status' => $status === '' ? null : $status,
            ],
            'seats' => [
                'capacity' => $event->capacity,
                'taken' => $this->registrar->seatsTaken($event),
                'left' => $this->registrar->seatsLeft($event),
            ],
        ]);
    }

    /**
     * Record a walk-in.
     *
     * Somebody turns up on the day who never registered: a former teacher, a
     * guest speaker, a parent. The office enters them here and they get a
     * registration like anybody else — including a pass, so the gate count
     * stays honest.
     */
    public function storeRegistration(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:32'],
            'ticket_type_id' => ['nullable', 'integer'],
            'guests_count' => ['nullable', 'integer', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $ticket = isset($validated['ticket_type_id'])
            ? EventTicketType::query()
                ->where('event_id', $event->id)
                ->whereKey($validated['ticket_type_id'])
                ->first()
            : null;

        // The office enters a head count rather than every guest's name.
        $guests = array_fill(
            0,
            (int) ($validated['guests_count'] ?? 0),
            ['name' => __('admin.events.walk_in_guest')],
        );

        $this->registrar->registerGuest(
            event: $event,
            registrant: [
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ],
            ticket: $ticket,
            guests: $guests,
            notes: $validated['notes'] ?? null,
        );

        return back()->with('success', __('admin.events.walk_in_added', [
            'name' => $validated['name'],
        ]));
    }

    /**
     * Promote a waitlisted registration.
     *
     * The committee decides this, not the software: a promotion may legitimately
     * push the event past its stated capacity, and that is their call.
     */
    public function promote(Event $event, EventRegistration $registration): RedirectResponse
    {
        $this->authorize('update', $event);

        abort_unless($registration->event_id === $event->id, 404);

        $this->registrar->promoteFromWaitlist($registration);

        return back()->with('success', __('admin.events.promoted', [
            'name' => $registration->registrant_name,
        ]));
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $suffix = 1;

        // Soft-deleted events keep their slug, and the unique index ignores
        // deletion.
        while (Event::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$suffix;
        }

        return $slug;
    }
}
