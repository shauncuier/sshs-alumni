<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\MemberStatus;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('forbids users without admin permissions from admin members API', function (): void {
    $user = User::factory()->create();
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'status' => MemberStatus::Approved,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.members.index'))
        ->assertForbidden();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.members.show', $member->ulid))
        ->assertForbidden();
});

it('allows admin users with members.view permission to list and view members', function (): void {
    $adminUser = User::factory()->create();
    $adminUser->givePermissionTo(['admin.access', 'members.view']);

    $batch = Batch::factory()->create(['name' => 'SSC 2000']);
    $targetMember = Member::factory()->create([
        'full_name' => 'Admin Test Target',
        'status' => MemberStatus::Approved,
        'batch_id' => $batch->id,
    ]);

    $token = $adminUser->createToken('admin-token')->plainTextToken;

    // List members
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.members.index'));

    $response->assertOk()
        ->assertJsonStructure(['members' => ['data', 'links', 'meta']]);

    // Show member detail
    $showResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.members.show', $targetMember->ulid));

    $showResponse->assertOk()
        ->assertJsonPath('member.full_name', 'Admin Test Target');
});
