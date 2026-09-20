<?php

declare(strict_types=1);

use App\Enums\ContentStatus;
use App\Models\Donation;
use App\Models\FundraisingCampaign;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('displays active campaigns on the public fundraising index', function (): void {
    $campaign = FundraisingCampaign::factory()->create([
        'title' => 'Science Lab Modernization Fund',
        'goal_amount' => 500000.00,
        'raised_amount' => 250000.00,
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $this->get('/campaigns')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('campaigns.data', 1)
            ->where('campaigns.data.0.title', 'Science Lab Modernization Fund')
            ->where('campaigns.data.0.progress_percentage', 50));
});

it('does not display draft campaigns publicly', function (): void {
    $draft = FundraisingCampaign::factory()->create([
        'title' => 'Internal Draft Project',
        'status' => ContentStatus::Draft,
        'published_at' => null,
    ]);

    $this->get("/campaigns/{$draft->slug}")
        ->assertNotFound();
});

it('displays campaign details with donor recognition wall', function (): void {
    $campaign = FundraisingCampaign::factory()->create([
        'title' => 'Jubilee Memorial Library',
        'goal_amount' => 200000.00,
        'raised_amount' => 50000.00,
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    Donation::factory()->create([
        'fundraising_campaign_id' => $campaign->id,
        'donor_name' => 'Generous Alumnus',
        'amount' => 25000.00,
        'is_public' => true,
        'status' => 'received',
    ]);

    $this->get("/campaigns/{$campaign->slug}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('campaign.title', 'Jubilee Memorial Library')
            ->has('campaign.recent_donors', 1)
            ->where('campaign.recent_donors.0.donor_name', 'Generous Alumnus'));
});
