<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventTicketType;
use App\Models\Member;
use App\Models\User;
use App\Services\Events\EventRegistrar;
use Database\Seeders\RolePermissionSeeder;

/**
 * Ticket types, and the walk-in entry that uses them.
 *
 * `sold_count` belongs to the registrar. An administrator correcting a price
 * must not be able to rewrite how many have been sold.
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function eventManager(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Event Manager']);

    return $user;
}

describe('managing ticket types', function (): void {
    it('creates one, taking the currency from the event', function (): void {
        $event = Event::factory()->create(['currency' => 'BDT']);

        $this->actingAs(eventManager())
            ->post("/admin/events/{$event->ulid}/tickets", [
                'name' => 'Standard',
                'price' => 1500,
                'per_person_limit' => 2,
                'is_active' => true,
            ])
            ->assertRedirect();

        $ticket = EventTicketType::query()->sole();

        expect($ticket->name)->toBe('Standard')
            ->and((float) $ticket->price)->toBe(1500.0)
            ->and($ticket->currency)->toBe('BDT')
            // Never posted, always zero at creation.
            ->and($ticket->sold_count)->toBe(0);
    });

    it('refuses a posted sold_count', function (): void {
        $event = Event::factory()->create();

        $this->actingAs(eventManager())
            ->post("/admin/events/{$event->ulid}/tickets", [
                'name' => 'Standard',
                'price' => 100,
                'per_person_limit' => 1,
                'is_active' => true,
                'sold_count' => 999,
            ]);

        expect(EventTicketType::query()->sole()->sold_count)->toBe(0);
    });

    it('deletes an unsold ticket type', function (): void {
        $event = Event::factory()->create();
        $ticket = EventTicketType::factory()->create([
            'event_id' => $event->id,
            'sold_count' => 0,
        ]);

        $this->actingAs(eventManager())
            ->delete("/admin/events/{$event->ulid}/tickets/{$ticket->id}")
            ->assertRedirect();

        expect(EventTicketType::query()->count())->toBe(0);
    });

    it('withdraws rather than deletes one that has been sold', function (): void {
        $event = Event::factory()->create();
        $ticket = EventTicketType::factory()->create([
            'event_id' => $event->id,
            'sold_count' => 3,
            'is_active' => true,
        ]);

        $this->actingAs(eventManager())
            ->delete("/admin/events/{$event->ulid}/tickets/{$ticket->id}")
            ->assertRedirect();

        // Deleting it would orphan the pricing of three registrations.
        expect(EventTicketType::query()->count())->toBe(1)
            ->and($ticket->refresh()->is_active)->toBeFalse();
    });

    it('refuses a ticket type belonging to another event', function (): void {
        $event = Event::factory()->create();
        $foreign = EventTicketType::factory()->create([
            'event_id' => Event::factory()->create()->id,
        ]);

        $this->actingAs(eventManager())
            ->delete("/admin/events/{$event->ulid}/tickets/{$foreign->id}")
            ->assertNotFound();
    });

    it('is closed to someone without events.edit', function (): void {
        $event = Event::factory()->create();

        $user = User::factory()->create();
        // Volunteer Coordinator can run the gate but not edit the event.
        $user->syncRoles(['Volunteer Coordinator']);

        $this->actingAs($user)
            ->post("/admin/events/{$event->ulid}/tickets", [
                'name' => 'Sneaky',
                'price' => 0,
                'per_person_limit' => 1,
                'is_active' => true,
            ])
            ->assertForbidden();
    });
});

describe('sold_count', function (): void {
    it('is incremented by a confirmed registration and returned on cancel', function (): void {
        $event = Event::factory()->create([
            'status' => EventStatus::RegistrationOpen,
            'registration_required' => true,
            'capacity' => null,
        ]);

        $ticket = EventTicketType::factory()->create([
            'event_id' => $event->id,
            'price' => 500,
            'sold_count' => 0,
            'is_active' => true,
        ]);

        $registrar = app(EventRegistrar::class);

        $registration = $registrar->registerMember(
            $event,
            Member::factory()->approved()->create(),
            ticket: $ticket,
        );

        expect($ticket->refresh()->sold_count)->toBe(1);

        $registrar->cancel($registration);

        expect($ticket->refresh()->sold_count)->toBe(0);
    });

    it('is not incremented by a waitlisted registration', function (): void {
        $event = Event::factory()->create([
            'status' => EventStatus::RegistrationOpen,
            'registration_required' => true,
            'capacity' => 0,
        ]);

        $ticket = EventTicketType::factory()->create([
            'event_id' => $event->id,
            'sold_count' => 0,
        ]);

        $registration = app(EventRegistrar::class)->registerMember(
            $event,
            Member::factory()->approved()->create(),
            ticket: $ticket,
        );

        expect($registration->status)->toBe(RegistrationStatus::Waitlisted)
            ->and($ticket->refresh()->sold_count)->toBe(0);
    });
});

describe('walk-ins', function (): void {
    it('records someone who never registered', function (): void {
        $event = Event::factory()->create([
            'status' => EventStatus::RegistrationOpen,
            'registration_required' => true,
        ]);

        $this->actingAs(eventManager())
            ->post("/admin/events/{$event->ulid}/registrations", [
                'name' => 'Abdul Karim',
                'phone' => '01712345678',
                'guests_count' => 2,
            ])
            ->assertRedirect();

        $registration = EventRegistration::query()->sole();

        expect($registration->registrant_name)->toBe('Abdul Karim')
            // No alumni record — that is the point of a walk-in.
            ->and($registration->member_id)->toBeNull()
            ->and($registration->guests_count)->toBe(2)
            // They get a pass like anybody else, so the gate count stays honest.
            ->and($registration->qr_token)->toHaveLength(40);
    });

    it('requires a name', function (): void {
        $event = Event::factory()->create();

        $this->actingAs(eventManager())
            ->post("/admin/events/{$event->ulid}/registrations", ['guests_count' => 1])
            ->assertSessionHasErrors('name');
    });

    it('is closed to someone without events.edit', function (): void {
        $event = Event::factory()->create();

        $user = User::factory()->create();
        $user->syncRoles(['Volunteer Coordinator']);

        $this->actingAs($user)
            ->post("/admin/events/{$event->ulid}/registrations", ['name' => 'Nope'])
            ->assertForbidden();
    });
});
