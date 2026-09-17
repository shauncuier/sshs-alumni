<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventTicketType;
use App\Models\Member;
use App\Services\Crm\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Turning an intention to attend into a registration.
 *
 * Capacity produces a WAITLIST entry, never a rejection. Turning an alumnus
 * away automatically is the wrong default for a reunion — the committee can
 * always promote from the waitlist, and cannot un-offend someone the software
 * turned away on their behalf.
 *
 * The seat count and the ticket's `sold_count` are read and written inside one
 * transaction with the event row locked, because two people pressing submit at
 * the same moment is exactly what happens when a date is finally announced.
 *
 * @see docs/05-modules.md section 4
 */
class EventRegistrar
{
    public function __construct(
        private readonly ActivityLogger $activities,
    ) {}

    /**
     * Register a member for an event.
     *
     * @param  array<int, array{name: string, relation?: string|null, age_group?: string|null}>  $guests
     */
    public function registerMember(
        Event $event,
        Member $member,
        ?EventTicketType $ticket = null,
        array $guests = [],
        ?string $notes = null,
    ): EventRegistration {
        return $this->create(
            event: $event,
            ticket: $ticket,
            guests: $guests,
            attributes: [
                'member_id' => $member->id,
                'registrant_name' => $member->full_name,
                'registrant_email' => $member->email,
                'registrant_phone' => $member->mobile,
                'notes' => $notes,
            ],
        );
    }

    /**
     * Register someone with no alumni record — a guest speaker, a parent, a
     * former teacher the office is entering by hand.
     *
     * @param  array{name: string, email?: string|null, phone?: string|null}  $registrant
     * @param  array<int, array{name: string, relation?: string|null, age_group?: string|null}>  $guests
     */
    public function registerGuest(
        Event $event,
        array $registrant,
        ?EventTicketType $ticket = null,
        array $guests = [],
        ?string $notes = null,
    ): EventRegistration {
        return $this->create(
            event: $event,
            ticket: $ticket,
            guests: $guests,
            attributes: [
                'registrant_name' => $registrant['name'],
                'registrant_email' => $registrant['email'] ?? null,
                'registrant_phone' => $registrant['phone'] ?? null,
                'notes' => $notes,
            ],
        );
    }

    /**
     * Whether the event is accepting registrations right now.
     *
     * Capacity is deliberately NOT part of this: a full event still accepts
     * registrations, onto the waitlist.
     */
    public function isOpen(Event $event): bool
    {
        if (! $event->registration_required) {
            return false;
        }

        if ($event->status !== EventStatus::RegistrationOpen) {
            return false;
        }

        $now = now();

        if ($event->registration_opens_at !== null && $now->lt($event->registration_opens_at)) {
            return false;
        }

        if ($event->registration_closes_at !== null && $now->gt($event->registration_closes_at)) {
            return false;
        }

        return true;
    }

    /**
     * Seats already taken, counting guests — a member bringing three people
     * occupies four seats, not one.
     */
    public function seatsTaken(Event $event): int
    {
        /** @var int $taken */
        $taken = $event->registrations()
            ->where('status', RegistrationStatus::Confirmed)
            ->selectRaw('coalesce(sum(1 + guests_count), 0) as seats')
            ->value('seats') ?? 0;

        return (int) $taken;
    }

    /**
     * Seats left, or null when the event has no capacity limit.
     */
    public function seatsLeft(Event $event): ?int
    {
        if ($event->capacity === null) {
            return null;
        }

        return max(0, $event->capacity - $this->seatsTaken($event));
    }

    /**
     * Cancel a registration and return its seats to the pool.
     */
    public function cancel(EventRegistration $registration): EventRegistration
    {
        return DB::transaction(function () use ($registration): EventRegistration {
            $registration->update(['status' => RegistrationStatus::Cancelled]);

            if ($registration->ticket_type_id !== null) {
                EventTicketType::query()
                    ->whereKey($registration->ticket_type_id)
                    ->where('sold_count', '>', 0)
                    ->decrement('sold_count');
            }

            return $registration->refresh();
        });
    }

