<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('verifies member publicly via digital card QR endpoint', function (): void {
    $batch = Batch::factory()->create(['name' => 'SSC 2005']);
    $member = Member::factory()->create([
        'full_name' => 'Kazi Nazrul',
        'status' => MemberStatus::Approved,
        'membership_no' => 'SSHS-2005-0001',
        'batch_id' => $batch->id,
    ]);

    $response = $this->getJson(route('api.verify.member', $member->ulid));

    $response->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('member.full_name', 'Kazi Nazrul')
        ->assertJsonPath('member.membership_no', 'SSHS-2005-0001')
        ->assertJsonPath('member.batch', 'SSC 2005');
});

it('enforces privacy rules and approval on directory API', function (): void {
    $approvedUser = User::factory()->create();
    $approvedMember = Member::factory()->create([
        'user_id' => $approvedUser->id,
        'status' => MemberStatus::Approved,
    ]);

    $publicTarget = Member::factory()->create([
        'full_name' => 'Visible Member',
        'status' => MemberStatus::Approved,
        'mobile' => '01700000000',
    ]);
    $publicTarget->privacy()->update([
        'show_profile' => true,
        'show_phone' => false,
    ]);

    $token = $approvedUser->createToken('app-token')->plainTextToken;

    // Directory list
    $listResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.directory.index'));

    $listResponse->assertOk()
        ->assertJsonStructure(['members' => ['data', 'links', 'meta']]);

    // Profile view — respects show_phone = false
    $profileResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.directory.show', $publicTarget->ulid));

    $profileResponse->assertOk()
        ->assertJsonPath('member.full_name', 'Visible Member')
        ->assertJsonMissing(['mobile' => '01700000000']);
});

it('serves digital card data to approved member', function (): void {
    $batch = Batch::factory()->create(['name' => 'SSC 2012']);
    $user = User::factory()->create();
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'status' => MemberStatus::Approved,
        'batch_id' => $batch->id,
        'membership_no' => 'SSHS-2012-0042',
    ]);

    $token = $user->createToken('card-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.me.card'));

    $response->assertOk()
        ->assertJsonPath('card.membership_no', 'SSHS-2012-0042')
        ->assertJsonPath('card.batch', 'SSC 2012');
});
