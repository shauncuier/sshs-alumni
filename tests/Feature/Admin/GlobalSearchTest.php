<?php

declare(strict_types=1);

use App\Models\Batch;
use App\Models\CrmContact;
use App\Models\Event;
use App\Models\Member;
use App\Models\News;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('forbids guests and users without admin.access from global search', function (): void {
    $this->getJson(route('admin.search', ['q' => 'test']))
        ->assertUnauthorized();

    $member = User::factory()->create();
    $member->syncRoles(['Member']);

    $this->actingAs($member)
        ->getJson(route('admin.search', ['q' => 'test']))
        ->assertForbidden();
});

it('returns empty results when search query is shorter than 2 characters', function (): void {
    $admin = User::factory()->create();
    $admin->syncRoles(['Super Admin']);

    $this->actingAs($admin)
        ->getJson(route('admin.search', ['q' => 'a']))
        ->assertOk()
        ->assertJson(['results' => []]);
});

it('searches across authorized entities matching the query term', function (): void {
    $admin = User::factory()->create();
    $admin->syncRoles(['Super Admin']);

    $member = Member::factory()->create(['full_name' => 'Tariqul Islam Special']);
    $batch = Batch::factory()->create(['name' => 'Special Batch 1999', 'ssc_year' => 1999]);
    $event = Event::factory()->create(['title' => 'Special Golden Reunion']);
    $contact = CrmContact::factory()->create(['name' => 'Special Donor VIP']);
    $news = News::factory()->create(['title' => 'Special Anniversary Feature']);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.search', ['q' => 'Special']))
        ->assertOk();

    $results = $response->json('results');

    expect($results)->toHaveKey('Members')
        ->and($results)->toHaveKey('Batches')
        ->and($results)->toHaveKey('Events')
        ->and($results)->toHaveKey('CRM Contacts')
        ->and($results)->toHaveKey('News');

    expect($results['Members'][0]['title'])->toContain('Tariqul Islam Special');
    expect($results['Batches'][0]['title'])->toContain('Special Batch 1999');
    expect($results['Events'][0]['title'])->toContain('Special Golden Reunion');
    expect($results['CRM Contacts'][0]['title'])->toContain('Special Donor VIP');
    expect($results['News'][0]['title'])->toContain('Special Anniversary Feature');
});

it('respects permission gates during global search', function (): void {
    $user = User::factory()->create();
    // Give user admin.access and events.view, but not crm.view
    $user->givePermissionTo(['admin.access', 'events.view']);

    Event::factory()->create(['title' => 'Unique Secret Gala']);
    CrmContact::factory()->create(['name' => 'Unique Secret Contact']);

    $response = $this->actingAs($user)
        ->getJson(route('admin.search', ['q' => 'Unique Secret']))
        ->assertOk();

    $results = $response->json('results');

    expect($results)->toHaveKey('Events')
        ->and($results)->not->toHaveKey('CRM Contacts');
});
