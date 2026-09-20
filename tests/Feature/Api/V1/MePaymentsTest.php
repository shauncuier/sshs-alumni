<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\DonationStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentStatus;
use App\Models\Donation;
use App\Models\Member;
use App\Models\MembershipFee;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('lists member payment history and totals', function (): void {
    $user = User::factory()->create();
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'status' => MemberStatus::Approved,
    ]);

    $fee = MembershipFee::factory()->create([
        'member_id' => $member->id,
    ]);

    Payment::factory()->create([
        'payable_type' => MembershipFee::class,
        'payable_id' => $fee->id,
        'payer_member_id' => $member->id,
        'amount' => 1500.00,
        'status' => PaymentStatus::Paid,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.me.payments'));

    $response->assertOk()
        ->assertJsonStructure(['payments' => ['data', 'links', 'meta'], 'totals'])
        ->assertJsonPath('totals.paid', 1500);
});

it('lists member donation history and totals', function (): void {
    $user = User::factory()->create();
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'status' => MemberStatus::Approved,
    ]);

    Donation::factory()->create([
        'donor_member_id' => $member->id,
        'amount' => 5000.00,
        'status' => DonationStatus::Received,
        'received_at' => now(),
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.me.donations'));

    $response->assertOk()
        ->assertJsonStructure(['donations' => ['data', 'links', 'meta'], 'total'])
        ->assertJsonPath('total', 5000);
});

it('refreshes personal access token via auth refresh endpoint', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('mobile-device', ['*'])->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.auth.refresh'));

    $response->assertOk()
        ->assertJsonStructure(['token', 'token_type'])
        ->assertJsonPath('token_type', 'Bearer');

    $newToken = $response->json('token');
    expect($newToken)->not()->toBeEmpty();

    // Reset guards in test environment
    app('auth')->forgetGuards();

    // Old token should be revoked
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.auth.me'))
        ->assertUnauthorized();

    // New token works
    $this->withHeader('Authorization', 'Bearer '.$newToken)
        ->getJson(route('api.auth.me'))
        ->assertOk();
});
