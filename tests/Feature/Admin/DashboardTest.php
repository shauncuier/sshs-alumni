<?php

declare(strict_types=1);

use App\Models\Batch;
use App\Models\Event;
use App\Models\Member;
use App\Models\User;
use App\Services\Analytics\ChartDataService;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('forbids unauthenticated access to the admin dashboard', function (): void {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

it('forbids users without admin.access permission from opening the dashboard', function (): void {
    $member = User::factory()->create();
    $member->syncRoles(['Member']);

    $this->actingAs($member)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('renders the dashboard with all KPI metrics and deferred analytical charts', function (): void {
    $admin = User::factory()->create();
    $admin->syncRoles(['Super Admin']);

    $batch = Batch::factory()->create(['ssc_year' => 2005, 'members_count' => 12]);
    Member::factory()->count(3)->create(['batch_id' => $batch->id]);
    Event::factory()->create(['title' => 'Alumni Grand Reunion']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->has('stats.total_members')
            ->has('stats.verified_members')
            ->has('stats.batches')
            ->has('stats.events')
            ->has('stats.active_members')
            ->has('stats.total_donations')
            ->has('stats.event_registrations')
        );
});

it('generates all 7 analytical chart datasets via ChartDataService', function (): void {
    $service = app(ChartDataService::class);
    $charts = $service->getDashboardCharts();

    expect($charts)->toHaveKeys([
        'members_over_time',
        'members_by_batch',
        'members_by_country',
        'members_by_district',
        'members_by_profession',
        'event_registration_trend',
        'donation_trend',
    ]);

    expect($charts['members_over_time'])->toBeArray()
        ->and(count($charts['members_over_time']))->toBe(12);

    expect($charts['donation_trend'])->toBeArray()
        ->and(count($charts['donation_trend']))->toBe(12);
});
