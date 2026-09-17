<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Http\Resources\RegistrationResource;
use App\Models\Event;
use App\Models\EventCheckin;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\User;
use App\Services\Events\CheckinService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;

/**
 * The gate.
 *
 * Duplicate check-ins are prevented by a UNIQUE constraint, not by an
 * application `if`. The test that matters is the one that bypasses the service
 * entirely and proves the DATABASE refuses the second row — because on the day
 * two volunteers will scan the same pass at two gates in the same second.
 *
 * @see docs/17-golden-jubilee.md section 5
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function gateOperator(): User
{
    $user = User::factory()->create();
    // Volunteer Coordinator holds events.checkin but NOT events.edit.
    $user->syncRoles(['Volunteer Coordinator']);

    return $user;
}

function confirmedRegistration(?Event $event = null): EventRegistration
{
    $event ??= Event::factory()->create(['status' => EventStatus::RegistrationOpen]);

    return EventRegistration::factory()->create([
        'event_id' => $event->id,
        'member_id' => Member::factory()->approved()->create()->id,
        'status' => RegistrationStatus::Confirmed,
    ]);
}

describe('duplicate prevention', function (): void {
    it('is enforced by the database, not by the application', function (): void {
        $registration = confirmedRegistration();

        $row = [
            'event_registration_id' => $registration->id,
            'event_id' => $registration->event_id,
            'checked_in_at' => now(),
            'operator_id' => User::factory()->create()->id,
        ];

        EventCheckin::query()->create($row);

        // Straight at the table, past every service and controller.
        expect(fn () => EventCheckin::query()->create($row))
            ->toThrow(QueryException::class);

        expect(EventCheckin::query()->count())->toBe(1);
    });

    it('reports the first check-in rather than failing', function (): void {
        $registration = confirmedRegistration();
        $first = gateOperator();
        $second = gateOperator();

        $service = app(CheckinService::class);

        $one = $service->checkIn($registration, $first, gate: 'Main');
        $two = $service->checkIn($registration->refresh(), $second, gate: 'Side');

        expect($one['status'])->toBe('checked_in')
            ->and($two['status'])->toBe('already')
            // The operator needs to know who admitted them, and when.
            ->and($two['checkin']?->operator_id)->toBe($first->id);

        expect(EventCheckin::query()->count())->toBe(1);
    });

    it('records which operator admitted someone', function (): void {
        $registration = confirmedRegistration();
        $operator = gateOperator();

        app(CheckinService::class)->checkIn($registration, $operator, gate: 'Main');

        $this->assertDatabaseHas('event_checkins', [
            'event_registration_id' => $registration->id,
            'operator_id' => $operator->id,
            'gate' => 'Main',
        ]);
    });
});

describe('admissibility', function (): void {
    it('refuses a cancelled registration', function (): void {
        $registration = confirmedRegistration();
        $registration->update(['status' => RegistrationStatus::Cancelled]);

        $result = app(CheckinService::class)
            ->checkIn($registration, gateOperator());

        expect($result['status'])->toBe('not_admissible')
            ->and($result['reason'])->toBe('cancelled');

        expect(EventCheckin::query()->count())->toBe(0);
    });

    it('refuses a waitlisted registration — the gate does not promote', function (): void {
        $registration = confirmedRegistration();
        $registration->update(['status' => RegistrationStatus::Waitlisted]);

        $result = app(CheckinService::class)
            ->checkIn($registration, gateOperator());

        expect($result['status'])->toBe('not_admissible')
            ->and($result['reason'])->toBe('waitlisted');
    });
});

describe('resolving a scanned pass', function (): void {
    it('reads the registration from the database, never from the code', function (): void {
        $registration = confirmedRegistration();

        $found = app(CheckinService::class)->resolve(
            $registration->event,
            $registration->ulid,
            $registration->qr_token,
        );

        expect($found?->id)->toBe($registration->id);
    });

    it('rejects a ULID with the wrong token', function (): void {
        $registration = confirmedRegistration();

        $found = app(CheckinService::class)->resolve(
            $registration->event,
            $registration->ulid,
            'not-the-token',
        );

        // A ULID harvested from a URL elsewhere cannot be turned into a pass.
        expect($found)->toBeNull();
    });

    it('rejects a pass belonging to another event', function (): void {
        $registration = confirmedRegistration();
        $other = Event::factory()->create();

        $found = app(CheckinService::class)->resolve(
            $other,
            $registration->ulid,
            $registration->qr_token,
        );

        expect($found)->toBeNull();
    });
});

describe('finding someone without their pass', function (): void {
    it('matches on name', function (): void {
        $registration = confirmedRegistration();
        $registration->update(['registrant_name' => 'Rahim Uddin']);

        $this->actingAs(gateOperator())
            ->get("/admin/events/{$registration->event->ulid}/checkin?q=Rahim")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('matches', 1)
                ->where('matches.0.registrant_name', 'Rahim Uddin')
                ->where('matches.0.admissible', true)
                ->where('matches.0.already', false)
            );
    });

    it('matches on phone number', function (): void {
        $registration = confirmedRegistration();
        $registration->update(['registrant_phone' => '01712345678']);

        $this->actingAs(gateOperator())
            ->get("/admin/events/{$registration->event->ulid}/checkin?q=01712345678")
            ->assertInertia(fn ($page) => $page->has('matches', 1));
    });

    it('never reaches across events', function (): void {
        $registration = confirmedRegistration();
        $registration->update(['registrant_name' => 'Rahim Uddin']);

        $other = Event::factory()->create();

        $this->actingAs(gateOperator())
            ->get("/admin/events/{$other->ulid}/checkin?q=Rahim")
            ->assertInertia(fn ($page) => $page->has('matches', 0));
    });

    it('returns nothing without a search term', function (): void {
        $registration = confirmedRegistration();

        // An empty box lists nobody rather than everybody — a gate operator
        // scanning a list of fifty has lost more time than they saved.
        $this->actingAs(gateOperator())
            ->get("/admin/events/{$registration->event->ulid}/checkin")
            ->assertInertia(fn ($page) => $page->has('matches', 0));
    });
});

describe('the gate screen', function (): void {
    it('is open to a volunteer holding events.checkin but not events.edit', function (): void {
        $event = Event::factory()->create();
        $operator = gateOperator();

        expect($operator->can('events.checkin'))->toBeTrue()
            ->and($operator->can('events.edit'))->toBeFalse();

        $this->actingAs($operator)
            ->get("/admin/events/{$event->ulid}/checkin")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/events/checkin'));
    });

    it('is closed to an ordinary member', function (): void {
        $event = Event::factory()->create();

        $user = User::factory()->create();
        $user->syncRoles(['Member']);

        $this->actingAs($user)
            ->get("/admin/events/{$event->ulid}/checkin")
            ->assertForbidden();
    });

    it('shows who a scan belongs to WITHOUT admitting them', function (): void {
        $registration = confirmedRegistration();

        $this->actingAs(gateOperator())
            ->get("/admin/events/{$registration->event->ulid}/checkin/{$registration->ulid}?token={$registration->qr_token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('scanned.found', true)
                ->where('scanned.already', false)
                ->where('scanned.admissible', true)
            );

        // Scanning is a GET from a QR code. A camera pointed at a wall of
        // passes must not admit all of them.
        expect(EventCheckin::query()->count())->toBe(0);
    });

    it('says so when the pass is not this event\'s', function (): void {
        $registration = confirmedRegistration();
        $other = Event::factory()->create();

        $this->actingAs(gateOperator())
            ->get("/admin/events/{$other->ulid}/checkin/{$registration->ulid}")
            ->assertInertia(fn ($page) => $page->where('scanned.found', false));
    });

    it('admits on an explicit post', function (): void {
        $registration = confirmedRegistration();

        $this->actingAs(gateOperator())
            ->post(
                "/admin/events/{$registration->event->ulid}/checkin/{$registration->ulid}",
                ['gate' => 'Main'],
            )
            ->assertRedirect();

        $this->assertDatabaseHas('event_checkins', [
            'event_registration_id' => $registration->id,
        ]);
    });

    it('never puts the pass token in a serialised registration', function (): void {
        $registration = confirmedRegistration();

        // The token is the credential that turns a ULID into an admissible
        // pass. It is rendered into a QR server-side and has no reason to
        // appear in any payload.
        //
        // The scan URL itself carries the token, because that is what was
        // scanned — so this asserts on the RESOURCE, not on the whole page.
        $payload = RegistrationResource::make($registration)
            ->resolve();

        expect($payload)->not->toHaveKey('qr_token')
            ->and(json_encode($payload))->not->toContain($registration->qr_token);
    });

    it('keeps the token out of a member own ticket payload', function (): void {
        $registration = confirmedRegistration();
        $member = $registration->member;
        $user = User::factory()->create();
        $member->update(['user_id' => $user->id]);
        $user->syncRoles(['Member']);

        $this->actingAs($user)
            ->get("/my/events/{$registration->ulid}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->missing('registration.qr_token')
                // The QR arrives as a rendered image instead.
                ->has('qr')
            );
    });
});
