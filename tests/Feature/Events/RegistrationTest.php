<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventTicketType;
use App\Models\Member;
use App\Models\User;
use App\Services\Events\EventRegistrar;
use Database\Seeders\RolePermissionSeeder;

/**
 * Event registration.
 *
 * Capacity produces a WAITLIST entry, never a rejection. Turning an alumnus
 * away automatically is the wrong default for a reunion — the committee can
 * promote from the waitlist, and cannot un-offend someone the software turned
 * away on their behalf.
 *
 * @see docs/05-modules.md section 4
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * An approved member with a login.
 */
function registrant(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    Member::factory()->approved()->create(['user_id' => $user->id]);

    return $user;
}

function openEvent(array $attributes = []): Event
{
    return Event::factory()->create([
        'status' => EventStatus::RegistrationOpen,
        'registration_required' => true,
        'registration_opens_at' => null,
        'registration_closes_at' => null,
        'capacity' => null,
        'registration_fee' => 0,
        ...$attributes,
    ]);
}

describe('registering', function (): void {
    it('creates a confirmed registration with a QR token', function (): void {
        $event = openEvent();
        $user = registrant();

        $this->actingAs($user)
            ->post("/events/{$event->slug}/register", [])
            ->assertRedirect();

        $registration = EventRegistration::query()->sole();

        expect($registration->status)->toBe(RegistrationStatus::Confirmed)
            ->and($registration->member_id)->toBe($user->member->id)
            // The token is a credential generated server-side, never posted.
            ->and($registration->qr_token)->toHaveLength(40);
    });

    it('snapshots the registrant so the record survives a deleted member', function (): void {
        $event = openEvent();
        $user = registrant();

        $this->actingAs($user)->post("/events/{$event->slug}/register", []);

        $registration = EventRegistration::query()->sole();

        expect($registration->registrant_name)->toBe($user->member->full_name);
    });

    it('counts guests as seats and charges for them', function (): void {
        $event = openEvent(['registration_fee' => 500]);
        $user = registrant();

        $this->actingAs($user)->post("/events/{$event->slug}/register", [
            'guests' => [
                ['name' => 'Guest One'],
                ['name' => 'Guest Two'],
            ],
        ]);

        $registration = EventRegistration::query()->sole();

        expect($registration->guests_count)->toBe(2)
            // Three seats at 500.
            ->and((float) $registration->amount_due)->toBe(1500.0)
            ->and($registration->payment_status)->toBe(PaymentStatus::Pending)
            ->and($registration->guests()->count())->toBe(2);
    });

    it('marks a free registration as paid rather than leaving it owing nothing', function (): void {
        $event = openEvent(['registration_fee' => 0]);

        $this->actingAs(registrant())->post("/events/{$event->slug}/register", []);

        expect(EventRegistration::query()->sole()->payment_status)
            ->toBe(PaymentStatus::Paid);
    });

    it('refuses a second registration for the same event', function (): void {
        $event = openEvent();
        $user = registrant();

        $this->actingAs($user)->post("/events/{$event->slug}/register", []);
        $this->actingAs($user)->post("/events/{$event->slug}/register", []);

        expect(EventRegistration::query()->count())->toBe(1);
    });

    it('refuses when registration is not open', function (): void {
        $event = openEvent(['status' => EventStatus::RegistrationClosed]);

        $this->actingAs(registrant())
            ->post("/events/{$event->slug}/register", [])
            ->assertRedirect();

        expect(EventRegistration::query()->count())->toBe(0);
    });

    it('refuses once the closing date has passed', function (): void {
        $event = openEvent(['registration_closes_at' => now()->subDay()]);

        $this->actingAs(registrant())->post("/events/{$event->slug}/register", []);

        expect(EventRegistration::query()->count())->toBe(0);
    });

    it('is closed to a guest', function (): void {
        $event = openEvent();

        $this->post("/events/{$event->slug}/register", [])
            ->assertRedirect('/login');
    });
});

