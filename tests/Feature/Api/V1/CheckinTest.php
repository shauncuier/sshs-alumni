<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('allows volunteer with checkin permission to scan and admit attendees', function (): void {
    $event = Event::factory()->create([
        'slug' => 'golden-jubilee-2026',
        'status' => EventStatus::Published,
    ]);

    $member = Member::factory()->create();

    $registration = EventRegistration::factory()->create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'status' => RegistrationStatus::Confirmed,
        'registrant_name' => 'Fahim Rahman',
    ]);

    $volunteer = User::factory()->create();
    $volunteer->givePermissionTo('events.checkin');
    $token = $volunteer->createToken('scanner-device')->plainTextToken;

    // 1. Scan check before admitting
    $scanResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.checkin.scan', ['event' => $event->slug, 'ulid' => $registration->ulid]));

    $scanResponse->assertOk()
        ->assertJsonPath('found', true)
        ->assertJsonPath('already', false)
        ->assertJsonPath('admissible', true);

    // 2. Perform admission check-in
    $checkinResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.checkin.store', ['event' => $event->slug]), [
            'ulid' => $registration->ulid,
            'gate' => 'North Gate',
        ]);

    $checkinResponse->assertOk()
        ->assertJsonPath('status', 'checked_in')
        ->assertJsonStructure(['status', 'message', 'registration', 'checked_in_at']);

    expect($registration->checkin()->exists())->toBeTrue();

    // 3. Duplicate scan is refused with 422
    $duplicateResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.checkin.store', ['event' => $event->slug]), [
            'ulid' => $registration->ulid,
            'gate' => 'North Gate',
        ]);

    $duplicateResponse->assertStatus(422)
        ->assertJsonPath('status', 'already_checked_in');
});

it('forbids users without checkin permission from using gate scanner API', function (): void {
    $event = Event::factory()->create(['status' => EventStatus::Published]);
    $registration = EventRegistration::factory()->create(['event_id' => $event->id]);

    $ordinaryUser = User::factory()->create();
    $token = $ordinaryUser->createToken('ordinary-device')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.checkin.store', ['event' => $event->slug]), [
            'ulid' => $registration->ulid,
        ])
        ->assertForbidden();
});
