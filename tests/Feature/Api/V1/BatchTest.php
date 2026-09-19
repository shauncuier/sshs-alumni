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

it('lists active batches publicly', function (): void {
    Batch::factory()->create([
        'name' => 'SSC 1998',
        'ssc_year' => 1998,
        'status' => 'active',
    ]);

    $response = $this->getJson(route('api.batches.index'));

    $response->assertOk()
        ->assertJsonPath('batches.0.name', 'SSC 1998');
});

it('exposes batch coordinators and member roster to approved member', function (): void {
    $batch = Batch::factory()->create([
        'name' => 'SSC 2002',
        'ssc_year' => 2002,
        'status' => 'active',
    ]);

    $coordinator = Member::factory()->create([
        'batch_id' => $batch->id,
        'full_name' => 'Dr. Rafiqul Islam',
        'status' => MemberStatus::Approved,
    ]);
    $batch->coordinators()->attach($coordinator->id);

    $user = User::factory()->create();
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'status' => MemberStatus::Approved,
        'batch_id' => $batch->id,
    ]);

    $token = $user->createToken('batch-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.batches.show', $batch->slug));

    $response->assertOk()
        ->assertJsonPath('can_view_members', true)
        ->assertJsonPath('coordinators.0.name', 'Dr. Rafiqul Islam')
        ->assertJsonStructure(['batch', 'coordinators', 'members' => ['data']]);
});
