<?php

declare(strict_types=1);

use App\Enums\BloodGroup;
use App\Enums\MemberStatus;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('allows member to update their blood group privacy setting', function (): void {
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    $batch = Batch::factory()->create(['ssc_year' => 2000]);
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'batch_id' => $batch->id,
        'status' => MemberStatus::Approved,
        'blood_group' => BloodGroup::BPositive,
    ]);

    $this->actingAs($user)
        ->patch('/my/privacy', [
            'show_profile' => true,
            'show_phone' => false,
            'show_email' => false,
            'show_workplace' => false,
            'show_location' => false,
            'show_date_of_birth' => false,
            'show_in_batch_list' => true,
            'show_blood_group' => true,
        ])
        ->assertRedirect();

    expect($member->privacy()->first()->show_blood_group)->toBeTrue();
});

it('filters blood donors via directory donors_only filter', function (): void {
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    $batch = Batch::factory()->create(['ssc_year' => 2001]);
    Member::factory()->create([
        'user_id' => $user->id,
        'batch_id' => $batch->id,
        'status' => MemberStatus::Approved,
    ]);

    // Donor who opted in
    $donor = Member::factory()->create([
        'batch_id' => $batch->id,
        'status' => MemberStatus::Approved,
        'full_name' => 'Consenting Donor',
        'blood_group' => BloodGroup::OPositive,
    ]);
    $donor->privacy()->update([
        'show_profile' => true,
        'show_blood_group' => true,
    ]);

    // Member with blood group but NO consent
    $nonDonor = Member::factory()->create([
        'batch_id' => $batch->id,
        'status' => MemberStatus::Approved,
        'full_name' => 'Secret Blood Member',
        'blood_group' => BloodGroup::OPositive,
    ]);
    $nonDonor->privacy()->update([
        'show_profile' => true,
        'show_blood_group' => false,
    ]);

    $this->actingAs($user)
        ->get('/donors?blood_group=O%2B')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('members.data.0.full_name', 'Consenting Donor')
            ->where('members.data.0.blood_group', 'O+')
            ->has('members.data', 1));
});
