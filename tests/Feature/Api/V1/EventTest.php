<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\MemberStatus;
use App\Models\Event;
use App\Models\EventTicketType;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('lists published events and details', function (): void {
    $event = Event::factory()->create([
        'title' => 'Annual Grand Reunion 2026',
        'status' => EventStatus::Published,
    ]);

    $ticket = EventTicketType::factory()->create([
        'event_id' => $event->id,
        'name' => 'General Admission',
        'price' => 1000,
        'is_active' => true,
    ]);

    // Public index
    $indexResponse = $this->getJson(route('api.events.index'));
    $indexResponse->assertOk()
        ->assertJsonPath('events.data.0.title', 'Annual Grand Reunion 2026');

    // Public show
    $showResponse = $this->getJson(route('api.events.show', $event->slug));
    $showResponse->assertOk()
        ->assertJsonPath('event.title', 'Annual Grand Reunion 2026')
        ->assertJsonPath('event.tickets.0.name', 'General Admission');
});

it('allows authenticated member to register for an event', function (): void {
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
        'capacity' => 100,
    ]);

    $ticket = EventTicketType::factory()->create([
        'event_id' => $event->id,
        'price' => 500,
        'is_active' => true,
    ]);

    $user = User::factory()->create();
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'status' => MemberStatus::Approved,
    ]);

    $token = $user->createToken('register-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.events.register', $event->slug), [
            'ticket_type_id' => $ticket->id,
            'notes' => 'Looking forward to the event!',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'confirmed')
        ->assertJsonStructure(['status', 'message', 'registration' => ['ulid', 'event', 'ticket_type']]);

    expect($event->registrations()->where('member_id', $member->id)->exists())->toBeTrue();
});
