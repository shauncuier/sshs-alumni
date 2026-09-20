<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('verifies a valid certificate publicly via QR token', function (): void {
    $batch = Batch::factory()->create(['ssc_year' => 2004]);
    $member = Member::factory()->create([
        'batch_id' => $batch->id,
        'status' => MemberStatus::Approved,
        'full_name' => 'Awarded Alumnus',
    ]);

    $certificate = Certificate::factory()->create([
        'member_id' => $member->id,
        'recipient_name' => 'Awarded Alumnus',
        'title' => 'Certificate of Outstanding Leadership',
        'type' => 'achievement',
    ]);

    // Valid verification with correct token
    $this->get("/verify/certificate/{$certificate->ulid}?token={$certificate->qr_token}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('is_valid', true)
            ->where('certificate.title', 'Certificate of Outstanding Leadership')
            ->where('certificate.recipient_name', 'Awarded Alumnus')
            ->missing('certificate.qr_token'));

    // Verification fails with mismatched token
    $this->get("/verify/certificate/{$certificate->ulid}?token=invalid_token_123456789012345678901234567890")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('is_valid', false)
            ->where('certificate', null));
});

it('allows a member to view their own certificates', function (): void {
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    $batch = Batch::factory()->create(['ssc_year' => 2004]);
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'batch_id' => $batch->id,
        'status' => MemberStatus::Approved,
    ]);

    $certificate = Certificate::factory()->create([
        'member_id' => $member->id,
        'recipient_name' => $member->full_name,
        'title' => 'Golden Jubilee Volunteer Recognition',
    ]);

    $this->actingAs($user)
        ->get('/my/certificates')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('certificates.data', 1)
            ->where('certificates.data.0.title', 'Golden Jubilee Volunteer Recognition'));

    $this->actingAs($user)
        ->get("/my/certificates/{$certificate->ulid}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('certificate.title', 'Golden Jubilee Volunteer Recognition')
            ->has('verify_url'));
});