    /**
     * Move a waitlisted registration to confirmed.
     *
     * The committee decides this, not the software: a promotion may legitimately
     * push the event over its stated capacity, and that is their call to make.
     */
    public function promoteFromWaitlist(EventRegistration $registration): EventRegistration
    {
        $registration->update(['status' => RegistrationStatus::Confirmed]);

        return $registration->refresh();
    }

    /**
     * @param  array<int, array{name: string, relation?: string|null, age_group?: string|null}>  $guests
     * @param  array<string, mixed>  $attributes
     */
    private function create(
        Event $event,
        ?EventTicketType $ticket,
        array $guests,
        array $attributes,
    ): EventRegistration {
        if ($ticket !== null && $ticket->event_id !== $event->id) {
            // A ticket type from another event would price this one wrongly
            // and corrupt that event's sold count.
            throw new RuntimeException('The ticket type does not belong to this event.');
        }

        return DB::transaction(function () use ($event, $ticket, $guests, $attributes): EventRegistration {
            // Lock the event row so two simultaneous submits cannot both read
            // the same remaining-seat count.
            $locked = Event::query()->lockForUpdate()->findOrFail($event->id);

            $guestCount = count($guests);
            $seatsWanted = 1 + $guestCount;

            $status = $this->fits($locked, $seatsWanted)
                ? RegistrationStatus::Confirmed
                : RegistrationStatus::Waitlisted;

            // With no ticket type the event's own fee applies, and an event
            // with neither is free.
            $unitPrice = (float) ($ticket === null
                ? $locked->registration_fee ?? 0
                : $ticket->price);

            // Guests occupy a seat and are charged for it. A free event
            // multiplies zero and stays free.
            $amountDue = $unitPrice * $seatsWanted;

            $registration = new EventRegistration;

            // `qr_token` and `payment_status` are NOT fillable — they are a
            // credential and a money field, neither of which may come from a
            // request. They are set here, in the same insert, because
            // `qr_token` is NOT NULL and a second write would fail.
            $registration->forceFill([
                ...$attributes,
                'event_id' => $locked->id,
                'ticket_type_id' => $ticket?->id,
                'guests_count' => $guestCount,
                'amount_due' => $amountDue,
                'currency' => $ticket === null ? $locked->currency : $ticket->currency,
                'status' => $status,
                'registered_at' => now(),
                'qr_token' => Str::random(40),
                'payment_status' => $amountDue > 0
                    ? PaymentStatus::Pending
                    : PaymentStatus::Paid,
            ]);

            $registration->save();

            foreach ($guests as $guest) {
                $registration->guests()->create([
                    'name' => $guest['name'],
                    'relation' => $guest['relation'] ?? null,
                    'age_group' => $guest['age_group'] ?? null,
                ]);
            }

            $this->recordOnTimeline($registration, $locked, $status);

            if ($ticket !== null && $status === RegistrationStatus::Confirmed) {
                $ticket->increment('sold_count');
            }

            return $registration->refresh();
        });
    }

    /**
     * Put the registration on the registrant's CRM timeline.
     *
     * Written here rather than by the caller, so a registration can never
     * happen without appearing in the person's history — which is the whole
     * point of a timeline that spans members and contacts.
     */
    private function recordOnTimeline(
        EventRegistration $registration,
        Event $event,
        RegistrationStatus $status,
    ): void {
        $subject = $registration->member ?? $registration->crmContact;

        // A walk-in with neither a member nor a contact record has no timeline
        // to write to. That is a real case, not an error.
        if ($subject === null) {
            return;
        }

        $this->activities->system(
            subject: $subject,
            subjectLine: __('admin.crm.system.registered_for', [
                'event' => $event->title,
            ]),
            body: $status === RegistrationStatus::Waitlisted
                ? (string) __('member.events.waitlisted')
                : null,
            meta: [
                'event_id' => $event->id,
                'registration_ulid' => $registration->ulid,
                'status' => $status->value,
            ],
        );
    }

    /**
     * Whether this many more seats fit inside the event's capacity.
     */
    private function fits(Event $event, int $seatsWanted): bool
    {
        if ($event->capacity === null) {
            return true;
        }

        return $this->seatsTaken($event) + $seatsWanted <= $event->capacity;
    }
}
