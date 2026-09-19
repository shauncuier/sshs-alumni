<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Enums\UserStatus;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('issues a sanctum token on successful login', function (): void {
    $batch = Batch::factory()->create(['ssc_year' => 2010]);

    $user = User::factory()->create([
        'email' => 'alumni@example.com',
        'password' => Hash::make('SecretPass123!'),
        'status' => UserStatus::Active,
    ]);

    $member = Member::factory()->create([
        'user_id' => $user->id,
        'full_name' => 'Tanvir Ahmed',
        'batch_id' => $batch->id,
        'status' => MemberStatus::Approved,
        'membership_no' => 'SSHS-2010-0012',
    ]);

    $response = $this->postJson(route('api.auth.login'), [
        'email' => 'alumni@example.com',
        'password' => 'SecretPass123!',
        'device_name' => 'iPhone Scanner',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'token',
            'token_type',
            'user' => ['id', 'name', 'email', 'status'],
            'member' => ['ulid', 'full_name', 'membership_no', 'status', 'is_approved'],
            'roles',
            'permissions',
        ]);

    expect($response->json('token_type'))->toBe('Bearer');
    expect($response->json('member.membership_no'))->toBe('SSHS-2010-0012');
});

it('rejects invalid credentials with 422', function (): void {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('CorrectPassword123!'),
    ]);

    $response = $this->postJson(route('api.auth.login'), [
        'email' => 'user@example.com',
        'password' => 'WrongPassword',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('allows authenticated users to view identity and logout', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $token = $user->createToken('test-token')->plainTextToken;

    // Me endpoint
    $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.auth.me'));

    $meResponse->assertOk()
        ->assertJsonPath('user.email', $user->email);

    // Logout endpoint
    $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.auth.logout'));

    $logoutResponse->assertOk()
        ->assertJson(['message' => 'Logged out successfully.']);

    expect($user->tokens()->count())->toBe(0);

    // Reset guards in test environment
    app('auth')->forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.auth.me'))
        ->assertUnauthorized();
});
