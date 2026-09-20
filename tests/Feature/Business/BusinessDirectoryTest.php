<?php

declare(strict_types=1);

use App\Enums\ContentStatus;
use App\Enums\MemberStatus;
use App\Models\Batch;
use App\Models\BusinessListing;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('allows public to browse published business listings', function (): void {
    $batch = Batch::factory()->create(['ssc_year' => 2000]);
    $member = Member::factory()->create([
        'batch_id' => $batch->id,
        'status' => MemberStatus::Approved,
    ]);

    $business = BusinessListing::factory()->create([
        'member_id' => $member->id,
        'name' => 'Alumni Tech Solutions',
        'category' => 'Technology',
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $this->get('/businesses')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('businesses.data', 1)
            ->where('businesses.data.0.name', 'Alumni Tech Solutions'));
});

it('does not expose draft business listings to the public', function (): void {
    $batch = Batch::factory()->create(['ssc_year' => 2000]);
    $member = Member::factory()->create([
        'batch_id' => $batch->id,
        'status' => MemberStatus::Approved,
    ]);

    $draft = BusinessListing::factory()->create([
        'member_id' => $member->id,
        'name' => 'Secret Enterprise',
        'status' => ContentStatus::Draft,
        'published_at' => null,
    ]);

    $this->get("/businesses/{$draft->ulid}")
        ->assertNotFound();
});

it('allows an approved member to create, update and delete a business listing', function (): void {
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    $batch = Batch::factory()->create(['ssc_year' => 2005]);
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'batch_id' => $batch->id,
        'status' => MemberStatus::Approved,
    ]);

    // Create
    $this->actingAs($user)
        ->post('/my/businesses', [
            'name' => 'Vertex Architects',
            'category' => 'Architecture',
            'industry' => 'Design & Construction',
            'tagline' => 'Sustainable modern spaces',
            'city' => 'Dhaka',
            'phone' => '01711223344',
            'email' => 'contact@vertex.test',
            'website' => 'https://vertex.test',
            'alumni_discount' => '15% on consultancy',
        ])
        ->assertRedirect();

    $business = BusinessListing::query()->where('name', 'Vertex Architects')->firstOrFail();
    expect($business->member_id)->toBe($member->id);

    // Update
    $this->actingAs($user)
        ->put("/my/businesses/{$business->ulid}", [
            'name' => 'Vertex Architects & Interiors',
            'category' => 'Architecture',
            'industry' => 'Design & Construction',
            'tagline' => 'Sustainable modern spaces',
            'city' => 'Dhaka',
        ])
        ->assertRedirect();

    expect($business->refresh()->name)->toBe('Vertex Architects & Interiors');

    // Delete
    $this->actingAs($user)
        ->delete("/my/businesses/{$business->ulid}")
        ->assertRedirect();

    expect($business->fresh()->trashed())->toBeTrue();
});

it('forbids members from modifying business listings owned by others', function (): void {
    $user1 = User::factory()->create();
    $user1->syncRoles(['Member']);
    $member1 = Member::factory()->create(['user_id' => $user1->id, 'status' => MemberStatus::Approved]);

    $user2 = User::factory()->create();
    $user2->syncRoles(['Member']);
    $member2 = Member::factory()->create(['user_id' => $user2->id, 'status' => MemberStatus::Approved]);

    $business = BusinessListing::factory()->create(['member_id' => $member1->id]);

    $this->actingAs($user2)
        ->put("/my/businesses/{$business->ulid}", [
            'name' => 'Hacked Business',
            'category' => 'Technology',
        ])
        ->assertForbidden();
});
