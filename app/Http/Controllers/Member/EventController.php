<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\EventRegistrationRequest;
use App\Http\Resources\RegistrationResource;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventTicketType;
use App\Models\Member;
use App\Services\Events\EventRegistrar;
use App\Support\Qr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A member's own event registrations and passes.
 *
 * @see docs/05-modules.md section 4
 */
class EventController extends Controller
{
    public function __construct(
        private readonly EventRegistrar $registrar,
    ) {}

    public function index(Request $request): Response
    {
        $member = $this->memberFor($request);

        $registrations = EventRegistration::query()
            ->where('member_id', $member->id)
            ->with(['event', 'ticketType', 'checkin'])
            ->latest('registered_at')
            ->paginate(12);

        return Inertia::render('member/events', [
            'registrations' => RegistrationResource::collection($registrations),
        ]);
    }

    /**
     * One pass, with its QR code.
     *
     * The QR encodes a URL the SERVER resolves — the registration ULID plus
     * its token. Nothing about the member is in the code itself, so a
     * photographed pass discloses nothing beyond what the gate already knows.
     */
    public function show(Request $request, EventRegistration $registration): Response
    {
        $member = $this->memberFor($request);

        // A registration belongs to exactly one member. Anyone else asking for
        // it gets a 404, not a 403 — an existence-revealing error is itself a
        // small disclosure.
        abort_unless($registration->member_id === $member->id, 404);

        $registration->load(['event', 'ticketType', 'guests', 'checkin.operator']);

        return Inertia::render('member/event-ticket', [
            'registration' => RegistrationResource::make($registration),
            'qr' => Qr::dataUri($this->passUrl($registration), size: 260),
        ]);
    }

    public function store(EventRegistrationRequest $request, Event $event): RedirectResponse
    {
        $member = $this->memberFor($request);

        if (! $this->registrar->isOpen($event)) {
            return back()->with('error', __('member.events.closed'));
        }

        // The unique index on (event_id, member_id) would reject this anyway;
        // checking first turns a database error into a sentence.
        $existing = EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('member_id', $member->id)
            ->first();

        if ($existing !== null) {
            return to_route('my.events.ticket', $existing)
                ->with('info', __('member.events.already_registered'));
        }

        $validated = $request->validated();

        $ticket = isset($validated['ticket_type_id'])
            ? EventTicketType::query()
                ->where('event_id', $event->id)
                ->where('is_active', true)
                // Scoped to THIS event, so a ticket type id copied from
                // another event resolves to null and the event's own fee
                // applies instead.
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

        $message = $registration->status === RegistrationStatus::Waitlisted
            ? __('member.events.waitlisted')
            : __('member.events.registered');

        return to_route('my.events.ticket', $registration)->with('success', $message);
    }

    /**
     * Cancelling returns the seats to the pool, so the committee can promote
     * someone from the waitlist.
     */
    public function destroy(Request $request, EventRegistration $registration): RedirectResponse
    {
        $member = $this->memberFor($request);

        abort_unless($registration->member_id === $member->id, 404);

        // A pass that has been through the gate is a record of attendance.
        abort_if($registration->checkin()->exists(), 403);

        $this->registrar->cancel($registration);

        return to_route('my.events')->with('success', __('member.events.cancelled'));
    }

    /**
     * What the QR encodes: the check-in URL for this pass.
     */
    private function passUrl(EventRegistration $registration): string
    {
        // The event is passed as a MODEL so the URL carries its ULID route
        // key, not its slug — the admin routes bind by ULID.
        return route('admin.events.checkin.scan', [
            'event' => $registration->event,
            'ulid' => $registration->ulid,
            'token' => $registration->qr_token,
        ]);
    }

    private function memberFor(Request $request): Member
    {
        $member = $request->user()?->member;

        abort_if($member === null, 404);

        return $member;
    }
}