describe('capacity', function (): void {
    it('waitlists rather than rejecting once the event is full', function (): void {
        $event = openEvent(['capacity' => 2]);
        $registrar = app(EventRegistrar::class);

        $first = Member::factory()->approved()->create();
        $second = Member::factory()->approved()->create();
        $third = Member::factory()->approved()->create();

        $one = $registrar->registerMember($event, $first);
        $two = $registrar->registerMember($event, $second);
        $three = $registrar->registerMember($event, $third);

        expect($one->status)->toBe(RegistrationStatus::Confirmed)
            ->and($two->status)->toBe(RegistrationStatus::Confirmed)
            // Not rejected. Waitlisted.
            ->and($three->status)->toBe(RegistrationStatus::Waitlisted);
    });

    it('counts a guest against capacity', function (): void {
        $event = openEvent(['capacity' => 2]);
        $registrar = app(EventRegistrar::class);

        // One member plus one guest fills both seats.
        $registrar->registerMember(
            $event,
            Member::factory()->approved()->create(),
            guests: [['name' => 'Guest']],
        );

        $next = $registrar->registerMember(
            $event,
            Member::factory()->approved()->create(),
        );

        expect($next->status)->toBe(RegistrationStatus::Waitlisted)
            ->and($registrar->seatsTaken($event))->toBe(2)
            ->and($registrar->seatsLeft($event))->toBe(0);
    });

    it('treats a null capacity as unlimited', function (): void {
        $event = openEvent(['capacity' => null]);
        $registrar = app(EventRegistrar::class);

        expect($registrar->seatsLeft($event))->toBeNull();
    });

    it('returns seats to the pool when a registration is cancelled', function (): void {
        $event = openEvent(['capacity' => 1]);
        $registrar = app(EventRegistrar::class);

        $registration = $registrar->registerMember(
            $event,
            Member::factory()->approved()->create(),
        );

        expect($registrar->seatsLeft($event))->toBe(0);

        $registrar->cancel($registration);

        expect($registrar->seatsLeft($event))->toBe(1);
    });

    it('lets the committee promote past capacity, because that is their call', function (): void {
        $event = openEvent(['capacity' => 1]);
        $registrar = app(EventRegistrar::class);

        $registrar->registerMember($event, Member::factory()->approved()->create());
        $waitlisted = $registrar->registerMember(
            $event,
            Member::factory()->approved()->create(),
        );

        $manager = User::factory()->create();
        $manager->syncRoles(['Event Manager']);

        $this->actingAs($manager)
            ->post("/admin/events/{$event->ulid}/registrations/{$waitlisted->ulid}/promote")
            ->assertRedirect();

        expect($waitlisted->refresh()->status)->toBe(RegistrationStatus::Confirmed)
            // Deliberately over capacity now.
            ->and($registrar->seatsTaken($event))->toBe(2);
    });
});

describe('ticket types', function (): void {
    it('prices from the ticket rather than the event', function (): void {
        $event = openEvent(['registration_fee' => 100]);

        $ticket = EventTicketType::factory()->create([
            'event_id' => $event->id,
            'price' => 750,
            'is_active' => true,
        ]);

        $this->actingAs(registrant())->post("/events/{$event->slug}/register", [
            'ticket_type_id' => $ticket->id,
        ]);

        expect((float) EventRegistration::query()->sole()->amount_due)->toBe(750.0);
    });

    it('ignores a ticket type belonging to another event', function (): void {
        $event = openEvent(['registration_fee' => 100]);

        $foreign = EventTicketType::factory()->create([
            'event_id' => Event::factory()->create()->id,
            'price' => 5000,
            'is_active' => true,
        ]);

        $this->actingAs(registrant())->post("/events/{$event->slug}/register", [
            'ticket_type_id' => $foreign->id,
        ]);

        $registration = EventRegistration::query()->sole();

        // Falls back to the event's own fee rather than another event's price.
        expect((float) $registration->amount_due)->toBe(100.0)
            ->and($registration->ticket_type_id)->toBeNull();
    });
});

describe('a member\'s own pass', function (): void {
    it('is not readable by another member', function (): void {
        $event = openEvent();
        $owner = registrant();

        $this->actingAs($owner)->post("/events/{$event->slug}/register", []);
        $registration = EventRegistration::query()->sole();

        $other = registrant();

        // 404 rather than 403: an existence-revealing error is itself a small
        // disclosure.
        $this->actingAs($other)
            ->get("/my/events/{$registration->ulid}")
            ->assertNotFound();
    });

    it('cannot be cancelled once it has been through the gate', function (): void {
        $event = openEvent();
        $user = registrant();

        $this->actingAs($user)->post("/events/{$event->slug}/register", []);
        $registration = EventRegistration::query()->sole();

        $registration->checkin()->create([
            'event_id' => $event->id,
            'checked_in_at' => now(),
            'operator_id' => User::factory()->create()->id,
        ]);

        $this->actingAs($user)
            ->delete("/my/events/{$registration->ulid}")
            ->assertForbidden();

        expect($registration->refresh()->status)->toBe(RegistrationStatus::Confirmed);
    });
});
